<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AbsensiScannerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_home_page_displays_the_absensi_scanner(): void
    {
        $this->actingAs(User::create([
            'username' => 'admin_scanner_page',
            'role' => 'admin',
            'password' => bcrypt('password123'),
        ]));

        $this->travelTo(Carbon::parse('2026-10-30 07:30:00'));
        $this->createSession();

        $this->get(route('scanner'))
            ->assertOk()
            ->assertSee('Scanner Absensi Siswa')
            ->assertSee('Kedatangan Pagi');
    }

    public function test_scanner_mutes_all_sessions_while_waiting_for_the_next_session(): void
    {
        // Sesi pagi selesai 09:00, sesi dhuhur mulai 11:30 -> saat ini jeda antar sesi pada hari sekolah
        $this->travelTo(Carbon::parse('2026-10-30 10:00:00'));
        $this->createSession();

        Livewire::test('absensi-scanner')
            ->assertSet('adaSesiAktif', false)
            ->assertSet('selectedSesi', '')
            ->assertSee('Tidak Ada Sesi Aktif');
    }

    public function test_scanner_reactivates_session_automatically_once_start_time_is_reached(): void
    {
        // Sesi pagi 07:00-09:00, sesi dhuhur 11:30-12:30 (Jumat)
        $this->travelTo(Carbon::parse('2026-10-30 10:00:00'));
        $pagiId = $this->createSession();
        $dhuhurId = $this->createSession('dhuhur', 'Sholat Dhuhur', '11:30:00', '12:00:00', '12:30:00');

        // Masih jeda: tidak ada sesi aktif, pemindaian ditolak walau sesi dipilih manual
        Livewire::test('absensi-scanner')
            ->assertSet('adaSesiAktif', false)
            ->set('selectedSesi', (string) $pagiId)
            ->call('processScan', 'UNKNOWN')
            ->assertSee('Tidak Ada Sesi Aktif');

        $this->assertDatabaseCount('absensi_siswas', 0);

        // Waktu sesi berikutnya tercapai -> sesi dhuhur otomatis aktif
        $this->travelTo(Carbon::parse('2026-10-30 11:30:00'));

        Livewire::test('absensi-scanner')
            ->assertSet('adaSesiAktif', true)
            ->assertSet('selectedSesi', (string) $dhuhurId)
            ->assertSee('Sholat Dhuhur');
    }

    public function test_duplicate_attendance_is_rejected_by_unique_constraint(): void
    {
        $this->travelTo(Carbon::parse('2026-10-30 07:30:00'));
        $sessionId = $this->createSession();
        $this->createStudent();

        $data = [
            'nis' => 'S001',
            'sesi_id' => $sessionId,
            'tanggal' => '2026-10-30',
            'jam_scan' => '07:30:00',
            'status' => 'Tepat Waktu',
        ];

        DB::table('absensi_siswas')->insert($data);

        // Insert kedua dengan kombinasi sama harus ditolak database
        $this->expectException(QueryException::class);
        DB::table('absensi_siswas')->insert($data);
    }

    public function test_scanner_records_attendance_and_rejects_duplicate_scans(): void
    {
        $this->travelTo(Carbon::parse('2026-10-30 07:30:00'));
        $sessionId = $this->createSession();
        $this->createStudent();

        Livewire::test('absensi-scanner')
            ->set('selectedSesi', (string) $sessionId)
            ->call('processScan', 'S001')
            ->assertSee('Siti Aminah')
            ->assertSee('Status: Tepat Waktu')
            ->call('processScan', 'S001')
            ->assertSee('sudah melakukan absensi pada sesi ini!');

        $this->assertDatabaseCount('absensi_siswas', 1);
        $this->assertDatabaseHas('absensi_siswas', [
            'nis' => 'S001',
            'sesi_id' => $sessionId,
            'tanggal' => '2026-10-30',
            'jam_scan' => '07:30:00',
            'status' => 'Tepat Waktu',
        ]);
    }

    public function test_scanner_marks_attendance_as_late_after_the_cutoff(): void
    {
        $this->travelTo(Carbon::parse('2026-10-30 08:01:00'));
        $sessionId = $this->createSession();
        $this->createStudent();

        Livewire::test('absensi-scanner')
            ->set('selectedSesi', (string) $sessionId)
            ->call('processScan', 'S001')
            ->assertSee('Status: Terlambat');

        $this->assertDatabaseHas('absensi_siswas', [
            'nis' => 'S001',
            'sesi_id' => $sessionId,
            'status' => 'Terlambat',
        ]);
    }

    public function test_scanner_rejects_an_unknown_student_number(): void
    {
        $this->travelTo(Carbon::parse('2026-10-30 07:30:00'));
        $sessionId = $this->createSession();

        Livewire::test('absensi-scanner')
            ->set('selectedSesi', (string) $sessionId)
            ->call('processScan', 'UNKNOWN')
            ->assertSee('NIS: UNKNOWN tidak ditemukan');

        $this->assertDatabaseCount('absensi_siswas', 0);
    }

    public function test_scanner_mutes_and_rejects_on_saturday_due_to_five_day_school_week(): void
    {
        // 2026-10-31 adalah hari Sabtu
        $this->travelTo(Carbon::parse('2026-10-31 07:30:00'));
        $sessionId = $this->createSession();
        $this->createStudent();

        Livewire::test('absensi-scanner')
            ->assertSet('adaSesiAktif', false)
            ->assertSet('selectedSesi', '')
            ->assertSee('Libur Akhir Pekan (Sabtu')
            ->call('processScan', 'S001')
            ->assertSee('Hari ini libur: Libur Akhir Pekan (Sabtu');

        $this->assertDatabaseCount('absensi_siswas', 0);
    }

    private function createSession(
        string $kode = 'pagi',
        string $nama = 'Kedatangan Pagi',
        string $mulai = '07:00:00',
        string $terlambat = '08:00:00',
        string $selesai = '09:00:00',
    ): int {
        return (int) DB::table('sesi_absensis')->insertGetId([
            'kode_sesi' => $kode,
            'nama_sesi' => $nama,
            'jam_mulai' => $mulai,
            'jam_terlambat' => $terlambat,
            'jam_selesai' => $selesai,
            'is_aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createStudent(): void
    {
        DB::table('siswas')->insert([
            'nis' => 'S001',
            'nama' => 'Siti Aminah',
            'kelas' => 'X IPA 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
