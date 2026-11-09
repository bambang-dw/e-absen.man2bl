<?php

namespace Tests\Feature;

use App\Http\Controllers\PengaturanAkunController;
use App\Models\Absensi;
use App\Models\AbsensiGuru;
use App\Models\HariLibur;
use App\Models\Jadwal;
use App\Models\JamBelajar;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\SesiAbsensi;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class DashboardPagesTest extends TestCase
{
    use RefreshDatabase;

    private function actAsAdmin(): User
    {
        $admin = User::create([
            'username' => 'admin_test',
            'name' => 'Admin Test',
            'role' => 'admin',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($admin);

        return $admin;
    }

    public function test_site_root_redirects_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_page_loads_successfully(): void
    {
        $this->actAsAdmin();

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Dashboard Presensi Hari Ini');
        $response->assertSee('Jumlah Siswa');
        $response->assertSee('Sudah Presensi');
        $response->assertSee('Tepat Waktu');
        $response->assertSee('Terlambat');
        $response->assertSee('Ijin / Sakit');
        $response->assertSee('Alpha');
    }

    public function test_dashboard_displays_all_sessions_without_limit(): void
    {
        $this->actAsAdmin();

        // Buat 6 sesi seperti kondisi riil di database
        $sessionsData = [
            ['kode_sesi' => 's1', 'nama_sesi' => 'Sesi Kedatangan', 'jam_mulai' => '06:30:00', 'jam_terlambat' => '07:00:00', 'jam_selesai' => '07:30:00', 'is_aktif' => true],
            ['kode_sesi' => 's2', 'nama_sesi' => 'Sesi Dhuhur Putra', 'jam_mulai' => '11:30:00', 'jam_terlambat' => '12:00:00', 'jam_selesai' => '12:05:00', 'is_aktif' => true],
            ['kode_sesi' => 's3', 'nama_sesi' => 'Sesi Dhuhur Putri', 'jam_mulai' => '11:30:00', 'jam_terlambat' => '12:00:00', 'jam_selesai' => '12:05:00', 'is_aktif' => true],
            ['kode_sesi' => 's4', 'nama_sesi' => 'Sesi Ashar Putra', 'jam_mulai' => '14:30:00', 'jam_terlambat' => '15:00:00', 'jam_selesai' => '15:15:00', 'is_aktif' => true],
            ['kode_sesi' => 's5', 'nama_sesi' => 'Sesi Ashar Putri', 'jam_mulai' => '14:30:00', 'jam_terlambat' => '15:00:00', 'jam_selesai' => '15:15:00', 'is_aktif' => true],
            ['kode_sesi' => 's6', 'nama_sesi' => 'Sesi Pulang Sekolah', 'jam_mulai' => '15:30:00', 'jam_terlambat' => '15:45:00', 'jam_selesai' => '16:01:00', 'is_aktif' => true],
        ];

        foreach ($sessionsData as $data) {
            SesiAbsensi::create($data);
        }

        $allActiveSesi = SesiAbsensi::where('is_aktif', true)->get();
        $this->assertEquals(6, $allActiveSesi->count());

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);

        // Pastikan SELURUH 6 sesi aktif muncul di halaman, tidak terpotong take(4)
        foreach ($allActiveSesi as $s) {
            $response->assertSee($s->nama_sesi);
        }
    }

    public function test_sesi_aktif_switches_automatically_when_current_time_exceeds_session_end(): void
    {
        SesiAbsensi::create([
            'kode_sesi' => 'pagi',
            'nama_sesi' => 'Kedatangan',
            'jam_mulai' => '06:30:00',
            'jam_terlambat' => '07:00:00',
            'jam_selesai' => '07:30:00',
            'is_aktif' => true,
        ]);

        SesiAbsensi::create([
            'kode_sesi' => 'dhuhur',
            'nama_sesi' => 'Sholat Dhuhur',
            'jam_mulai' => '11:30:00',
            'jam_terlambat' => '12:00:00',
            'jam_selesai' => '12:05:00',
            'is_aktif' => true,
        ]);

        SesiAbsensi::create([
            'kode_sesi' => 'ashar',
            'nama_sesi' => 'Sholat Ashar',
            'jam_mulai' => '14:30:00',
            'jam_terlambat' => '15:00:00',
            'jam_selesai' => '15:15:00',
            'is_aktif' => true,
        ]);

        SesiAbsensi::create([
            'kode_sesi' => 'pulang',
            'nama_sesi' => 'Pulang',
            'jam_mulai' => '15:30:00',
            'jam_terlambat' => '15:45:00',
            'jam_selesai' => '16:01:00',
            'is_aktif' => true,
        ]);

        // 1. Waktu berada dalam rentang sesi Kedatangan (07:00:00) -> Kedatangan aktif
        $sesi1 = SesiAbsensi::getSesiAktif('07:00:00');
        $this->assertNotNull($sesi1);
        $this->assertEquals('Kedatangan', $sesi1->nama_sesi);

        // 2. Sesi Kedatangan sudah selesai (07:30) dan Dhuhur belum mulai (11:30),
        //    contoh 07:35:00 -> tidak ada sesi aktif (jeda antar sesi)
        $this->assertNull(SesiAbsensi::getSesiAktif('07:35:00'));
        $this->assertFalse(SesiAbsensi::adaSesiAktif('07:35:00'));

        // 3. Waktu tepat saat jam mulai sesi berikutnya (11:30:00) -> Dhuhur otomatis aktif
        $sesiMulaiDhuhur = SesiAbsensi::getSesiAktif('11:30:00');
        $this->assertNotNull($sesiMulaiDhuhur);
        $this->assertEquals('Sholat Dhuhur', $sesiMulaiDhuhur->nama_sesi);

        // 4. Waktu sedang berlangsung Dhuhur (11:45:00) -> Sholat Dhuhur aktif
        $sesi3 = SesiAbsensi::getSesiAktif('11:45:00');
        $this->assertNotNull($sesi3);
        $this->assertEquals('Sholat Dhuhur', $sesi3->nama_sesi);

        // 5. Waktu melebihi selesai Dhuhur (12:05) namun Ashar belum mulai (14:30),
        //    contoh 12:10:00 -> tidak ada sesi aktif
        $this->assertNull(SesiAbsensi::getSesiAktif('12:10:00'));

        // 6. Waktu melebihi selesai Ashar (15:15) dan Pulang belum mulai (15:30),
        //    contoh 15:20:00 -> tidak ada sesi aktif
        $this->assertNull(SesiAbsensi::getSesiAktif('15:20:00'));

        // 7. Waktu melebihi seluruh sesi hari ini (contoh 20:00:00) -> tidak ada sesi aktif,
        //    sesi berikutnya baru aktif otomatis keesokan hari saat jam mulainya tercapai
        $this->assertNull(SesiAbsensi::getSesiAktif('20:00:00'));
        $this->assertNull(SesiAbsensi::getSesiAktif('05:00:00'));
    }

    public function test_scanning_records_petugas_and_displays_on_dashboard_recent_activity(): void
    {
        $this->actAsAdmin();

        $guru = User::create([
            'name' => 'Budi Santoso, S.Pd.',
            'username' => 'guru_budi',
            'password' => Hash::make('password'),
            'role' => 'guru',
        ]);

        $siswa = Siswa::create([
            'nis' => '998877',
            'nisn' => '0099887766',
            'nama' => 'Ahmad Dahlan',
            'kelas' => 'XII IPA 1',
            'jenis_kelamin' => 'L',
        ]);

        $sesi = SesiAbsensi::create([
            'kode_sesi' => 'pagi-test',
            'nama_sesi' => 'Presensi Pagi',
            'jam_mulai' => '06:00:00',
            'jam_terlambat' => '07:00:00',
            'jam_selesai' => '08:00:00',
            'is_aktif' => true,
        ]);

        $absensi = Absensi::create([
            'nis' => $siswa->nis,
            'sesi_id' => $sesi->id,
            'user_id' => $guru->id,
            'tanggal' => now()->toDateString(),
            'jam_scan' => now()->toTimeString(),
            'status' => 'Tepat Waktu',
        ]);

        $this->assertDatabaseHas('absensi_siswas', [
            'id' => $absensi->id,
            'user_id' => $guru->id,
        ]);

        $this->assertEquals($guru->id, $absensi->petugas->id);
        $this->assertEquals('Budi Santoso, S.Pd.', $absensi->petugas->name);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Aktivitas Presensi Terbaru');
        $response->assertSee('Petugas Scan');
        $response->assertSee('Budi Santoso, S.Pd.');
    }

    public function test_saturday_is_recognized_as_holiday_in_five_day_school_week(): void
    {
        // 2026-10-31 adalah hari Sabtu
        $saturday = Carbon::parse('2026-10-31 07:00:00');
        $this->assertTrue(HariLibur::isHariLibur($saturday));

        $status = HariLibur::statusLibur($saturday);
        $this->assertTrue($status['is_libur']);
        $this->assertStringContainsString('Sabtu', $status['keterangan']);

        // Pastikan tidak ada sesi yang aktif pada hari Sabtu
        SesiAbsensi::create([
            'kode_sesi' => 'sesi-pagi-sabtu',
            'nama_sesi' => 'Presensi Pagi',
            'jam_mulai' => '06:30:00',
            'jam_terlambat' => '07:00:00',
            'jam_selesai' => '07:30:00',
            'is_aktif' => true,
        ]);

        $this->assertNull(SesiAbsensi::getSesiAktif('07:00:00', $saturday));
    }

    public function test_admin_can_manage_hari_libur(): void
    {
        $admin = User::create([
            'name' => 'Administrator MAN 2',
            'username' => 'admin_libur',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        // 1. Tambah Hari Libur
        $responseStore = $this->post(route('hari-libur.store'), [
            'tanggal' => '2026-11-25',
            'keterangan' => 'Hari Guru Nasional 2026',
        ]);

        $responseStore->assertRedirect();
        $this->assertDatabaseHas('hari_liburs', [
            'keterangan' => 'Hari Guru Nasional 2026',
        ]);
        $this->assertNotNull(HariLibur::whereDate('tanggal', '2026-11-25')->first());

        // 2. Cek halaman daftar hari libur
        $responseIndex = $this->get(route('hari-libur.index'));
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('Hari Guru Nasional 2026');
        $responseIndex->assertSee('25 November 2026');

        // 3. Cek widget hari libur di dashboard admin
        $responseDash = $this->get(route('dashboard'));
        $responseDash->assertStatus(200);
        $responseDash->assertSee('Pengaturan Hari Libur (5 Hari Belajar)');
        $responseDash->assertSee('Hari Guru Nasional 2026');

        // 4. Hapus Hari Libur
        $libur = HariLibur::whereDate('tanggal', '2026-11-25')->first();
        $responseDelete = $this->delete(route('hari-libur.destroy', $libur->id));
        $responseDelete->assertRedirect();
        $this->assertDatabaseMissing('hari_liburs', ['id' => $libur->id]);
    }

    public function test_admin_can_push_pulang_awal_and_auto_records_attendance(): void
    {
        $this->travelTo(Carbon::parse('2026-10-30 13:00:00')); // Jumat siang

        $admin = User::create([
            'name' => 'Kepala Madrasah',
            'username' => 'kepsek_man2',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $siswa = Siswa::create([
            'nis' => '112233',
            'nisn' => '0011223344',
            'nama' => 'Zahra Amelia',
            'kelas' => 'X B',
            'jenis_kelamin' => 'P',
        ]);

        $sesiPagi = SesiAbsensi::create([
            'kode_sesi' => 'pagi-jumat',
            'nama_sesi' => 'Presensi Masuk',
            'jam_mulai' => '06:30:00',
            'jam_terlambat' => '07:00:00',
            'jam_selesai' => '07:30:00',
            'is_aktif' => true,
        ]);

        $sesiPulang = SesiAbsensi::create([
            'kode_sesi' => 'pulang-jumat',
            'nama_sesi' => 'Sesi Pulang Reguler',
            'jam_mulai' => '15:30:00',
            'jam_terlambat' => '15:45:00',
            'jam_selesai' => '16:01:00',
            'is_aktif' => true,
        ]);

        // Siswa sudah hadir pagi
        Absensi::create([
            'nis' => $siswa->nis,
            'sesi_id' => $sesiPagi->id,
            'tanggal' => '2026-10-30',
            'jam_scan' => '06:45:00',
            'status' => 'Tepat Waktu',
        ]);

        $this->actingAs($admin);

        // Kirim Push Pulang Lebih Awal (misal pukul 13:00 karena Rapat Dinas)
        $responsePush = $this->post(route('dashboard.pulang-awal'), [
            'keterangan' => 'Rapat Dewan Guru & Dinas Kemenag',
            'jam_pulang' => '13:00',
            'sesi_id' => $sesiPulang->id,
            'catat_otomatis' => '1',
        ]);

        $responsePush->assertRedirect();

        // Pastikan absensi pulang siswa otomatis tercatat
        $this->assertDatabaseHas('absensi_siswas', [
            'nis' => $siswa->nis,
            'sesi_id' => $sesiPulang->id,
            'tanggal' => '2026-10-30',
            'status' => 'Tepat Waktu',
            'keterangan' => 'Dipulangkan lebih awal: Rapat Dewan Guru & Dinas Kemenag',
        ]);

        // Sesi pulang sekarang otomatis aktif mendahului jam reguler
        $activeSesi = SesiAbsensi::getSesiAktif('13:05:00', Carbon::parse('2026-10-30'));
        $this->assertNotNull($activeSesi);
        $this->assertEquals($sesiPulang->id, $activeSesi->id);

        // Banner peringatan pulang awal muncul di dashboard
        $responseDash = $this->get(route('dashboard'));
        $responseDash->assertStatus(200);
        $responseDash->assertSee('Instruksi Pulang Lebih Awal Sedang Aktif');
        $responseDash->assertSee('Rapat Dewan Guru &amp; Dinas Kemenag', false);

        // Batalkan mode pulang awal
        $responseBatal = $this->post(route('dashboard.batalkan-pulang-awal'));
        $responseBatal->assertRedirect();
        $this->assertNull(Cache::get('pulang_awal_2026-10-30'));
    }

    public function test_siswa_management_page_loads(): void
    {
        $this->actAsAdmin();

        $response = $this->get(route('siswa.index'));
        $response->assertStatus(200);
        $response->assertSee('Kelola Data Siswa');
        $response->assertSee('Input Siswa Manual');
        $response->assertSee('Import dari Excel');
    }

    public function test_siswa_template_download(): void
    {
        $this->actAsAdmin();

        $response = $this->get(route('siswa.template'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_siswa_export_download(): void
    {
        $this->actAsAdmin();

        $response = $this->get(route('siswa.export'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_qrcode_page_loads(): void
    {
        $this->actAsAdmin();

        Siswa::create([
            'nis' => '12345',
            'nisn' => '0012345',
            'nama' => 'Budi Test',
            'kelas' => 'X-A',
        ]);

        $response = $this->get(route('qrcode.index'));
        $response->assertStatus(200);
        $response->assertSee('Generate Kartu Absen Siswa');
        $response->assertSee('QR Code NISN');
    }

    public function test_qrcode_print_all_loads(): void
    {
        $this->actAsAdmin();

        $response = $this->get(route('qrcode.print-all'));
        $response->assertStatus(200);
        $response->assertSee('Generate Kartu Absen Siswa');
        $response->assertSee('Cetak Kartu (Ctrl + P)');
    }

    public function test_rekapitulasi_page_loads(): void
    {
        $this->actAsAdmin();

        Siswa::create([
            'nis' => '88881',
            'nisn' => '0088881',
            'nama' => 'Siswa Rekap A',
            'kelas' => 'X-A',
        ]);

        $response = $this->get(route('rekapitulasi.index'));
        $response->assertStatus(200);
        $response->assertSee('Rekapitulasi Presensi Siswa');
        $response->assertSee('Pilih Kelas');
        $response->assertSee('Diagram Batang Rekapitulasi Presensi per Kategori');
        $response->assertSee('JUMLAH TOTAL');
    }

    public function test_rekapitulasi_filter_bulan(): void
    {
        $this->actAsAdmin();

        Siswa::create([
            'nis' => '88885',
            'nisn' => '0088885',
            'nama' => 'Siswa Bulan',
            'kelas' => 'X-A',
        ]);

        $response = $this->get(route('rekapitulasi.index', [
            'periode' => 'bulan',
            'bulan' => 10,
            'tahun_bulan' => 2026,
            'kelas' => 'X-A',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Oktober 2026');
        $response->assertSee('Kelas X-A');
        $response->assertSee('Siswa Bulan');
    }

    public function test_rekapitulasi_filter_semester_1(): void
    {
        $this->actAsAdmin();

        Siswa::create([
            'nis' => '88886',
            'nisn' => '0088886',
            'nama' => 'Siswa Sem1',
            'kelas' => 'XI-IPA',
        ]);

        $response = $this->get(route('rekapitulasi.index', [
            'periode' => 'semester',
            'semester' => '1',
            'tahun_semester' => 2026,
            'kelas' => 'XI-IPA',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Semester 1');
        $response->assertSee('Juli - Des');
        $response->assertSee('Kelas XI-IPA');
        $response->assertSee('Siswa Sem1');
    }

    public function test_rekapitulasi_filter_semester_2(): void
    {
        $this->actAsAdmin();

        Siswa::create([
            'nis' => '88887',
            'nisn' => '0088887',
            'nama' => 'Siswa Sem2',
            'kelas' => 'XII-IPS',
        ]);

        $response = $this->get(route('rekapitulasi.index', [
            'periode' => 'semester',
            'semester' => '2',
            'tahun_semester' => 2026,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Semester 2');
        $response->assertSee('Jan - Juli');
    }

    public function test_rekapitulasi_page_with_selected_class(): void
    {
        $this->actAsAdmin();

        Siswa::create([
            'nis' => '88882',
            'nisn' => '0088882',
            'nama' => 'Siswa Kelas X-B',
            'kelas' => 'X-B',
        ]);

        $response = $this->get(route('rekapitulasi.index', ['kelas' => 'X-B']));
        $response->assertStatus(200);
        $response->assertSee('Detail Presensi Siswa');
        $response->assertSee('Kelas X-B');
        $response->assertSee('Siswa Kelas X-B');
    }

    public function test_rekapitulasi_page_with_selected_kategori(): void
    {
        $this->actAsAdmin();

        $response = $this->get(route('rekapitulasi.index', ['kategori' => 'Terlambat']));
        $response->assertStatus(200);
        $response->assertSee('Tepat Waktu');
        $response->assertSee('Terlambat');
    }

    public function test_rekapitulasi_export_download(): void
    {
        $this->actAsAdmin();

        $response = $this->get(route('rekapitulasi.export'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_pengaturan_waktu_page_loads(): void
    {
        $this->actAsAdmin();

        $response = $this->get(route('pengaturan-waktu.index'));
        $response->assertStatus(200);
        $response->assertSee('Pengaturan Waktu Presensi');
        $response->assertSee('Daftar Sesi Absensi');
    }

    public function test_pengaturan_akun_page_loads(): void
    {
        $this->actAsAdmin();

        $response = $this->get(route('pengaturan-akun.index'));
        $response->assertStatus(200);
        $response->assertSee('Pengaturan Akun Pengguna');
        $response->assertSee('Total Pengguna');
        $response->assertSee('Tambah Akun Baru');
        $response->assertSee('Ekspor PDF');
        $response->assertSee('Ekspor Excel');
    }

    public function test_can_manage_user_accounts_crud(): void
    {
        $this->actAsAdmin();

        // 1. Create User
        $response = $this->post(route('pengaturan-akun.store'), [
            'username' => 'guru_pagi',
            'password' => 'secret123',
            'role' => 'guru',
            'no_hp' => '08123456789',
        ]);
        $response->assertRedirect(route('pengaturan-akun.index'));
        $this->assertDatabaseHas('users', [
            'username' => 'guru_pagi',
            'role' => 'guru',
            'no_hp' => '08123456789',
        ]);

        $user = User::where('username', 'guru_pagi')->first();

        // 2. Update User
        $updateResponse = $this->put(route('pengaturan-akun.update', $user->id), [
            'username' => 'guru_siang',
            'role' => 'wali_kelas',
            'no_hp' => '08987654321',
        ]);
        $updateResponse->assertRedirect(route('pengaturan-akun.index'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => 'guru_siang',
            'role' => 'wali_kelas',
            'no_hp' => '08987654321',
        ]);

        // 3. Delete User
        $deleteResponse = $this->delete(route('pengaturan-akun.destroy', $user->id));
        $deleteResponse->assertRedirect(route('pengaturan-akun.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_pengaturan_akun_export_excel(): void
    {
        $this->actAsAdmin();

        $response = $this->get(route('pengaturan-akun.export.excel'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_pengaturan_akun_export_pdf(): void
    {
        $this->actAsAdmin();

        $response = $this->get(route('pengaturan-akun.export.pdf'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_scanner_page_loads(): void
    {
        $this->actAsAdmin();

        $response = $this->get(route('scanner'));
        $response->assertStatus(200);
        $response->assertSee('Scanner Absensi Siswa');
    }

    public function test_can_create_update_and_delete_siswa(): void
    {
        $this->actAsAdmin();

        // 1. Create Siswa
        $response = $this->post(route('siswa.store'), [
            'nis' => '9999001',
            'nisn' => '009999001',
            'nama' => 'Santoso Pratama',
            'kelas' => 'X-A',
            'no_hp_ortu' => '08123456789',
        ]);
        $response->assertRedirect(route('siswa.index'));
        $this->assertDatabaseHas('siswas', ['nis' => '9999001', 'nama' => 'Santoso Pratama']);

        $siswa = Siswa::where('nis', '9999001')->first();

        // 2. Update Siswa
        $updateResponse = $this->put(route('siswa.update', $siswa->id), [
            'nis' => '9999001',
            'nisn' => '009999001',
            'nama' => 'Santoso Pratama Edit',
            'kelas' => 'X-B',
            'no_hp_ortu' => '08987654321',
        ]);
        $updateResponse->assertRedirect(route('siswa.index'));
        $this->assertDatabaseHas('siswas', ['nis' => '9999001', 'nama' => 'Santoso Pratama Edit', 'kelas' => 'X-B']);

        // 3. Delete Siswa
        $deleteResponse = $this->delete(route('siswa.destroy', $siswa->id));
        $deleteResponse->assertRedirect(route('siswa.index'));
        $this->assertDatabaseMissing('siswas', ['nis' => '9999001']);
    }

    public function test_can_manage_sesi_absensi(): void
    {
        $this->actAsAdmin();

        // 1. Create Sesi
        $response = $this->post(route('pengaturan-waktu.store'), [
            'kode_sesi' => 'sesi-sore',
            'nama_sesi' => 'Kepulangan Sore',
            'jam_mulai' => '15:00',
            'jam_terlambat' => '15:30',
            'jam_selesai' => '16:00',
            'is_aktif' => '1',
        ]);
        $response->assertRedirect(route('pengaturan-waktu.index'));
        $this->assertDatabaseHas('sesi_absensis', ['kode_sesi' => 'sesi-sore']);

        $sesi = SesiAbsensi::where('kode_sesi', 'sesi-sore')->first();

        // 2. Toggle status
        $this->post(route('pengaturan-waktu.toggle', $sesi->id));
        $this->assertDatabaseHas('sesi_absensis', ['id' => $sesi->id, 'is_aktif' => false]);

        // 3. Update Sesi
        $this->put(route('pengaturan-waktu.update', $sesi->id), [
            'kode_sesi' => 'sesi-sore',
            'nama_sesi' => 'Kepulangan Sore Baru',
            'jam_mulai' => '15:15',
            'jam_terlambat' => '15:45',
            'jam_selesai' => '16:15',
            'is_aktif' => '1',
        ]);
        $this->assertDatabaseHas('sesi_absensis', ['id' => $sesi->id, 'nama_sesi' => 'Kepulangan Sore Baru']);

        // 4. Delete Sesi
        $this->delete(route('pengaturan-waktu.destroy', $sesi->id));
        $this->assertDatabaseMissing('sesi_absensis', ['id' => $sesi->id]);
    }

    public function test_can_update_admin_account(): void
    {
        $user = User::create([
            'username' => 'original_admin',
            'role' => 'admin',
            'no_hp' => '08111111111',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($user);

        $response = $this->put(route('pengaturan-akun.update', $user->id), [
            'username' => 'super_admin_updated',
            'role' => 'admin',
            'no_hp' => '08222222222',
        ]);

        $response->assertRedirect(route('pengaturan-akun.index'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => 'super_admin_updated',
            'no_hp' => '08222222222',
        ]);
    }

    public function test_login_page_renders_with_clear_look_and_role_selection(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('MAN 2 BANDAR LAMPUNG');
        $response->assertSee('images/logo2.png');
        $response->assertSee('images/logo1.png');
        $response->assertSee('Pilih Peran Anda');
        $response->assertSee('Siswa');
        $response->assertSee('Guru');
        $response->assertSee('Admin');
        $response->assertDontSee('selectRole(\'wali_kelas\'', false);
    }

    public function test_siswa_can_login_using_nis_and_redirects_to_siswa_dashboard(): void
    {
        $siswa = Siswa::create([
            'nis' => '10001',
            'nisn' => '0010001001',
            'nama' => 'Ahmad Faiz',
            'kelas' => 'X-A',
            'no_hp_ortu' => '081234567890',
        ]);

        $response = $this->post(route('login.post'), [
            'role' => 'siswa',
            'username' => '10001',
            'password' => 'siswa123',
        ]);

        $response->assertRedirect(route('siswa.dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'siswa_id' => $siswa->id,
            'role' => 'siswa',
            'username' => '10001',
        ]);
    }

    public function test_siswa_dashboard_displays_monthly_and_semester_recap_and_qr_card(): void
    {
        $siswa = Siswa::create([
            'nis' => '10002',
            'nisn' => '0010002002',
            'nama' => 'Fatimah Az-Zahra',
            'kelas' => 'XI-IPA-1',
            'no_hp_ortu' => '081298765432',
        ]);

        $user = User::create([
            'siswa_id' => $siswa->id,
            'username' => $siswa->nis,
            'name' => $siswa->nama,
            'role' => 'siswa',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('siswa.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('MAN 2 BANDAR LAMPUNG');
        $response->assertSee('Fatimah Az-Zahra');
        $response->assertSee('Bulan Berjalan');
        $response->assertSee('Akumulasi Semester');
        $response->assertSee('Mode Scan Layar HP');
        $response->assertSee('0010002002'); // QR Code = NISN
    }

    public function test_siswa_can_upload_photo_into_foto_siswa_folder(): void
    {
        $siswa = Siswa::create([
            'nis' => '10003',
            'nisn' => '0010003003',
            'nama' => 'Rahmat Hidayat',
            'kelas' => 'XII-IPS-2',
            'no_hp_ortu' => '081233344455',
        ]);

        $user = User::create([
            'siswa_id' => $siswa->id,
            'username' => $siswa->nis,
            'name' => $siswa->nama,
            'role' => 'siswa',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($user);

        $fakePhoto = UploadedFile::fake()->image('foto_formal.jpg', 300, 400);

        $response = $this->post(route('siswa.upload-foto'), [
            'foto' => $fakePhoto,
        ]);

        $response->assertSessionHas('success');
        $siswa->refresh();

        $this->assertNotNull($siswa->foto);
        $this->assertStringStartsWith('FotoSiswa/', $siswa->foto);
        $this->assertFileExists(public_path($siswa->foto));

        // Clean up created test photo file
        if (file_exists(public_path($siswa->foto))) {
            @unlink(public_path($siswa->foto));
        }
        $storagePath = storage_path('app/public/'.$siswa->foto);
        if (file_exists($storagePath)) {
            @unlink($storagePath);
        }
    }

    public function test_guru_dashboard_loads_and_has_scanner_and_izin_sakit(): void
    {
        $guruUser = User::create([
            'username' => 'guru_matematika',
            'name' => 'Bambang Guru',
            'role' => 'guru',
            'password' => bcrypt('guru123'),
        ]);

        $this->actingAs($guruUser);

        // Accessing guru dashboard renders view
        $responseGuru = $this->get(route('guru.dashboard'));
        $responseGuru->assertStatus(200);
        $responseGuru->assertSee('Dashboard Guru &amp; Presensi', false);
        $responseGuru->assertSee('Buka Scanner Kamera');
        $responseGuru->assertSee('Catat Izin / Sakit');
        $responseGuru->assertDontSee('Dashboard Wali Kelas');

        // Accessing scanner also works for guru
        $scannerResponse = $this->get(route('scanner'));
        $scannerResponse->assertStatus(200);
        $scannerResponse->assertSee('Guru: Bambang Guru');
    }

    public function test_guru_dashboard_displays_active_absensi_session_schedule(): void
    {
        $guru = User::create([
            'username' => 'guru_jadwal',
            'name' => 'Guru Jadwal',
            'role' => 'guru',
            'password' => bcrypt('guru123'),
        ]);

        SesiAbsensi::create([
            'kode_sesi' => 'kedatangan',
            'nama_sesi' => 'Sesi Kedatangan',
            'jam_mulai' => '06:30:00',
            'jam_terlambat' => '07:00:00',
            'jam_selesai' => '07:30:00',
            'is_aktif' => true,
        ]);

        SesiAbsensi::create([
            'kode_sesi' => 'nonaktif',
            'nama_sesi' => 'Sesi Nonaktif',
            'jam_mulai' => '08:00:00',
            'jam_terlambat' => '08:15:00',
            'jam_selesai' => '08:30:00',
            'is_aktif' => false,
        ]);

        $response = $this->actingAs($guru)->get(route('guru.dashboard'));

        $response->assertOk()
            ->assertSee('Jadwal Sesi Absensi')
            ->assertSee('Sesi Kedatangan')
            ->assertSee('06:30')
            ->assertSee('07:30')
            ->assertSee('Batas tepat waktu 07:00')
            ->assertDontSee('Sesi Nonaktif')
            ->assertDontSee('Semua Rombongan Belajar');
    }

    public function test_guru_login_redirects_to_guru_dashboard(): void
    {
        User::create([
            'username' => 'guru_piket',
            'role' => 'guru',
            'password' => bcrypt('guru123'),
        ]);

        $response = $this->post(route('login.post'), [
            'role' => 'guru',
            'username' => 'guru_piket',
            'password' => 'guru123',
        ]);

        $response->assertRedirect(route('guru.dashboard'));
    }

    public function test_wali_kelas_dashboard_displays_monthly_and_semester_recaps(): void
    {
        $siswa = Siswa::create([
            'nis' => '888801',
            'nisn' => '00888801',
            'nama' => 'Bintang Kelas',
            'kelas' => 'X-A',
            'no_hp_ortu' => '081234567890',
        ]);

        $waliUser = User::create([
            'username' => 'walikelas_xa',
            'name' => 'Siti Wali Kelas',
            'role' => 'wali_kelas',
            'password' => bcrypt('wali123'),
        ]);

        $this->actingAs($waliUser);

        // Test Rekap Bulan
        $responseBulan = $this->get(route('walikelas.dashboard', ['kelas' => 'X-A', 'mode' => 'bulan']));
        $responseBulan->assertStatus(200);
        $responseBulan->assertSee('Rekap Bulan');
        $responseBulan->assertSee('Bintang Kelas');
        $responseBulan->assertSee('Kelas Binaan:');

        // Test Rekap Semester
        $responseSemester = $this->get(route('walikelas.dashboard', ['kelas' => 'X-A', 'mode' => 'semester']));
        $responseSemester->assertStatus(200);
        $responseSemester->assertSee('Rekap Semester');
        $responseSemester->assertSee('Akumulasi Satu Semester');
        $responseSemester->assertSee('Bintang Kelas');
    }

    public function test_user_can_logout_and_redirects_to_login(): void
    {
        $user = User::create([
            'username' => 'user_logout',
            'role' => 'admin',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post(route('logout'));
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_can_manage_kelas_crud(): void
    {
        $this->actAsAdmin();

        // 1. Create Kelas
        $storeResponse = $this->post(route('kelas.store'), [
            'tahun_pelajaran' => '2026/2027',
            'kelas' => 'XII-MIPA-1',
            'wali_kelas' => 'Dr. H. Ahmad Fauzan, M.Pd.',
        ]);
        $storeResponse->assertRedirect(route('kelas.index'));
        $this->assertDatabaseHas('kelases', [
            'tahun_pelajaran' => '2026/2027',
            'kelas' => 'XII-MIPA-1',
            'wali_kelas' => 'Dr. H. Ahmad Fauzan, M.Pd.',
        ]);

        $kelas = Kelas::where('kelas', 'XII-MIPA-1')->first();

        // 2. View Index
        $indexResponse = $this->get(route('kelas.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('XII-MIPA-1');
        $indexResponse->assertSee('Dr. H. Ahmad Fauzan, M.Pd.');
        $indexResponse->assertSee('modalEditKelas'.$kelas->id);
        $indexResponse->assertSee('modalHapusKelas'.$kelas->id);

        // 3. Update Kelas
        $updateResponse = $this->put(route('kelas.update', $kelas->id), [
            'tahun_pelajaran' => '2026/2027',
            'kelas' => 'XII-MIPA-1-Unggulan',
            'wali_kelas' => 'Dr. H. Ahmad Fauzan, M.Pd. Ed.',
        ]);
        $updateResponse->assertRedirect(route('kelas.index'));
        $this->assertDatabaseHas('kelases', [
            'id' => $kelas->id,
            'kelas' => 'XII-MIPA-1-Unggulan',
        ]);

        // 4. Delete Kelas
        $deleteResponse = $this->delete(route('kelas.destroy', $kelas->id));
        $deleteResponse->assertRedirect(route('kelas.index'));
        $this->assertDatabaseMissing('kelases', ['id' => $kelas->id]);
    }

    public function test_pengaturan_aplikasi_page_loads_successfully(): void
    {
        $this->actAsAdmin();

        $response = $this->get(route('pengaturan-aplikasi.index'));
        $response->assertStatus(200);
        $response->assertSee('Pengaturan & Pemeliharaan Aplikasi');
        $response->assertSee('Unduh Cadangan / Arsip Presensi');
        $response->assertSee('Kosongkan Data Presensi');
    }

    public function test_pengaturan_aplikasi_export_arsip_downloads_excel(): void
    {
        $this->actAsAdmin();

        $response = $this->get(route('pengaturan-aplikasi.export.arsip'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_pengaturan_aplikasi_can_reset_presensi(): void
    {
        $this->actAsAdmin();

        $sesi = SesiAbsensi::create([
            'kode_sesi' => 'pagi-test',
            'nama_sesi' => 'Presensi Pagi',
            'jam_mulai' => '06:00',
            'jam_terlambat' => '07:00',
            'jam_selesai' => '08:00',
            'is_aktif' => true,
        ]);

        Siswa::create([
            'nis' => '10001',
            'nisn' => '0010001',
            'nama' => 'Siswa Test 1',
            'kelas' => 'X-A',
        ]);
        Siswa::create([
            'nis' => '10002',
            'nisn' => '0010002',
            'nama' => 'Siswa Test 2',
            'kelas' => 'X-A',
        ]);

        // Create sample attendance records
        Absensi::create([
            'sesi_id' => $sesi->id,
            'nis' => '10001',
            'tanggal' => '2026-07-15',
            'jam_scan' => '06:45:00',
            'status' => 'Tepat Waktu',
        ]);
        Absensi::create([
            'sesi_id' => $sesi->id,
            'nis' => '10002',
            'tanggal' => '2026-07-16',
            'jam_scan' => '07:15:00',
            'status' => 'Terlambat',
        ]);

        $this->assertEquals(2, Absensi::count());

        // Attempt reset with invalid confirmation keyword should fail
        $failResponse = $this->post(route('pengaturan-aplikasi.reset'), [
            'konfirmasi' => 'SALAH',
            'lingkup' => 'semua',
        ]);
        $failResponse->assertSessionHasErrors('konfirmasi');
        $this->assertEquals(2, Absensi::count());

        // Valid reset with confirmation keyword KOSONGKAN
        $resetResponse = $this->post(route('pengaturan-aplikasi.reset'), [
            'konfirmasi' => 'KOSONGKAN',
            'lingkup' => 'semua',
        ]);
        $resetResponse->assertRedirect(route('pengaturan-aplikasi.index'));
        $resetResponse->assertSessionHas('success');
        $this->assertEquals(0, Absensi::count());
    }

    public function test_favicon_and_app_title_rendered_across_pages(): void
    {
        // 1. Login page
        $loginRes = $this->get(route('login'));
        $loginRes->assertStatus(200);
        $loginRes->assertSee('images/logo1.png');
        $loginRes->assertSee('e-absen MAN 2 Balam');

        // 2. Dashboard admin layout
        $this->actAsAdmin();
        $dashRes = $this->get(route('dashboard'));
        $dashRes->assertStatus(200);
        $dashRes->assertSee('images/logo1.png');
        $dashRes->assertSee('e-absen MAN 2 Balam');

        // 3. Scanner layout
        $scannerRes = $this->get(route('scanner'));
        $scannerRes->assertStatus(200);
        $scannerRes->assertSee('images/logo1.png');
        $scannerRes->assertSee('e-absen MAN 2 Balam');
    }

    public function test_siswa_can_login_using_nisn_and_default_password_nisn(): void
    {
        $siswa = Siswa::create([
            'nis' => '99001',
            'nisn' => '0099001001',
            'nama' => 'Siswa Akun Test',
            'kelas' => 'XII-MIPA-1',
        ]);

        $response = $this->post(route('login.post'), [
            'role' => 'siswa',
            'username' => '0099001001',
            'password' => '0099001001',
        ]);

        $response->assertRedirect(route('siswa.dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'siswa_id' => $siswa->id,
            'role' => 'siswa',
            'username' => '0099001001',
        ]);
    }

    public function test_siswa_can_change_password_and_login_with_new_password(): void
    {
        $siswa = Siswa::create([
            'nis' => '99002',
            'nisn' => '0099002002',
            'nama' => 'Siswa Ganti Sandi',
            'kelas' => 'XII-MIPA-2',
        ]);

        // Login first with NISN
        $this->post(route('login.post'), [
            'role' => 'siswa',
            'username' => '0099002002',
            'password' => '0099002002',
        ]);

        $this->assertAuthenticated();

        // Change password
        $changeResponse = $this->post(route('siswa.ganti-password'), [
            'password_lama' => '0099002002',
            'password_baru' => 'sandiBaru2026',
            'password_baru_confirmation' => 'sandiBaru2026',
        ]);

        $changeResponse->assertSessionHas('success');

        // Logout
        $this->post(route('logout'));
        $this->assertGuest();

        // Old NISN password should now fail
        $failResponse = $this->post(route('login.post'), [
            'role' => 'siswa',
            'username' => '0099002002',
            'password' => '0099002002',
        ]);
        $failResponse->assertSessionHasErrors('password');
        $this->assertGuest();

        // New password should succeed
        $loginNew = $this->post(route('login.post'), [
            'role' => 'siswa',
            'username' => '0099002002',
            'password' => 'sandiBaru2026',
        ]);
        $loginNew->assertRedirect(route('siswa.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_admin_can_view_siswa_accounts_tab_in_pengaturan_akun(): void
    {
        $this->actAsAdmin();

        Siswa::create([
            'nis' => '99003',
            'nisn' => '0099003003',
            'nama' => 'Siswa Di Tab Admin',
            'kelas' => 'X-A',
        ]);

        $response = $this->get(route('pengaturan-akun.index', ['tab' => 'siswa']));
        $response->assertStatus(200);
        $response->assertSee('Daftar Akun Siswa (Login via NISN)');
        $response->assertSee('0099003003');
        $response->assertSee('Siswa Di Tab Admin');
        $response->assertSee('Reset ke NISN');
    }

    public function test_admin_can_reset_siswa_password_back_to_nisn(): void
    {
        $siswa = Siswa::create([
            'nis' => '99004',
            'nisn' => '0099004004',
            'nama' => 'Siswa Reset Password',
            'kelas' => 'X-B',
        ]);

        // Student sets custom password
        $this->post(route('login.post'), [
            'role' => 'siswa',
            'username' => '0099004004',
            'password' => '0099004004',
        ]);
        $this->post(route('siswa.ganti-password'), [
            'password_lama' => '0099004004',
            'password_baru' => 'kustomSandi999',
            'password_baru_confirmation' => 'kustomSandi999',
        ]);
        $this->post(route('logout'));

        // Admin resets password back to NISN
        $this->actAsAdmin();
        $resetResponse = $this->post(route('pengaturan-akun.siswa.reset-password', $siswa->id));
        $resetResponse->assertRedirect(route('pengaturan-akun.index', ['tab' => 'siswa']));
        $resetResponse->assertSessionHas('success');

        // Student can now log in using NISN again
        $reLogin = $this->post(route('login.post'), [
            'role' => 'siswa',
            'username' => '0099004004',
            'password' => '0099004004',
        ]);
        $reLogin->assertRedirect(route('siswa.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_admin_can_bulk_reset_siswa_passwords_by_class(): void
    {
        $this->actAsAdmin();

        $siswa1 = Siswa::create([
            'nis' => '99005',
            'nisn' => '0099005005',
            'nama' => 'Siswa Kelas Reset 1',
            'kelas' => 'X-C',
        ]);
        $siswa2 = Siswa::create([
            'nis' => '99006',
            'nisn' => '0099006006',
            'nama' => 'Siswa Kelas Reset 2',
            'kelas' => 'X-C',
        ]);

        $response = $this->post(route('pengaturan-akun.siswa.reset-kelas'), [
            'kelas' => 'X-C',
        ]);

        $response->assertRedirect(route('pengaturan-akun.index', ['tab' => 'siswa', 'kelas_siswa' => 'X-C']));
        $response->assertSessionHas('success');

        // Both students can log in with their respective NISN
        $this->post(route('login.post'), [
            'role' => 'siswa',
            'username' => '0099005005',
            'password' => '0099005005',
        ])->assertRedirect(route('siswa.dashboard'));

        $this->post(route('logout'));

        $this->post(route('login.post'), [
            'role' => 'siswa',
            'username' => '0099006006',
            'password' => '0099006006',
        ])->assertRedirect(route('siswa.dashboard'));
    }

    public function test_pengaturan_akun_export_siswa_excel_and_pdf(): void
    {
        $this->actAsAdmin();

        Siswa::create([
            'nis' => '99007',
            'nisn' => '0099007007',
            'nama' => 'Siswa Export Test',
            'kelas' => 'X-D',
        ]);

        // 1. Excel export for siswa
        $excelRes = $this->get(route('pengaturan-akun.export.excel', ['tab' => 'siswa']));
        $excelRes->assertStatus(200);
        $excelRes->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // 2. PDF export for siswa
        $pdfRes = $this->get(route('pengaturan-akun.export.pdf', ['tab' => 'siswa']));
        $pdfRes->assertStatus(200);
        $pdfRes->assertHeader('content-type', 'application/pdf');
    }

    public function test_pengaturan_akun_pegawai_template_download(): void
    {
        $this->actAsAdmin();

        $response = $this->get(route('pengaturan-akun.template'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_pengaturan_akun_pegawai_import_excel_with_multi_roles(): void
    {
        $this->actAsAdmin();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'USERNAME / NIP');
        $sheet->setCellValue('B1', 'NAMA LENGKAP');
        $sheet->setCellValue('C1', 'ROLE');
        $sheet->setCellValue('D1', 'NO HP / WHATSAPP');
        $sheet->setCellValue('E1', 'PASSWORD');

        $sheet->setCellValue('A2', '198001012005011001');
        $sheet->setCellValue('B2', 'Drs. H. Ahmad Fauzi, M.Pd.');
        $sheet->setCellValue('C2', 'guru,wali_kelas');
        $sheet->setCellValue('D2', '081234567890');
        $sheet->setCellValue('E2', 'fauzi123');

        $sheet->setCellValue('A3', '198502022010012002');
        $sheet->setCellValue('B3', 'Siti Rahmawati, S.Pd.');
        $sheet->setCellValue('C3', 'guru');
        $sheet->setCellValue('D3', '081234567891');
        $sheet->setCellValue('E3', 'siti123');

        $tempPath = tempnam(sys_get_temp_dir(), 'import_pegawai_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $file = new UploadedFile(
            $tempPath,
            'Template_Import_Pegawai.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->post(route('pengaturan-akun.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('pengaturan-akun.index', ['tab' => 'pegawai']));
        $response->assertSessionHas('success');

        @unlink($tempPath);

        $userFauzi = User::where('username', '198001012005011001')->first();
        $this->assertNotNull($userFauzi);
        $this->assertEquals('Drs. H. Ahmad Fauzi, M.Pd.', $userFauzi->name);
        $this->assertTrue($userFauzi->hasRole('guru'));
        $this->assertTrue($userFauzi->hasRole('wali_kelas'));
        $this->assertEquals(['guru', 'wali_kelas'], $userFauzi->roles);

        $userSiti = User::where('username', '198502022010012002')->first();
        $this->assertNotNull($userSiti);
        $this->assertTrue($userSiti->hasRole('guru'));
        $this->assertFalse($userSiti->hasRole('wali_kelas'));
    }

    public function test_multi_role_user_can_login_via_both_roles(): void
    {
        $user = User::create([
            'username' => 'guru_wali_test',
            'name' => 'Guru Wali Kelas Test',
            'role' => 'guru,wali_kelas',
            'password' => Hash::make('password123'),
        ]);

        // 1. Login as Guru
        $resGuru = $this->post(route('login.post'), [
            'role' => 'guru',
            'username' => 'guru_wali_test',
            'password' => 'password123',
        ]);
        $resGuru->assertRedirect(route('guru.dashboard'));
        $this->assertAuthenticatedAs($user);

        $dashboardGuru = $this->get(route('guru.dashboard'));
        $dashboardGuru->assertOk()
            ->assertSee('Dashboard Guru')
            ->assertSee('Dashboard Wali Kelas');

        $this->post(route('logout'));
        $this->assertGuest();

        // 2. Login as Wali Kelas
        $resWali = $this->post(route('login.post'), [
            'role' => 'wali_kelas',
            'username' => 'guru_wali_test',
            'password' => 'password123',
        ]);
        $resWali->assertRedirect(route('walikelas.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_dashboard_layout_does_not_contain_scanner_menu(): void
    {
        $this->actAsAdmin();

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertDontSee('Scanner Presensi');
        $response->assertDontSee('Buka Scanner Kamera');
        $response->assertDontSee('Scanner Cepat');
    }

    public function test_pengaturan_akun_store_and_update_with_multiple_roles(): void
    {
        $this->actAsAdmin();

        // 1. Store with multiple roles and name
        $responseStore = $this->post(route('pengaturan-akun.store'), [
            'username' => 'multi_role_user',
            'name' => 'Drs. Ahmad Multi, M.Pd.',
            'password' => 'secret123',
            'roles' => ['guru', 'wali_kelas'],
            'no_hp' => '089988776655',
        ]);

        $responseStore->assertRedirect(route('pengaturan-akun.index'));
        $user = User::where('username', 'multi_role_user')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Drs. Ahmad Multi, M.Pd.', $user->name);
        $this->assertTrue($user->hasRole('guru'));
        $this->assertTrue($user->hasRole('wali_kelas'));
        $this->assertFalse($user->hasRole('admin'));

        // 2. Update to new roles and updated name
        $responseUpdate = $this->put(route('pengaturan-akun.update', $user->id), [
            'username' => 'multi_role_user',
            'name' => 'Dr. Ahmad Multi, M.Pd.',
            'roles' => ['guru', 'kepsek'],
        ]);

        $responseUpdate->assertRedirect(route('pengaturan-akun.index'));
        $user->refresh();
        $this->assertEquals('Dr. Ahmad Multi, M.Pd.', $user->name);
        $this->assertTrue($user->hasRole('guru'));
        $this->assertTrue($user->hasRole('kepsek'));
        $this->assertFalse($user->hasRole('wali_kelas'));
    }

    public function test_pengaturan_akun_index_displays_nama_column_and_supports_various_multi_role_formats(): void
    {
        $this->actAsAdmin();

        // Verify parseRolesString handles all variations from human Excel inputs
        $this->assertEquals('guru,wali_kelas', PengaturanAkunController::parseRolesString('guru,wali_kelas'));
        $this->assertEquals('guru,wali_kelas', PengaturanAkunController::parseRolesString('guru, wali kelas'));
        $this->assertEquals('guru,wali_kelas', PengaturanAkunController::parseRolesString('Guru / Wali Kelas'));
        $this->assertEquals('guru,wali_kelas', PengaturanAkunController::parseRolesString('guru, walikelas'));
        $this->assertEquals('guru,wali_kelas', PengaturanAkunController::parseRolesString('Guru & Wali Kelas'));
        $this->assertEquals('guru,wali_kelas', PengaturanAkunController::parseRolesString('Guru dan Wali Kelas'));

        $user = User::create([
            'username' => 'guru_wali_lengkap',
            'name' => 'Hj. Siti Lengkap, S.Pd.',
            'role' => 'guru,wali_kelas',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->get(route('pengaturan-akun.index'));
        $response->assertStatus(200);
        $response->assertSee('Username / NIP');
        $response->assertSee('Nama Lengkap');
        $response->assertSee('guru_wali_lengkap');
        $response->assertSee('Hj. Siti Lengkap, S.Pd.');
        $response->assertSee('Guru');
        $response->assertSee('Wali Kelas');
    }

    public function test_guru_dashboard_can_store_update_and_delete_izin_sakit(): void
    {
        $guruUser = User::create([
            'username' => 'guru_pengajar',
            'name' => 'Guru Pengajar',
            'role' => 'guru',
            'password' => bcrypt('password123'),
        ]);

        $siswa = Siswa::create([
            'nis' => '112233',
            'nisn' => '00112233',
            'nama' => 'Ahmad Pelajar',
            'kelas' => 'X-IPA-1',
            'no_hp_ortu' => '081234567890',
        ]);

        $this->actingAs($guruUser);

        // 1. Store Izin
        $resStore = $this->post(route('guru.izin-sakit.store'), [
            'nis' => '112233',
            'tanggal' => now()->toDateString(),
            'status' => 'Izin',
            'keterangan' => 'Mengikuti lomba olimpiade matematika',
        ]);

        $resStore->assertRedirect();
        $this->assertDatabaseHas('absensi_siswas', [
            'nis' => '112233',
            'status' => 'Izin',
            'keterangan' => 'Mengikuti lomba olimpiade matematika',
        ]);

        $absensi = Absensi::where('nis', '112233')->first();
        $this->assertNotNull($absensi);

        // 2. Update to Sakit
        $resUpdate = $this->put(route('guru.izin-sakit.update', $absensi->id), [
            'status' => 'Sakit',
            'keterangan' => 'Surat dokter: demam tinggi',
        ]);

        $resUpdate->assertRedirect();
        $this->assertDatabaseHas('absensi_siswas', [
            'id' => $absensi->id,
            'status' => 'Sakit',
            'keterangan' => 'Surat dokter: demam tinggi',
        ]);

        // 3. Delete record
        $resDelete = $this->delete(route('guru.izin-sakit.destroy', $absensi->id));
        $resDelete->assertRedirect();
        $this->assertDatabaseMissing('absensi_siswas', [
            'id' => $absensi->id,
        ]);
    }

    public function test_logout_buttons_are_prominently_visible_across_all_roles(): void
    {
        // 1. Role Admin
        $admin = User::create([
            'username' => 'test_admin_logout',
            'name' => 'Admin Utama',
            'role' => 'admin',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($admin);
        $resAdmin = $this->get(route('dashboard'));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee(route('logout'));
        $resAdmin->assertSee('Keluar (Logout)');
        $resAdmin->assertSee('Keluar');

        // 2. Role Guru
        $guru = User::create([
            'username' => 'test_guru_logout',
            'name' => 'Guru Pengajar',
            'role' => 'guru',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($guru);
        $resGuru = $this->get(route('guru.dashboard'));
        $resGuru->assertStatus(200);
        $resGuru->assertSee(route('logout'));
        $resGuru->assertSee('Keluar (Logout)');
        $resGuru->assertSee('Keluar');

        // 3. Role Wali Kelas
        $waliKelas = User::create([
            'username' => 'test_walikelas_logout',
            'name' => 'Wali Kelas X A',
            'role' => 'wali_kelas',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($waliKelas);
        $resWali = $this->get(route('walikelas.dashboard'));
        $resWali->assertStatus(200);
        $resWali->assertSee(route('logout'));
        $resWali->assertSee('Keluar (Logout)');
        $resWali->assertSee('Keluar');

        // 4. Role Siswa
        $siswa = Siswa::create([
            'nis' => '998877',
            'nisn' => '9988776655',
            'nama' => 'Siswa Uji Logout',
            'kelas' => 'X-1',
            'jenis_kelamin' => 'L',
        ]);

        $siswaUser = User::create([
            'username' => '9988776655',
            'name' => 'Siswa Uji Logout',
            'role' => 'siswa',
            'password' => bcrypt('9988776655'),
            'siswa_id' => $siswa->id,
        ]);

        $this->actingAs($siswaUser);
        $resSiswa = $this->get(route('siswa.dashboard'));
        $resSiswa->assertStatus(200);
        $resSiswa->assertSee(route('logout'));
        $resSiswa->assertSee('Keluar');
        $resSiswa->assertSee('Keluar dari Akun');

        // 5. Scanner Presensi Navbar
        $this->actingAs($guru);
        $resScanner = $this->get(route('scanner'));
        $resScanner->assertStatus(200);
        $resScanner->assertSee(route('logout'));
        $resScanner->assertSee('Keluar');
    }

    public function test_guru_menu_only_has_dashboard_and_scanner_and_no_rekapitulasi(): void
    {
        $guruOnly = User::create([
            'username' => 'guru_murni',
            'name' => 'Guru Pengajar Mapel',
            'role' => 'guru',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($guruOnly);

        $res = $this->get(route('guru.dashboard'));
        $res->assertStatus(200);

        // Harus ada
        $res->assertSee('Dashboard Guru');
        $res->assertSee('Scanner Presensi');
        $res->assertSee('Jurnal Mengajar');

        // Tidak boleh ada Rekapitulasi di sidebar maupun menu dropdown
        $res->assertDontSee('Rekapitulasi</span>', false);
        $res->assertDontSee('Rekapitulasi</a>', false);

        // Jika mencoba akses langsung ke /rekapitulasi, diarahkan kembali
        $resRekap = $this->get(route('rekapitulasi.index'));
        $resRekap->assertRedirect(route('guru.dashboard'));
    }

    public function test_wali_kelas_menu_has_dashboard_scanner_rekapitulasi_and_no_admin_menus(): void
    {
        $waliOnly = User::create([
            'username' => 'wali_murni',
            'name' => 'Ibu Novriyanti',
            'role' => 'wali_kelas',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($waliOnly);

        $res = $this->get(route('walikelas.dashboard'));
        $res->assertStatus(200);

        // Harus ada
        $res->assertSee('Dashboard Wali Kelas');
        $res->assertSee('Scanner Presensi');
        $res->assertSee('Rekapitulasi Kelas');

        // Tidak boleh ada menu admin
        $res->assertDontSee('Dashboard Admin');
        $res->assertDontSee('Kelola Siswa');
        $res->assertDontSee('Kelola Kelas');
        $res->assertDontSee('Generate Kartu Absen');
        $res->assertDontSee('Pengaturan Waktu');
        $res->assertDontSee('Pengaturan Akun');
        $res->assertDontSee('Pengaturan Aplikasi');
    }

    public function test_wali_kelas_detects_assigned_class_and_limits_rekapitulasi_to_their_class(): void
    {
        // Setup kelas X A dengan wali kelas Novriyanti
        Kelas::firstOrCreate([
            'tahun_pelajaran' => '2026/2027',
            'kelas' => 'X A',
        ], [
            'wali_kelas' => 'Novriyanti, S.Ag.',
        ]);

        // Setup kelas X B dengan wali kelas lain
        Kelas::firstOrCreate([
            'tahun_pelajaran' => '2026/2027',
            'kelas' => 'X B',
        ], [
            'wali_kelas' => 'Softa Rizanah, S.Pd.',
        ]);

        Siswa::firstOrCreate([
            'nis' => '101010',
        ], [
            'nisn' => '1010101010',
            'nama' => 'Siswa Kelas X A',
            'kelas' => 'X A',
            'jenis_kelamin' => 'L',
        ]);

        Siswa::firstOrCreate([
            'nis' => '202020',
        ], [
            'nisn' => '2020202020',
            'nama' => 'Siswa Kelas X B',
            'kelas' => 'X B',
            'jenis_kelamin' => 'P',
        ]);

        $waliUser = User::create([
            'username' => '197311251999032002',
            'name' => 'Novriyanti, S.Ag.',
            'role' => 'guru,wali_kelas',
            'password' => bcrypt('password123'),
        ]);

        // Verifikasi method deteksi kelas
        $detectedClasses = $waliUser->getDaftarKelasWali();
        $this->assertTrue($detectedClasses->contains('X A'));
        $this->assertFalse($detectedClasses->contains('X B'));

        $this->actingAs($waliUser);

        // 1. Dashboard Wali Kelas otomatis menampilkan X A
        $resDashboard = $this->get(route('walikelas.dashboard'));
        $resDashboard->assertStatus(200);
        $resDashboard->assertSee('Kelas X A');
        $resDashboard->assertSee('Siswa Kelas X A');
        $resDashboard->assertDontSee('Siswa Kelas X B');

        // 2. Halaman Rekapitulasi otomatis terkunci pada Kelas X A
        $resRekap = $this->get(route('rekapitulasi.index'));
        $resRekap->assertStatus(200);
        $resRekap->assertSee('Kelas X A');
        $resRekap->assertSee('Siswa Kelas X A');
        $resRekap->assertDontSee('Siswa Kelas X B');
    }

    public function test_admin_dashboard_live_stats_endpoint_returns_updated_statistics_after_absen_or_izin(): void
    {
        $admin = User::create([
            'username' => 'admin_live',
            'name' => 'Admin Live',
            'role' => 'admin',
            'password' => bcrypt('password123'),
        ]);

        $siswa1 = Siswa::create([
            'nis' => '555001',
            'nisn' => '00555001',
            'nama' => 'Rizki Pratama',
            'kelas' => 'X-A',
        ]);

        $siswa2 = Siswa::create([
            'nis' => '555002',
            'nisn' => '00555002',
            'nama' => 'Siti Aisyah',
            'kelas' => 'X-A',
        ]);

        $sesi = SesiAbsensi::create([
            'kode_sesi' => 'pagi-live',
            'nama_sesi' => 'Sesi Pagi',
            'jam_mulai' => '06:00:00',
            'jam_terlambat' => '07:00:00',
            'jam_selesai' => '08:00:00',
            'is_aktif' => true,
        ]);

        $this->actingAs($admin);

        // Statistik awal sebelum ada transaksi absen/izin
        $initialResponse = $this->getJson(route('dashboard', ['live' => 1]));
        $initialResponse->assertOk()
            ->assertJson([
                'totalSiswa' => 2,
                'jumlahSudahPresensi' => 0,
                'jumlahTepatWaktu' => 0,
                'totalIzinSakit' => 0,
                'jumlahAlpha' => 2,
            ]);

        // Transaksi 1: Siswa 1 tercatat hadir Tepat Waktu
        Absensi::create([
            'nis' => $siswa1->nis,
            'sesi_id' => $sesi->id,
            'user_id' => $admin->id,
            'tanggal' => Carbon::today()->toDateString(),
            'jam_scan' => '06:45:00',
            'status' => 'Tepat Waktu',
        ]);

        // Transaksi 2: Siswa 2 dicatat Izin melalui endpoint izin-sakit
        $this->post(route('guru.izin-sakit.store'), [
            'nis' => $siswa2->nis,
            'status' => 'Izin',
            'tanggal' => Carbon::today()->toDateString(),
            'sesi_id' => $sesi->id,
            'keterangan' => 'Izin mengikuti lomba',
        ])->assertRedirect();

        // Statistik live dashboard otomatis berubah sesuai transaksi terbaru
        $updatedResponse = $this->getJson(route('dashboard', ['live' => 1]));
        $updatedResponse->assertOk()
            ->assertJson([
                'totalSiswa' => 2,
                'jumlahSudahPresensi' => 2,
                'persentaseKehadiran' => 100,
                'jumlahTepatWaktu' => 1,
                'jumlahIjin' => 1,
                'totalIzinSakit' => 1,
                'jumlahAlpha' => 0,
            ]);
    }

    public function test_guru_dashboard_renders_student_live_search_data_elements_correctly(): void
    {
        $guru = User::create([
            'username' => 'guru_livesearch',
            'name' => 'Guru Live Search',
            'role' => 'guru',
            'password' => bcrypt('password123'),
        ]);

        Siswa::create([
            'nis' => '777001',
            'nisn' => '00777001',
            'nama' => 'Ahmad Fauzan',
            'kelas' => 'XI-IPA-1',
        ]);

        $this->actingAs($guru);

        $response = $this->get(route('guru.dashboard'));
        $response->assertOk()
            ->assertSee('id="allSiswaDataList"', false)
            ->assertSee('data-nis="777001"', false)
            ->assertSee('data-nama="Ahmad Fauzan"', false)
            ->assertSee('data-kelas="XI-IPA-1"', false)
            ->assertDontSee('!!json_encode', false);
    }

    public function test_uploaded_student_photo_is_saved_and_displayed_in_siswa_and_admin_dashboards(): void
    {
        $admin = User::create([
            'username' => 'admin_foto',
            'name' => 'Admin Foto',
            'role' => 'admin',
            'password' => bcrypt('password123'),
        ]);

        $siswa = Siswa::create([
            'nis' => '888001',
            'nisn' => '00888001',
            'nama' => 'Naila Rahma',
            'kelas' => 'X A',
        ]);

        $siswaUser = User::create([
            'username' => '00888001',
            'name' => 'Naila Rahma',
            'role' => 'siswa',
            'siswa_id' => $siswa->id,
            'password' => bcrypt('00888001'),
        ]);

        // 1. Siswa mengunggah foto profil dari Dashboard Siswa
        $this->actingAs($siswaUser);
        $fileSiswa = UploadedFile::fake()->image('foto_naila.jpg', 300, 300);

        $uploadRes = $this->post(route('siswa.upload-foto'), [
            'foto' => $fileSiswa,
        ]);
        $uploadRes->assertRedirect();

        $siswa->refresh();
        $this->assertNotNull($siswa->foto);
        $this->assertStringStartsWith('FotoSiswa/', $siswa->foto);
        $this->assertFileExists(public_path($siswa->foto));

        // 2. Pastikan foto tampil di Dashboard Siswa
        $siswaDash = $this->get(route('siswa.dashboard'));
        $siswaDash->assertOk()
            ->assertSee(asset($siswa->foto), false);

        // 3. Catat absensi hari ini dan pastikan foto tampil di Dashboard Admin & Kelola Siswa
        $sesi = SesiAbsensi::create([
            'kode_sesi' => 'pagi-foto',
            'nama_sesi' => 'Sesi Pagi',
            'jam_mulai' => '06:00:00',
            'jam_terlambat' => '07:00:00',
            'jam_selesai' => '08:00:00',
            'is_aktif' => true,
        ]);

        Absensi::create([
            'nis' => $siswa->nis,
            'sesi_id' => $sesi->id,
            'user_id' => $admin->id,
            'tanggal' => Carbon::today()->toDateString(),
            'jam_scan' => '06:50:00',
            'status' => 'Tepat Waktu',
        ]);

        $this->actingAs($admin);

        $adminDash = $this->get(route('dashboard'));
        $adminDash->assertOk()
            ->assertSee(asset($siswa->foto), false);

        $kelolaSiswa = $this->get(route('siswa.index'));
        $kelolaSiswa->assertOk()
            ->assertSee(asset($siswa->foto), false);

        // 4. Admin mengganti foto siswa melalui menu Kelola Siswa
        $fileAdmin = UploadedFile::fake()->image('foto_admin_update.png', 300, 300);
        $oldFoto = $siswa->foto;

        $updateRes = $this->put(route('siswa.update', $siswa->id), [
            'nis' => $siswa->nis,
            'nisn' => $siswa->nisn,
            'nama' => $siswa->nama,
            'kelas' => $siswa->kelas,
            'foto' => $fileAdmin,
        ]);
        $updateRes->assertRedirect(route('siswa.index'));

        $siswa->refresh();
        $this->assertNotEquals($oldFoto, $siswa->foto);
        $this->assertStringStartsWith('FotoSiswa/', $siswa->foto);
        $this->assertFileExists(public_path($siswa->foto));

        // Bersihkan file test
        $siswa->deleteExistingFoto();
    }

    public function test_siswa_can_update_no_hp_siswa_and_no_hp_ortu(): void
    {
        $siswa = Siswa::create([
            'nis' => '999001',
            'nisn' => '00999001',
            'nama' => 'Budi Santoso',
            'kelas' => 'X B',
        ]);

        $siswaUser = User::create([
            'username' => '00999001',
            'name' => 'Budi Santoso',
            'role' => 'siswa',
            'siswa_id' => $siswa->id,
            'password' => bcrypt('00999001'),
        ]);

        $this->actingAs($siswaUser);

        $response = $this->post(route('siswa.update-kontak'), [
            'no_hp_siswa' => '081211112222',
            'no_hp_ortu' => '081233334444',
        ]);

        $response->assertRedirect();

        $siswa->refresh();
        $this->assertSame('081211112222', $siswa->no_hp_siswa);
        $this->assertSame('081233334444', $siswa->no_hp_ortu);

        $dashResponse = $this->get(route('siswa.dashboard'));
        $dashResponse->assertOk()
            ->assertSee('081211112222')
            ->assertSee('081233334444');
    }

    public function test_admin_can_manage_jam_belajars_and_mapels(): void
    {
        $admin = User::create([
            'username' => 'admin_mapel',
            'name' => 'Admin Jadwal',
            'role' => 'admin',
            'password' => bcrypt('password123'),
        ]);

        $guru = User::create([
            'username' => 'guru_mapel_1',
            'name' => 'Drs. Hendra Wijaya',
            'role' => 'guru',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($admin);

        // 1. Buat data Kelas untuk referensi kolom kelas di tabel mapels
        $kelas = Kelas::create([
            'tahun_pelajaran' => '2026/2027',
            'kelas' => 'X A',
        ]);

        // 2. Tambah data ke tabel jam_belajars (id | jam_ke | waktu_mulai | waktu_selesai)
        $storeJamRes = $this->post(route('jam-belajar.store'), [
            'jam_ke' => '1',
            'waktu_mulai' => '07:15',
            'waktu_selesai' => '08:00',
        ]);
        $storeJamRes->assertRedirect();

        $jamBelajar = JamBelajar::first();
        $this->assertNotNull($jamBelajar);
        $this->assertSame('1', $jamBelajar->jam_ke);
        $this->assertStringStartsWith('07:15', $jamBelajar->waktu_mulai);
        $this->assertStringStartsWith('08:00', $jamBelajar->waktu_selesai);

        // 3. Tambah data ke tabel mapels (id | kode_mapel | mata_pelajaran)
        $storeMapelRes = $this->post(route('mapel.store'), [
            'kode_mapel' => 'MTK-W',
            'mata_pelajaran' => 'Matematika Wajib',
        ]);
        $storeMapelRes->assertRedirect();

        $mapel = Mapel::first();
        $this->assertNotNull($mapel);
        $this->assertSame('MTK-W', $mapel->kode_mapel);
        $this->assertSame('Matematika Wajib', $mapel->mata_pelajaran);

        // 4. Pastikan tampil di Dashboard Admin serta di halaman khusus Jam Belajar dan Mapel (dengan Kode Mapel, tanpa kolom Kelas & Nama Pengajar)
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Jam ke-1')
            ->assertSee('MTK-W')
            ->assertSee('Matematika Wajib');

        $this->get(route('jam-belajar.index'))
            ->assertOk()
            ->assertSee('Jam ke-1')
            ->assertSee('07:15');

        $this->get(route('mapel.index'))
            ->assertOk()
            ->assertSee('MTK-W')
            ->assertSee('Matematika Wajib')
            ->assertDontSee('Nama Pengajar (ID User)')
            ->assertDontSee('Cari mata pelajaran...');

        // 5. Update Jam Belajar & Mapel
        $this->put(route('jam-belajar.update', $jamBelajar->id), [
            'jam_ke' => '2',
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '08:45',
        ])->assertRedirect();

        $this->put(route('mapel.update', $mapel->id), [
            'kode_mapel' => 'FIS-D',
            'mata_pelajaran' => 'Fisika Dasar',
        ])->assertRedirect();

        $this->assertDatabaseHas('jam_belajars', [
            'id' => $jamBelajar->id,
            'jam_ke' => '2',
        ]);
        $this->assertDatabaseHas('mapels', [
            'id' => $mapel->id,
            'kode_mapel' => 'FIS-D',
            'mata_pelajaran' => 'Fisika Dasar',
        ]);

        // 6. Hapus Jam Belajar & Mapel
        $this->delete(route('jam-belajar.destroy', $jamBelajar->id))->assertRedirect();
        $this->delete(route('mapel.destroy', $mapel->id))->assertRedirect();

        $this->assertDatabaseMissing('jam_belajars', ['id' => $jamBelajar->id]);
        $this->assertDatabaseMissing('mapels', ['id' => $mapel->id]);
    }

    public function test_admin_can_manage_jadwals_and_absensi_gurus(): void
    {
        $admin = User::create([
            'username' => 'admin_jadwal',
            'name' => 'Admin Kurikulum',
            'role' => 'admin',
            'password' => bcrypt('password123'),
        ]);

        $guru = User::create([
            'username' => 'guru_fisika',
            'name' => 'Dra. Siti Aminah',
            'role' => 'guru',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($admin);

        $kelas = Kelas::create([
            'tahun_pelajaran' => '2026/2027',
            'kelas' => 'XI IPA 1',
        ]);

        $jam = JamBelajar::create([
            'jam_ke' => '1',
            'waktu_mulai' => '07:15:00',
            'waktu_selesai' => '08:00:00',
        ]);

        $mapel = Mapel::create([
            'kode_mapel' => 'FIS-P',
            'kelas' => $kelas->id,
            'mata_pelajaran' => 'Fisika Peminatan',
            'nama_pengajar' => $guru->id,
        ]);

        // 1. Tambah Jadwal Pelajaran (mengambil jam dari jam_belajars dan mapel/pengajar dari mapels)
        $storeJadwal = $this->post(route('jadwal.store'), [
            'tahun_pelajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'kelas' => $kelas->id,
            'hari' => 'Senin',
            'jam_belajar_id' => $jam->id,
            'mapel_id' => $mapel->id,
        ]);
        $storeJadwal->assertRedirect();

        $jadwal = Jadwal::first();
        $this->assertNotNull($jadwal);
        $this->assertSame('2026/2027', $jadwal->tahun_pelajaran);
        $this->assertSame('Ganjil', $jadwal->semester);
        $this->assertEquals($kelas->id, $jadwal->kelas);
        $this->assertSame('Senin', $jadwal->hari);
        $this->assertSame('1', $jadwal->jam_ke);
        $this->assertStringStartsWith('07:15', $jadwal->waktu_mulai);
        $this->assertStringStartsWith('08:00', $jadwal->waktu_selesai);
        $this->assertSame('Fisika Peminatan', $jadwal->mata_pelajaran);
        $this->assertSame('FIS-P', $jadwal->tampil_mapel);
        $this->assertEquals($guru->id, $jadwal->nama_pengajar);

        // 2. Tambah Absensi Guru pada tabel absensi_gurus
        $storeAbsensiGuru = $this->post(route('jadwal.absensi-guru.store'), [
            'hari' => 'Senin',
            'tanggal' => '2026-11-05',
            'nama_guru' => 'Dra. Siti Aminah',
            'jam_ke' => '1',
            'mata_pelajaran' => 'Fisika Peminatan',
            'materi_pembelajaran' => 'Hukum Newton tentang Gerak',
            'status' => 'Hadir',
            'keterangan' => 'Pembelajaran kondusif',
        ]);
        $storeAbsensiGuru->assertRedirect();

        $absensiGuru = AbsensiGuru::first();
        $this->assertNotNull($absensiGuru);
        $this->assertSame('Senin', $absensiGuru->hari);
        $this->assertSame('Dra. Siti Aminah', $absensiGuru->nama_guru);
        $this->assertSame('Hukum Newton tentang Gerak', $absensiGuru->materi_pembelajaran);
        $this->assertSame('Hadir', $absensiGuru->status);

        // 3. Cek tampilan halaman Kelola Jadwal Pelajaran dengan filter dropdown (menampilkan baris jam_belajars, dropdown mapel & nama pengajar)
        $this->get(route('jadwal.index', [
            'tahun_pelajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'kelas' => $kelas->id,
            'hari' => 'Senin',
        ]))
            ->assertOk()
            ->assertSee('Kelola Jadwal Pelajaran')
            ->assertSee('Jam ke-1')
            ->assertSee('07:15 WIB')
            ->assertSee('08:00 WIB')
            ->assertSee('Fisika Peminatan')
            ->assertSee('FIS-P')
            ->assertSee('Dra. Siti Aminah')
            ->assertDontSee('(ID: #');

        // 2b. Tabel Absensi & Jurnal Mengajar Guru kini punya halaman tersendiri
        $this->get(route('jurnal-mengajar.index'))
            ->assertOk()
            ->assertSee('Tabel Absensi & Jurnal Mengajar Guru', false)
            ->assertSee('Hukum Newton tentang Gerak')
            ->assertSee('Dra. Siti Aminah');

        // 3b. Simpan jadwal langsung dari dropdown baris Tabel Jadwal Pelajaran (bulk items)
        $this->post(route('jadwal.store'), [
            'tahun_pelajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'kelas' => $kelas->id,
            'hari' => 'Rabu',
            'items' => [
                [
                    'jam_belajar_id' => $jam->id,
                    'jam_ke' => '1',
                    'waktu_mulai' => '07:15',
                    'waktu_selesai' => '08:00',
                    'mapel_id' => $mapel->id,
                    'nama_pengajar' => $guru->id,
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('jadwals', [
            'kelas' => $kelas->id,
            'hari' => 'Rabu',
            'jam_ke' => '1',
            'mapel_id' => $mapel->id,
            'mata_pelajaran' => 'Fisika Peminatan',
            'nama_pengajar' => $guru->id,
        ]);

        // 4. Update & Hapus Jadwal serta Absensi Guru
        $this->put(route('jadwal.update', $jadwal->id), [
            'tahun_pelajaran' => '2026/2027',
            'semester' => 'Genap',
            'kelas' => $kelas->id,
            'hari' => 'Selasa',
            'jam_ke' => '2',
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '08:45',
            'mata_pelajaran' => 'Fisika Lanjutan',
            'nama_pengajar' => $guru->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('jadwals', [
            'id' => $jadwal->id,
            'semester' => 'Genap',
            'hari' => 'Selasa',
            'mata_pelajaran' => 'Fisika Lanjutan',
        ]);

        $this->delete(route('jadwal.destroy', $jadwal->id))->assertRedirect();
        $this->delete(route('jadwal.absensi-guru.destroy', $absensiGuru->id))->assertRedirect();

        $this->assertDatabaseMissing('jadwals', ['id' => $jadwal->id]);
        $this->assertDatabaseMissing('absensi_gurus', ['id' => $absensiGuru->id]);
    }

    public function test_guru_absensi_mengajar_and_admin_dashboard_mengajar_indicators(): void
    {
        $this->travelTo('2026-11-05 07:16:00');

        $admin = User::create([
            'username' => 'admin_pantau',
            'name' => 'Admin Pemantau',
            'role' => 'admin',
            'password' => bcrypt('password123'),
        ]);

        $guru = User::create([
            'username' => 'guru_kimia',
            'name' => 'Drs. Rahmat Kimia',
            'role' => 'guru',
            'password' => bcrypt('password123'),
        ]);

        $kelasXA = Kelas::create([
            'tahun_pelajaran' => '2026/2027',
            'kelas' => 'X A',
        ]);

        $kelasXB = Kelas::create([
            'tahun_pelajaran' => '2026/2027',
            'kelas' => 'X B',
        ]);

        $jam1 = JamBelajar::create(['jam_ke' => '1', 'waktu_mulai' => '07:15:00', 'waktu_selesai' => '08:00:00']);
        $jam2 = JamBelajar::create(['jam_ke' => '2', 'waktu_mulai' => '08:00:00', 'waktu_selesai' => '08:45:00']);

        $mapelXA = Mapel::create([
            'kode_mapel' => 'KIM',
            'kelas' => $kelasXA->id,
            'mata_pelajaran' => 'Kimia Dasar',
            'nama_pengajar' => $guru->id,
        ]);

        // Buat jadwal Kelas X A untuk Jam ke-1 dan Jam ke-2 pada hari Kamis
        Jadwal::create([
            'tahun_pelajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'kelas' => $kelasXA->id,
            'hari' => 'Kamis',
            'jam_belajar_id' => $jam1->id,
            'jam_ke' => '1',
            'waktu_mulai' => '07:15:00',
            'waktu_selesai' => '08:00:00',
            'mapel_id' => $mapelXA->id,
            'mata_pelajaran' => 'Kimia Dasar',
            'nama_pengajar' => $guru->id,
        ]);

        Jadwal::create([
            'tahun_pelajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'kelas' => $kelasXA->id,
            'hari' => 'Kamis',
            'jam_belajar_id' => $jam2->id,
            'jam_ke' => '2',
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '08:45:00',
            'mapel_id' => $mapelXA->id,
            'mata_pelajaran' => 'Kimia Dasar',
            'nama_pengajar' => $guru->id,
        ]);

        // 1. Cek Dashboard Mengajar Admin SEBELUM guru scan QR Kelas -> Indikator Merah (Belum Hadir) & menampilkan Kode Mapel 'KIM'
        $this->actingAs($admin);
        $dashBefore = $this->get(route('dashboard.mengajar', [
            'tanggal' => '2026-11-05',
            'hari' => 'Kamis',
        ]));
        $dashBefore->assertOk()
            ->assertSee('Dashboard Absensi')
            ->assertSee('Dashboard Mengajar')
            ->assertDontSee('Presensi Izin & Sakit')
            ->assertSee('Level Kelas X')
            ->assertSee('Kelas X A')
            ->assertSee('Kelas X B')
            ->assertSee('KIM')
            ->assertSee('Drs. Rahmat Kimia')
            ->assertSee('status-belum-merah', false)
            ->assertSee('Belum Hadir');

        // 2. Guru membuka menu Absensi Mengajar dan mengisi Kelas X A, memilih Jam ke 1 dan 2 sekaligus, dan scan QR Code Kelas
        $this->actingAs($guru);
        $this->get(route('guru.absensi-mengajar'))
            ->assertOk()
            ->assertSee('Absensi Mengajar Guru')
            ->assertSee('Scan QR Code Kelas');

        Storage::fake('foto_guru');
        $scanRes = $this->post(route('guru.absensi-mengajar.store'), [
            'kelas' => $kelasXA->id,
            'jam_ke' => ['1', '2'],
            'qr_kelas' => 'KELAS:'.$kelasXA->id,
            'materi_pembelajaran' => 'Struktur Atom & Tabel Periodik',
            'foto' => UploadedFile::fake()->image('foto-kelas.jpg'),
            'tanggal' => '2026-11-05',
            'hari' => 'Kamis',
        ], ['Accept' => 'application/json']);
        $scanRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.mata_pelajaran', 'Kimia Dasar')
            ->assertJsonPath('data.jam_ke', '1,2')
            ->assertJsonPath('data.foto', '2026-11-05/x-a-jam-1-2.jpg');

        // 3. Cek Dashboard Mengajar Admin SETELAH guru scan QR Kelas -> Indikator berubah Hijau (Hadir) untuk Jam ke-1 & Jam ke-2
        $this->actingAs($admin);
        $dashAfter = $this->get(route('dashboard.mengajar', [
            'tanggal' => '2026-11-05',
            'hari' => 'Kamis',
        ]));
        $dashAfter->assertOk()
            ->assertSee('jadwal-cell-card status-hadir-hijau', false)
            ->assertDontSee('jadwal-cell-card status-belum-merah', false)
            ->assertSee('id="statTotalHadirSlot">2</h4>', false)
            ->assertSee('id="statTotalBelumHadirSlot">0</h4>', false)
            ->assertSee('x-a-jam-1-2.jpg');

        // 4. Catat Absensi Guru dengan multi-status ['Izin', 'Tugas'] untuk Kelas X B -> Indikator tampil berwarna Cyan
        Jadwal::create([
            'tahun_pelajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'kelas' => $kelasXB->id,
            'hari' => 'Kamis',
            'jam_belajar_id' => $jam1->id,
            'jam_ke' => '1',
            'waktu_mulai' => '07:15:00',
            'waktu_selesai' => '08:00:00',
            'mata_pelajaran' => 'Biologi Umum',
            'nama_pengajar' => $guru->id,
        ]);

        $this->post(route('jadwal.absensi-guru.store'), [
            'kelas' => $kelasXB->id,
            'hari' => 'Kamis',
            'tanggal' => '2026-11-05',
            'nama_guru' => 'Drs. Rahmat Kimia',
            'jam_ke' => '1',
            'mata_pelajaran' => 'Biologi Umum',
            'materi_pembelajaran' => 'Mengerjakan LKS Bab Sel Halaman 25',
            'status' => ['Izin', 'Tugas'],
            'keterangan' => 'Dinas Luar + Tugas Mandiri',
        ])->assertRedirect();

        $this->assertDatabaseHas('absensi_gurus', [
            'kelas' => $kelasXB->id,
            'mata_pelajaran' => 'Biologi Umum',
            'status' => 'Izin, Tugas',
        ]);

        $dashWithTugas = $this->get(route('dashboard.mengajar', [
            'tanggal' => '2026-11-05',
            'hari' => 'Kamis',
        ]));
        $dashWithTugas->assertOk()
            ->assertSee('jadwal-cell-card status-tugas-cyan', false)
            ->assertSee('Izin, Tugas')
            ->assertSee('id="statTotalTugasSlot" style="color: #0891b2;">1</h4>', false);

        // 5. Jika guru kosong (mapel terisi) pada jadwal, jadwal tampil aktif tetapi tidak dihitung dalam jadwal yang dipantau
        Jadwal::create([
            'tahun_pelajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'kelas' => $kelasXB->id,
            'hari' => 'Kamis',
            'jam_belajar_id' => $jam2->id,
            'jam_ke' => '2',
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '08:45:00',
            'mata_pelajaran' => 'Kimia Dasar',
            'nama_pengajar' => null,
        ]);

        $dashWithEmptyGuru = $this->get(route('dashboard.mengajar', [
            'tanggal' => '2026-11-05',
            'hari' => 'Kamis',
        ]));
        $dashWithEmptyGuru->assertOk()
            ->assertSee('status-guru-kosong-orange', false)
            ->assertDontSee('Aktif</span>', false);
        // Total slot tetap 3 (Kimia Jam 1 & 2 di X A, Biologi Jam 1 di X B), slot guru kosong di Jam 2 X B tidak dihitung
        $this->assertEquals(3, $dashWithEmptyGuru->viewData('totalJadwalSlot'));
        // Belum hadir hanya 0 karena Kimia X A sudah hadir, Biologi X B sudah tercatat Izin+Tugas, dan slot guru kosong tidak dipantau
        $this->assertEquals(0, $dashWithEmptyGuru->viewData('totalBelumHadirSlot'));
        $this->assertEquals(2, $dashWithEmptyGuru->viewData('totalHadirSlot'));
        $this->assertEquals(1, $dashWithEmptyGuru->viewData('totalTugasSlot'));
    }
}
