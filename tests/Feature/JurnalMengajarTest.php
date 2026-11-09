<?php

namespace Tests\Feature;

use App\Models\AbsensiGuru;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class JurnalMengajarTest extends TestCase
{
    use RefreshDatabase;

    protected User $guru;

    protected User $guruLain;

    protected User $admin;

    protected Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::create([
            'username' => 'guru_jurnal',
            'name' => 'Abdul Basit',
            'role' => 'guru',
            'password' => bcrypt('password123'),
        ]);

        $this->guruLain = User::create([
            'username' => 'guru_jurnal_lain',
            'name' => 'Siti Aminah',
            'role' => 'guru',
            'password' => bcrypt('password123'),
        ]);

        $this->admin = User::create([
            'username' => 'admin_jurnal',
            'name' => 'Admin Jurnal',
            'role' => 'admin',
            'password' => bcrypt('password123'),
        ]);

        $this->kelas = Kelas::create([
            'tahun_pelajaran' => '2026/2027',
            'kelas' => 'XI A4',
        ]);

        $mapel = Mapel::create([
            'kode_mapel' => 'SKI',
            'kelas' => $this->kelas->id,
            'mata_pelajaran' => 'Sejarah Kebudayaan Islam',
        ]);

        Jadwal::create([
            'tahun_pelajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'kelas' => $this->kelas->id,
            'hari' => 'Kamis',
            'jam_ke' => '1',
            'waktu_mulai' => '07:15:00',
            'waktu_selesai' => '08:00:00',
            'mata_pelajaran' => $mapel->mata_pelajaran,
            'nama_pengajar' => $this->guru->id,
            'mapel_id' => $mapel->id,
        ]);

        AbsensiGuru::create([
            'kelas' => $this->kelas->id,
            'hari' => 'Kamis',
            'tanggal' => '2026-11-05',
            'nama_guru' => $this->guru->name,
            'guru_id' => $this->guru->id,
            'jam_ke' => '1',
            'mata_pelajaran' => 'Sejarah Kebudayaan Islam',
            'materi_pembelajaran' => 'Peradaban Islam Abad Pertengahan',
            'foto' => '2026-11-05/xi-a4-jam-1.jpg',
            'status' => 'Hadir',
        ]);

        AbsensiGuru::create([
            'kelas' => $this->kelas->id,
            'hari' => 'Kamis',
            'tanggal' => '2026-11-05',
            'nama_guru' => $this->guruLain->name,
            'guru_id' => $this->guruLain->id,
            'jam_ke' => '2',
            'mata_pelajaran' => 'Matematika Wajib',
            'materi_pembelajaran' => 'Persamaan Linear',
            'status' => 'Izin',
        ]);
    }

    public function test_halaman_jurnal_menampilkan_catatan_milik_guru_sendiri(): void
    {
        $this->actingAs($this->guru)
            ->get(route('jurnal-mengajar.index', ['search' => 'Persamaan']))
            ->assertOk()
            ->assertSee('Tabel Absensi & Jurnal Mengajar Guru')
            ->assertSee('Peradaban Islam Abad Pertengahan')
            ->assertSee('xi-a4-jam-1.jpg')
            ->assertSee('href="'.asset('foto-guru/2026-11-05/xi-a4-jam-1.jpg').'"', false)
            ->assertDontSee('Persamaan Linear')
            ->assertDontSee('Matematika Wajib')
            ->assertDontSee('name="search"', false)
            ->assertDontSee('>Aksi</th>', false)
            ->assertSee('>No</th>', false)
            ->assertDontSee('Rekapitulasi Absen Guru')
            ->assertSee('Halaman ini menampilkan')
            // Tombol pencatatan hanya untuk admin; modal tetap ada di HTML namun tombolnya tidak dirender
            ->assertViewHas('isAdmin', false)
            ->assertViewHas('daftarPengajar', fn ($pengajar) => $pengajar->pluck('id')->all() === [$this->guru->id]);
    }

    public function test_catatan_guru_diurutkan_dari_terbaru_dan_menggunakan_nomor_urut(): void
    {
        AbsensiGuru::create([
            'kelas' => $this->kelas->id,
            'hari' => 'Jumat',
            'tanggal' => '2026-11-06',
            'nama_guru' => $this->guru->name,
            'guru_id' => $this->guru->id,
            'jam_ke' => '2',
            'mata_pelajaran' => 'Sejarah Kebudayaan Islam',
            'materi_pembelajaran' => 'Materi Terbaru',
            'status' => 'Hadir',
        ]);

        $response = $this->actingAs($this->guru)
            ->get(route('jurnal-mengajar.index'))
            ->assertOk()
            ->assertDontSee('<td class="ps-3 fw-bold text-muted">#', false);

        $content = $response->getContent();

        $this->assertMatchesRegularExpression('/<td class="ps-3 fw-bold text-muted">\s*1\s*<\/td>/', $content);
        $this->assertMatchesRegularExpression('/<td class="ps-3 fw-bold text-muted">\s*2\s*<\/td>/', $content);
        $this->assertLessThan(
            strpos($content, 'Peradaban Islam Abad Pertengahan'),
            strpos($content, 'Materi Terbaru'),
        );
    }

    public function test_admin_melihat_catatan_semua_guru(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('jurnal-mengajar.index'))
            ->assertOk()
            ->assertSee('Rekapitulasi Absen Guru')
            ->assertSee('Cuti')
            ->assertSee('Tidak Hadir')
            ->assertSee('Rekapitulasi Absen Mengajar')
            ->assertDontSee('Per Tanggal')
            ->assertSee('max-height: 280px; overflow-y: auto', false)
            ->assertSee('Rekapitulasi Absen Siswa')
            ->assertSee('Peradaban Islam Abad Pertengahan')
            ->assertSee('Persamaan Linear')
            ->assertSee('Catat Absensi Guru')
            ->assertSee('name="search"', false)
            ->assertSee('>Aksi</th>', false)
            ->assertSee('>ID</th>', false)
            ->assertDontSee('Halaman ini menampilkan');

        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, '<!-- Filter & Pencarian -->'),
            strpos($content, 'Jumlah Sesi per Kategori'),
        );
    }

    public function test_rekap_semester_ganjil_mengelompokkan_kategori_absensi_guru(): void
    {
        $this->createGuruAttendance($this->guru, '2026-08-10', 'Izin, Tugas', '2');
        $this->createGuruAttendance($this->guru, '2026-09-10', 'Sakit, Tugas', '3');
        $this->createGuruAttendance($this->guru, '2026-10-10', 'Alfa', '4');
        $this->createGuruAttendance($this->guru, '2026-10-11', 'Izin', '5');
        $this->createGuruAttendance($this->guru, '2026-10-12', 'Sakit', '6');
        $this->createGuruAttendance($this->guru, '2026-10-13', 'Tidak Hadir', '7');
        $this->createGuruAttendance($this->guru, '2026-10-14', 'Hadir, Tugas', '8');
        $this->createGuruAttendance($this->guru, '2026-10-15', 'Cuti', '10');
        $this->createGuruAttendance($this->guru, '2027-01-10', 'Hadir', '9');

        $response = $this->actingAs($this->admin)
            ->get(route('jurnal-mengajar.index', [
                'periode' => 'semester',
                'semester' => 'ganjil',
                'tahun' => 2026,
            ]))
            ->assertOk();

        $response->assertViewHas('totalKategoriRekap', [
            'hadir' => 2,
            'izin_tugas' => 1,
            'sakit_tugas' => 1,
            'cuti' => 1,
            'tidak_hadir' => 5,
        ]);
        $response->assertViewHas('rekapAbsensiGuru', fn ($rekap) => $rekap->contains(
            fn ($guru) => $guru->nama_guru === 'Abdul Basit'
                && (int) $guru->hadir === 2
                && (int) $guru->izin_tugas === 1
                && (int) $guru->sakit_tugas === 1
                && (int) $guru->cuti === 1
                && (int) $guru->tidak_hadir === 4,
        ));
    }

    public function test_rekap_semester_genap_mencakup_januari_sampai_juni_tahun_ajaran(): void
    {
        $this->createGuruAttendance($this->guru, '2026-12-31', 'Hadir', '2');
        $this->createGuruAttendance($this->guru, '2027-01-01', 'Hadir', '3');
        $this->createGuruAttendance($this->guru, '2027-06-30', 'Hadir', '4');
        $this->createGuruAttendance($this->guru, '2027-07-01', 'Alfa', '5');

        $response = $this->actingAs($this->admin)
            ->get(route('jurnal-mengajar.index', [
                'periode' => 'semester',
                'semester' => 'genap',
                'tahun' => 2026,
            ]))
            ->assertOk();

        $response->assertViewHas('totalKategoriRekap', [
            'hadir' => 2,
            'izin_tugas' => 0,
            'sakit_tugas' => 0,
            'cuti' => 0,
            'tidak_hadir' => 0,
        ]);
    }

    public function test_rekap_bulanan_hanya_menghitung_bulan_yang_dipilih(): void
    {
        $this->createGuruAttendance($this->guru, '2026-12-01', 'Hadir', '2');

        $this->actingAs($this->admin)
            ->get(route('jurnal-mengajar.index', [
                'periode' => 'bulan',
                'bulan' => 11,
                'tahun' => 2026,
            ]))
            ->assertOk()
            ->assertViewHas('totalKategoriRekap', [
                'hadir' => 1,
                'izin_tugas' => 0,
                'sakit_tugas' => 0,
                'cuti' => 0,
                'tidak_hadir' => 1,
            ]);
    }

    public function test_admin_dapat_mengunduh_rekap_absensi_guru_dalam_excel(): void
    {
        $this->createGuruAttendance($this->guru, '2026-11-06', 'Cuti', '2');

        $response = $this->actingAs($this->admin)
            ->get(route('jurnal-mengajar.export.excel', [
                'periode' => 'bulan',
                'bulan' => 11,
                'tahun' => 2026,
            ]))
            ->assertOk()
            ->assertDownload('Rekap_Absen_Guru_november-2026.xlsx')
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $excelContent = $response->streamedContent();
        $this->assertStringStartsWith('PK', $excelContent);

        $temporaryFile = tempnam(sys_get_temp_dir(), 'rekap-absen-guru-');
        $this->assertNotFalse($temporaryFile);
        file_put_contents($temporaryFile, $excelContent);

        try {
            $sheet = IOFactory::load($temporaryFile)->getActiveSheet();

            $this->assertSame('Cuti', $sheet->getCell('F4')->getValue());
            $this->assertSame('Abdul Basit', $sheet->getCell('B5')->getValue());
            $this->assertSame(1, $sheet->getCell('F5')->getValue());
        } finally {
            unlink($temporaryFile);
        }
    }

    public function test_admin_dapat_mengunduh_rekap_absensi_guru_dalam_pdf(): void
    {
        $this->createGuruAttendance($this->guru, '2026-11-06', 'Cuti', '2');

        $response = $this->actingAs($this->admin)
            ->get(route('jurnal-mengajar.export.pdf', [
                'periode' => 'bulan',
                'bulan' => 11,
                'tahun' => 2026,
            ]))
            ->assertOk()
            ->assertDownload('Rekap_Absen_Guru_november-2026.pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_guru_tidak_dapat_mengunduh_rekap_absensi_guru(): void
    {
        $this->actingAs($this->guru)
            ->get(route('jurnal-mengajar.export.excel', [
                'periode' => 'bulan',
                'bulan' => 11,
                'tahun' => 2026,
            ]))
            ->assertForbidden();
    }

    public function test_guru_tidak_dapat_mengunduh_rekap_absensi_guru_dalam_pdf(): void
    {
        $this->actingAs($this->guru)
            ->get(route('jurnal-mengajar.export.pdf', [
                'periode' => 'bulan',
                'bulan' => 11,
                'tahun' => 2026,
            ]))
            ->assertForbidden();
    }

    public function test_filter_pencarian_materi_bekerja(): void
    {
        $this->actingAs($this->admin)
            ->get(route('jurnal-mengajar.index', ['search' => 'Persamaan']))
            ->assertOk()
            ->assertSee('Persamaan Linear')
            ->assertDontSee('Peradaban Islam Abad Pertengahan');
    }

    public function test_filter_rentang_tanggal_bekerja(): void
    {
        $this->actingAs($this->admin)
            ->get(route('jurnal-mengajar.index', ['tanggal_mulai' => '2026-11-06']))
            ->assertOk()
            ->assertDontSee('Peradaban Islam Abad Pertengahan')
            ->assertDontSee('Persamaan Linear');
    }

    public function test_filter_kelas_bekerja(): void
    {
        $kelasLain = Kelas::create([
            'tahun_pelajaran' => '2026/2027',
            'kelas' => 'XII B1',
        ]);

        $this->actingAs($this->admin)
            ->get(route('jurnal-mengajar.index', ['kelas' => $kelasLain->id]))
            ->assertOk()
            ->assertDontSee('Peradaban Islam Abad Pertengahan');
    }

    public function test_aksi_hapus_tetap_berfungsi_dari_halaman_jurnal(): void
    {
        $catatan = AbsensiGuru::where('guru_id', $this->guru->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->delete(route('jadwal.absensi-guru.destroy', $catatan->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('absensi_gurus', ['id' => $catatan->id]);
    }

    private function createGuruAttendance(User $guru, string $tanggal, string $status, string $jamKe): AbsensiGuru
    {
        return AbsensiGuru::create([
            'kelas' => $this->kelas->id,
            'hari' => 'Senin',
            'tanggal' => $tanggal,
            'nama_guru' => $guru->name,
            'guru_id' => $guru->id,
            'jam_ke' => $jamKe,
            'mata_pelajaran' => 'Sejarah Kebudayaan Islam',
            'status' => $status,
        ]);
    }
}
