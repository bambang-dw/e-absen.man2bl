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
use Tests\TestCase;

class AbsensiMengajarDuplicateTest extends TestCase
{
    use RefreshDatabase;

    protected User $guru;

    protected Kelas $kelas;

    protected Kelas $kelasLain;

    protected Mapel $mapel;

    protected Mapel $mapelLain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-05 10:04:00');
        Storage::fake('foto_guru');

        $this->guru = User::create([
            'username' => 'guru_duplikat',
            'name' => 'Drs. Ahmad Subarjo',
            'role' => 'guru',
            'password' => bcrypt('password123'),
        ]);

        $this->kelas = Kelas::create(['tahun_pelajaran' => '2026/2027', 'kelas' => 'X IPA 1']);
        $this->kelasLain = Kelas::create(['tahun_pelajaran' => '2026/2027', 'kelas' => 'X IPA 2']);

        JamBelajar::create(['jam_ke' => '5', 'waktu_mulai' => '10:00:00', 'waktu_selesai' => '10:45:00']);

        $this->mapel = Mapel::create([
            'kode_mapel' => 'MTK-W',
            'kelas' => $this->kelas->id,
            'mata_pelajaran' => 'Matematika Wajib',
            'nama_pengajar' => $this->guru->id,
        ]);

        $this->mapelLain = Mapel::create([
            'kode_mapel' => 'MTK-W2',
            'kelas' => $this->kelasLain->id,
            'mata_pelajaran' => 'Matematika Wajib',
            'nama_pengajar' => $this->guru->id,
        ]);

        // Jadwal mengajar: guru hanya boleh mencatat kelas & mapel yang diampu
        foreach ([$this->kelas, $this->kelasLain] as $kelas) {
            Jadwal::create([
                'tahun_pelajaran' => '2026/2027',
                'semester' => 'Ganjil',
                'kelas' => $kelas->id,
                'hari' => 'Kamis',
                'jam_ke' => '5',
                'waktu_mulai' => '10:00:00',
                'waktu_selesai' => '10:45:00',
                'mata_pelajaran' => 'Matematika Wajib',
                'nama_pengajar' => $this->guru->id,
                'mapel_id' => $kelas->id === $this->kelas->id ? $this->mapel->id : $this->mapelLain->id,
            ]);
        }

