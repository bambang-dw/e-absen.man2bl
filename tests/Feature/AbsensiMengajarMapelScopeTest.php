<?php

namespace Tests\Feature;

use App\Models\AbsensiGuru;
use App\Models\Jadwal;
use App\Models\JamBelajar;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AbsensiMengajarMapelScopeTest extends TestCase
{
    use RefreshDatabase;

    protected User $guru;

    protected User $guruLain;

    protected Kelas $kelasAmpuan;

    protected Kelas $kelasLain;

    protected Mapel $mapelAmpuan;

    protected Mapel $mapelGuruLain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-05 07:16:00');
        Storage::fake('public');

        $this->guru = User::create([
            'username' => 'guru_ski',
            'name' => 'Abdul Basit',
            'role' => 'guru',
            'password' => bcrypt('password123'),
        ]);

        $this->guruLain = User::create([
            'username' => 'guru_mtk',
            'name' => 'Siti Aminah',
            'role' => 'guru',
            'password' => bcrypt('password123'),
        ]);

        $this->kelasAmpuan = Kelas::create(['tahun_pelajaran' => '2026/2027', 'kelas' => 'XI A4']);
        $this->kelasLain = Kelas::create(['tahun_pelajaran' => '2026/2027', 'kelas' => 'XI B1']);

        JamBelajar::create(['jam_ke' => '1', 'waktu_mulai' => '07:15:00', 'waktu_selesai' => '08:00:00']);

        $this->mapelAmpuan = Mapel::create([
            'kode_mapel' => 'SKI',
            'kelas' => $this->kelasAmpuan->id,
            'mata_pelajaran' => 'Sejarah Kebudayaan Islam',
        ]);

        $this->mapelGuruLain = Mapel::create([
            'kode_mapel' => 'MTK',
            'kelas' => $this->kelasAmpuan->id,
            'mata_pelajaran' => 'Matematika Wajib',
            'nama_pengajar' => $this->guruLain->id,
        ]);

        Jadwal::create([
            'tahun_pelajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'kelas' => $this->kelasAmpuan->id,
            'hari' => 'Kamis',
            'jam_ke' => '1',
            'waktu_mulai' => '07:15:00',
            'waktu_selesai' => '08:00:00',
            'mata_pelajaran' => $this->mapelAmpuan->mata_pelajaran,
            'nama_pengajar' => $this->guru->id,
            'mapel_id' => $this->mapelAmpuan->id,
        ]);
    }

    public function test_dropdown_mapel_hanya_berisi_mapel_yang_diampu(): void
    {
        $response = $this->actingAs($this->guru)->get(route('guru.absensi-mengajar.mapel', $this->kelasAmpuan->id));

        $response->assertOk()
            ->assertJsonPath('default_mata_pelajaran', 'Sejarah Kebudayaan Islam')
            ->assertJsonCount(1, 'mapels')
            ->assertJsonPath('mapels.0.mata_pelajaran', 'Sejarah Kebudayaan Islam');

        $namaMapel = collect($response->json('mapels'))->pluck('mata_pelajaran');
        $this->assertFalse($namaMapel->contains('Matematika Wajib'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function postAbsensiMengajarJson(array $payload): TestResponse
    {
        $payload['foto'] = UploadedFile::fake()->image('foto-kelas.jpg');

        return $this->post(route('guru.absensi-mengajar.store'), $payload, ['Accept' => 'application/json']);
    }

    public function test_dropdown_mapel_kosong_untuk_kelas_yang_tidak_diampu(): void
    {
        $this->actingAs($this->guru)
            ->get(route('guru.absensi-mengajar.mapel', $this->kelasLain->id))
            ->assertOk()
            ->assertJsonCount(0, 'mapels');
    }

    public function test_halaman_tidak_menampilkan_mapel_guru_lain(): void
    {
        $this->actingAs($this->guru)
            ->get(route('guru.absensi-mengajar'))
            ->assertOk()
            ->assertViewHas('mapels', fn ($mapels) => $mapels->pluck('mata_pelajaran')->all() === ['Sejarah Kebudayaan Islam'])
            ->assertViewHas('daftarKelas', fn ($kelas) => $kelas->pluck('id')->all() === [$this->kelasAmpuan->id])
            // Data jadwal mentah hanya boleh memuat mapel milik guru ini
            ->assertSee('data-mapel="Sejarah Kebudayaan Islam"', false)
            ->assertDontSee('data-mapel="Matematika Wajib"', false);
    }

    public function test_form_absensi_mengajar_menampilkan_mapel_otomatis_dan_tombol_simpan_nonaktif(): void
    {
        $this->actingAs($this->guru)
            ->get(route('guru.absensi-mengajar'))
            ->assertOk()
            ->assertSee('id="inputMataPelajaran"', false)
            ->assertDontSee('id="selectMapelKelas"', false)
            ->assertSee('id="jamKeWaktuWarning" class="alert alert-danger d-none', false)
            ->assertSee('Waktu mengajar tidak sesuai jadwal. Anda tidak bisa melanjutkan Absensi Mengajar.', false)
            ->assertSee('class="btn btn-danger rounded-pill px-4 py-2 fw-bold shadow-sm" disabled', false);
    }

    public function test_pilihan_jam_hanya_memuat_jam_jadwal_guru_pada_hari_ini(): void
    {
        Jadwal::create([
            'tahun_pelajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'kelas' => $this->kelasAmpuan->id,
            'hari' => 'Kamis',
            'jam_ke' => '2',
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '08:45:00',
            'mata_pelajaran' => $this->mapelAmpuan->mata_pelajaran,
            'nama_pengajar' => $this->guru->id,
            'mapel_id' => $this->mapelAmpuan->id,
        ]);

        Jadwal::create([
            'tahun_pelajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'kelas' => $this->kelasAmpuan->id,
            'hari' => 'Jumat',
            'jam_ke' => '3',
            'waktu_mulai' => '08:45:00',
            'waktu_selesai' => '09:30:00',
            'mata_pelajaran' => $this->mapelAmpuan->mata_pelajaran,
            'nama_pengajar' => $this->guru->id,
            'mapel_id' => $this->mapelAmpuan->id,
        ]);

        $this->actingAs($this->guru)
            ->get(route('guru.absensi-mengajar'))
            ->assertOk()
            ->assertSee('name="jam_ke[]" value="1"', false)
            ->assertSee('name="jam_ke[]" value="2"', false)
            ->assertSee('jam-ke-option d-none', false)
            ->assertSee('Scan QR Code kelas terlebih dahulu.')
            ->assertSee('data-server-time="07:16:00"', false)
            ->assertSee('data-waktu-mulai="07:15:00"', false)
            ->assertSee('data-waktu-selesai="08:00:00"', false)
            ->assertSee('id="inputKelasMengajar"', false)
            ->assertDontSee('id="selectKelasMengajar"', false)
            ->assertSee('id="readerKelas"', false)
            ->assertSee('Ambil Gambar Kelas')
            ->assertSee('id="inputFotoKelas"', false)
            ->assertSee('id="btnStartCameraFotoKelas"', false)
            ->assertDontSee('name="jam_ke[]" value="3"', false);
    }

    public function test_guru_tidak_bisa_menyimpan_jam_yang_tidak_ada_di_jadwal_hari_ini(): void
    {
        Jadwal::create([
            'tahun_pelajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'kelas' => $this->kelasAmpuan->id,
            'hari' => 'Jumat',
            'jam_ke' => '3',
            'waktu_mulai' => '08:45:00',
            'waktu_selesai' => '09:30:00',
            'mata_pelajaran' => $this->mapelAmpuan->mata_pelajaran,
            'nama_pengajar' => $this->guru->id,
            'mapel_id' => $this->mapelAmpuan->id,
        ]);

        $this->actingAs($this->guru)
            ->postAbsensiMengajarJson([
                'kelas' => $this->kelasAmpuan->id,
                'jam_ke' => ['3'],
                'qr_kelas' => 'KELAS:'.$this->kelasAmpuan->id,
                'mata_pelajaran' => 'Sejarah Kebudayaan Islam',
                'materi_pembelajaran' => 'Peradaban Islam',
                'tanggal' => '2026-11-05',
                'hari' => 'Kamis',
            ])
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Jam yang dipilih tidak sesuai dengan jadwal mengajar Anda di kelas XI A4 hari ini.');

        $this->assertSame(0, AbsensiGuru::count());
    }

    public function test_guru_tidak_bisa_menyimpan_mapel_milik_guru_lain(): void
    {
        $this->actingAs($this->guru)
            ->postAbsensiMengajarJson([
                'kelas' => $this->kelasAmpuan->id,
                'jam_ke' => ['1'],
                'qr_kelas' => 'KELAS:'.$this->kelasAmpuan->id,
                'mata_pelajaran' => 'Matematika Wajib',
                'materi_pembelajaran' => 'Persamaan Linear',
                'tanggal' => '2026-11-05',
                'hari' => 'Kamis',
            ])
            ->assertStatus(403)
            ->assertJson(['success' => false]);

        $this->assertSame(0, AbsensiGuru::count());
    }

    public function test_guru_tidak_bisa_menyimpan_pada_kelas_yang_tidak_diampu(): void
    {
        $this->actingAs($this->guru)
            ->postAbsensiMengajarJson([
                'kelas' => $this->kelasLain->id,
                'jam_ke' => ['1'],
                'qr_kelas' => 'KELAS:'.$this->kelasLain->id,
                'mata_pelajaran' => 'Sejarah Kebudayaan Islam',
                'materi_pembelajaran' => 'Materi',
                'tanggal' => '2026-11-05',
                'hari' => 'Kamis',
            ])
            ->assertStatus(403);

        $this->assertSame(0, AbsensiGuru::count());
    }

    public function test_guru_tetap_bisa_menyimpan_mapel_yang_diampu(): void
    {
        $this->actingAs($this->guru)
            ->postAbsensiMengajarJson([
                'kelas' => $this->kelasAmpuan->id,
                'jam_ke' => ['1'],
                'qr_kelas' => 'KELAS:'.$this->kelasAmpuan->id,
                'mata_pelajaran' => 'Sejarah Kebudayaan Islam',
                'materi_pembelajaran' => 'Peradaban Islam',
                'tanggal' => '2026-11-05',
                'hari' => 'Kamis',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('absensi_gurus', [
            'kelas' => $this->kelasAmpuan->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran' => 'Sejarah Kebudayaan Islam',
        ]);
    }

    public function test_guru_ditolak_setelah_waktu_selesai_jam_terakhir_terpilih(): void
    {
        $this->travelTo('2026-11-05 08:01:00');

        $this->actingAs($this->guru)
            ->postAbsensiMengajarJson([
                'kelas' => $this->kelasAmpuan->id,
                'jam_ke' => ['1'],
                'qr_kelas' => 'KELAS:'.$this->kelasAmpuan->id,
                'mata_pelajaran' => 'Sejarah Kebudayaan Islam',
                'materi_pembelajaran' => 'Peradaban Islam',
                'tanggal' => '2026-11-05',
                'hari' => 'Kamis',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Waktu mengajar tidak sesuai jadwal.');

        $this->assertSame(0, AbsensiGuru::count());
    }

    public function test_guru_ditolak_jika_waktu_server_belum_mencapai_jam_mulai_jadwal(): void
    {
        $this->travelTo('2026-11-05 07:14:00');

        $this->actingAs($this->guru)
            ->postAbsensiMengajarJson([
                'kelas' => $this->kelasAmpuan->id,
                'jam_ke' => ['1'],
                'qr_kelas' => 'KELAS:'.$this->kelasAmpuan->id,
                'mata_pelajaran' => 'Sejarah Kebudayaan Islam',
                'materi_pembelajaran' => 'Peradaban Islam',
                'tanggal' => '2026-11-05',
                'hari' => 'Kamis',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Waktu mengajar tidak sesuai jadwal.');

        $this->assertSame(0, AbsensiGuru::count());
    }

    public function test_guru_wajib_memindai_qr_dan_mengisi_materi_pembelajaran(): void
    {
        $this->actingAs($this->guru)
            ->postAbsensiMengajarJson([
                'kelas' => $this->kelasAmpuan->id,
                'jam_ke' => ['1'],
                'materi_pembelajaran' => '',
                'tanggal' => '2026-11-05',
                'hari' => 'Kamis',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['qr_kelas', 'materi_pembelajaran']);

        $this->assertSame(0, AbsensiGuru::count());
    }

    public function test_guru_wajib_mengunggah_foto_kelas_sebelum_menyimpan(): void
    {
        $this->actingAs($this->guru)
            ->postJson(route('guru.absensi-mengajar.store'), [
                'kelas' => $this->kelasAmpuan->id,
                'jam_ke' => ['1'],
                'qr_kelas' => 'KELAS:'.$this->kelasAmpuan->id,
                'mata_pelajaran' => 'Sejarah Kebudayaan Islam',
                'materi_pembelajaran' => 'Peradaban Islam',
                'tanggal' => '2026-11-05',
                'hari' => 'Kamis',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['foto'])
            ->assertJsonPath('errors.foto.0', 'Foto kelas wajib diambil sebelum menyimpan absensi.');

        $this->assertSame(0, AbsensiGuru::count());
    }

    public function test_guru_ditolak_jika_qr_kelas_tidak_sesuai_dengan_kelas_yang_dideteksi(): void
    {
        $this->actingAs($this->guru)
            ->postAbsensiMengajarJson([
                'kelas' => $this->kelasAmpuan->id,
                'jam_ke' => ['1'],
                'qr_kelas' => 'KELAS:'.$this->kelasLain->id,
                'mata_pelajaran' => 'Sejarah Kebudayaan Islam',
                'materi_pembelajaran' => 'Peradaban Islam',
                'tanggal' => '2026-11-05',
                'hari' => 'Kamis',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'message',
                'QR Code yang dipindai (KELAS:'.$this->kelasLain->id.') tidak sesuai dengan Kelas '.$this->kelasAmpuan->kelas.'.'
            );

        $this->assertSame(0, AbsensiGuru::count());
    }

    public function test_admin_tetap_melihat_semua_kelas_dan_mapel(): void
    {
        $admin = User::create([
            'username' => 'admin_absensi_scope',
            'name' => 'Admin Absensi',
            'role' => 'admin',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($admin)
            ->get(route('guru.absensi-mengajar'))
            ->assertOk()
            ->assertViewHas('daftarKelas', fn ($kelas) => $kelas->count() === 2)
            ->assertViewHas('mapels', fn ($mapels) => $mapels->count() === 2);
    }

    public function test_guru_tidak_melihat_riwayat_absensi_mengajar_hari_ini(): void
    {
        AbsensiGuru::create([
            'kelas' => $this->kelasAmpuan->id,
            'hari' => 'Kamis',
            'tanggal' => now()->toDateString(),
            'nama_guru' => $this->guru->name,
            'guru_id' => $this->guru->id,
            'jam_ke' => '1',
            'mata_pelajaran' => 'Sejarah Kebudayaan Islam',
            'materi_pembelajaran' => 'Materi Rahasia Riwayat',
            'status' => 'Hadir',
        ]);

        $this->actingAs($this->guru)
            ->get(route('guru.absensi-mengajar'))
            ->assertOk()
            ->assertDontSee('Riwayat Absensi Mengajar Hari Ini')
            ->assertDontSee('Materi Rahasia Riwayat')
            ->assertViewHas('riwayatMengajar', fn ($riwayatMengajar) => $riwayatMengajar->isEmpty());
    }

    public function test_wali_kelas_tidak_melihat_riwayat_absensi_mengajar_hari_ini(): void
    {
        $waliKelas = User::create([
            'username' => 'wali_absensi_scope',
            'name' => 'Wali Kelas',
            'role' => 'wali_kelas',
            'password' => bcrypt('password123'),
        ]);

        AbsensiGuru::create([
            'kelas' => $this->kelasAmpuan->id,
            'hari' => 'Kamis',
            'tanggal' => now()->toDateString(),
            'nama_guru' => $this->guru->name,
            'guru_id' => $this->guru->id,
            'jam_ke' => '1',
            'mata_pelajaran' => 'Sejarah Kebudayaan Islam',
            'materi_pembelajaran' => 'Materi Rahasia Riwayat',
            'status' => 'Hadir',
        ]);

        $this->actingAs($waliKelas)
            ->get(route('guru.absensi-mengajar'))
            ->assertOk()
            ->assertDontSee('Riwayat Absensi Mengajar Hari Ini')
            ->assertDontSee('Materi Rahasia Riwayat')
            ->assertViewHas('riwayatMengajar', fn ($riwayatMengajar) => $riwayatMengajar->isEmpty());
    }

    public function test_admin_melihat_riwayat_absensi_mengajar_hari_ini(): void
    {
        $admin = User::create([
            'username' => 'admin_riwayat_mengajar',
            'name' => 'Admin Riwayat',
            'role' => 'admin',
            'password' => bcrypt('password123'),
        ]);

        AbsensiGuru::create([
            'kelas' => $this->kelasAmpuan->id,
            'hari' => 'Kamis',
            'tanggal' => now()->toDateString(),
            'nama_guru' => $this->guru->name,
            'guru_id' => $this->guru->id,
            'jam_ke' => '1',
            'mata_pelajaran' => 'Sejarah Kebudayaan Islam',
            'materi_pembelajaran' => 'Riwayat Untuk Admin',
            'status' => 'Hadir',
        ]);

        $this->actingAs($admin)
            ->get(route('guru.absensi-mengajar'))
            ->assertOk()
            ->assertSee('Riwayat Absensi Mengajar Hari Ini')
            ->assertSee('Riwayat Untuk Admin')
            ->assertViewHas('riwayatMengajar', fn ($riwayatMengajar) => $riwayatMengajar->count() === 1);
    }
}