        Jadwal::create([
            'tahun_pelajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'kelas' => $this->kelas->id,
            'hari' => 'Kamis',
            'jam_ke' => '6',
            'waktu_mulai' => '10:45:00',
            'waktu_selesai' => '11:30:00',
            'mata_pelajaran' => 'Matematika Wajib',
            'nama_pengajar' => $this->guru->id,
            'mapel_id' => $this->mapel->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        $payload = array_merge([
            'kelas' => $this->kelas->id,
            'jam_ke' => ['5'],
            'mata_pelajaran' => $this->mapel->mata_pelajaran,
            'materi_pembelajaran' => 'Fungsi Kuadrat',
            'foto' => UploadedFile::fake()->image('foto-kelas.jpg'),
            'tanggal' => '2026-11-05',
            'hari' => 'Kamis',
        ], $overrides);
        $kelas = $payload['kelas'] === $this->kelasLain->id ? $this->kelasLain : $this->kelas;
        $payload['qr_kelas'] = 'KELAS:'.$kelas->id;

        return $payload;
    }

    public function test_scan_ganda_tidak_menyimpan_absensi_duplikat(): void
    {
        $this->actingAs($this->guru);

        // Pemindaian pertama tersimpan
        $this->post(route('guru.absensi-mengajar.store'), $this->payload())
            ->assertRedirect()
            ->assertSessionHas('success');

        // Pemindaian kedua untuk kelas & jam yang sama ditolak
        $this->post(route('guru.absensi-mengajar.store'), $this->payload())
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertSame(1, AbsensiGuru::count());
        $this->assertDatabaseHas('absensi_gurus', [
            'kelas' => $this->kelas->id,
            'guru_id' => $this->guru->id,
            'jam_ke' => '5',
            'tanggal' => '2026-11-05 00:00:00',
        ]);

        $record = AbsensiGuru::firstOrFail();
        $this->assertNotEmpty($record->foto);
        Storage::disk('foto_guru')->assertExists($record->foto);
    }

    public function test_scan_ganda_mengembalikan_409_untuk_request_ajax(): void
    {
        $this->actingAs($this->guru);

        $this->post(route('guru.absensi-mengajar.store'), $this->payload(), ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->post(route('guru.absensi-mengajar.store'), $this->payload(), ['Accept' => 'application/json'])
            ->assertStatus(409)
            ->assertJson(['success' => false, 'duplicate' => true]);

        $this->assertSame(1, AbsensiGuru::count());
    }

    public function test_jam_yang_sama_tetap_boleh_dicatat_untuk_kelas_berbeda(): void
    {
        $this->actingAs($this->guru);

        $this->post(route('guru.absensi-mengajar.store'), $this->payload())->assertSessionHas('success');
        $this->post(route('guru.absensi-mengajar.store'), $this->payload([
            'kelas' => $this->kelasLain->id,
            'mata_pelajaran' => $this->mapelLain->mata_pelajaran,
        ]))->assertSessionHas('success');

        $this->assertSame(2, AbsensiGuru::count());
    }

    public function test_bentrok_sebagian_jam_pada_record_multi_jam_ditolak(): void
    {
        $this->actingAs($this->guru);

        $this->post(route('guru.absensi-mengajar.store'), $this->payload(['jam_ke' => ['5', '6']]))
            ->assertSessionHas('success');

        $this->travelTo('2026-11-05 10:46:00');

        // Hanya jam 6 yang bentrok -> harus ditolak
        $this->post(route('guru.absensi-mengajar.store'), $this->payload(['jam_ke' => ['6']]))
            ->assertSessionHas('warning');

        $this->assertSame(1, AbsensiGuru::count());
        $this->assertDatabaseHas('absensi_gurus', ['jam_ke' => '5,6']);
    }

    public function test_pencatatan_admin_tidak_membuat_duplikat(): void
    {
        $admin = User::create([
            'username' => 'admin_absensi',
            'name' => 'Admin Absensi',
            'role' => 'admin',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($admin);

        $payload = [
            'hari' => 'Kamis',
            'tanggal' => '2026-11-05',
            'nama_guru' => $this->guru->name,
            'jam_ke' => '5',
            'mata_pelajaran' => 'Matematika Wajib',
            'materi_pembelajaran' => 'Fungsi Kuadrat',
            'status' => 'Hadir',
        ];

        $this->post(route('jadwal.absensi-guru.store'), $payload)->assertSessionHas('success');
        $this->post(route('jadwal.absensi-guru.store'), $payload)->assertSessionHas('warning');

        $this->assertSame(1, AbsensiGuru::count());
        $this->assertEquals($this->guru->id, AbsensiGuru::first()->guru_id);
    }

    public function test_dashboard_guru_menampilkan_status_jam_yang_belum_tercatat(): void
    {
        $this->actingAs($this->guru);

        // Belum ada yang tercatat -> tampil peringatan
        $this->get(route('guru.dashboard'))
            ->assertOk()
            ->assertSee('Jam Belum Tercatat')
            ->assertSee('Absensi Mengajar');

        // Setelah seluruh jadwal hari ini dicatat -> berubah menjadi lengkap
        foreach ([$this->kelas, $this->kelasLain] as $kelas) {
            AbsensiGuru::create([
                'kelas' => $kelas->id,
                'hari' => 'Kamis',
                'tanggal' => now()->toDateString(),
                'nama_guru' => $this->guru->name,
                'guru_id' => $this->guru->id,
                'jam_ke' => '5',
                'mata_pelajaran' => 'Matematika Wajib',
                'status' => 'Hadir',
            ]);
        }

        $this->get(route('guru.dashboard'))
            ->assertOk()
            ->assertSee('Semua Jam Sudah Tercatat');
    }
}
