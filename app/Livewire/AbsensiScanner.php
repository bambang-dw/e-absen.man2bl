<?php

namespace App\Livewire;

use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\SesiAbsensi;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AbsensiScanner extends Component
{
    public $sesis;

    public $selectedSesi = '';

    public $alertMessage = '';

    public $alertType = 'info'; // success, warning, danger

    public $lastSiswa = null;

    /**
     * Nama sesi yang otomatis aktif pada waktu saat ini (null jika sedang jeda antar sesi).
     */
    public $namaSesiAktif = null;

    /**
     * Apakah saat ini berada dalam rentang waktu sesi aktif.
     */
    public $adaSesiAktif = false;

    /**
     * Keterangan hari libur jika hari ini hari libur.
     */
    public $keteranganLibur = null;

    public function mount()
    {
        $this->sesis = SesiAbsensi::where('is_aktif', true)->orderBy('jam_mulai')->orderBy('id')->get();
        $this->sinkronkanSesi();
    }

    /**
     * Sinkronkan sesi terpilih dengan sesi yang sedang berlangsung.
     * Jika hari ini libur atau tidak ada sesi yang berlangsung, pilihannya dikosongkan (mute).
     */
    public function sinkronkanSesi(): void
    {
        $statusLibur = HariLibur::statusLibur();
        if ($statusLibur['is_libur']) {
            $this->keteranganLibur = $statusLibur['keterangan'];
            $this->adaSesiAktif = false;
            $this->selectedSesi = '';
            $this->namaSesiAktif = null;

            return;
        }

        $this->keteranganLibur = null;
        $sesiAktif = SesiAbsensi::getSesiAktif();
        $this->adaSesiAktif = $sesiAktif !== null;

        if (! $sesiAktif) {
            // Jeda antar sesi: mute seluruh sesi
            $this->selectedSesi = '';
            $this->namaSesiAktif = null;

            return;
        }

        $this->selectedSesi = $sesiAktif->id;
        $this->namaSesiAktif = $sesiAktif->nama_sesi;
    }

    /**
     * Dipanggil berkala dari frontend untuk mengecek pergantian sesi.
     * Sesi otomatis aktif kembali begitu jam_mulai sesi berikutnya tercapai.
     */
    public function periksaSesi(): void
    {
        $sesiAktifSebelumnya = $this->selectedSesi;
        $this->sesis = SesiAbsensi::where('is_aktif', true)->orderBy('jam_mulai')->orderBy('id')->get();
        $this->sinkronkanSesi();

        if ((string) $sesiAktifSebelumnya !== (string) $this->selectedSesi) {
            $this->showAlert(
                $this->adaSesiAktif
                    ? "Sesi aktif: {$this->namaSesiAktif}. Silakan lakukan pemindaian."
                    : 'Tidak Ada Sesi Aktif. Menunggu sesi berikutnya dimulai.',
                $this->adaSesiAktif ? 'success' : 'warning'
            );

            $this->dispatch('sesi-diubah', adaSesiAktif: $this->adaSesiAktif);
        }
    }

    public function processScan($nis)
    {
        // Pastikan sesi yang dipakai selalu sesi yang sedang berlangsung
        $this->sinkronkanSesi();

        if ($this->keteranganLibur) {
            $this->showAlert("Hari ini libur: {$this->keteranganLibur}. Pemindaian presensi dinonaktifkan.", 'danger');
            $this->dispatch('play-sound', type: 'danger');

            return ['success' => false, 'message' => 'Hari Libur'];
        }

        if (! $this->adaSesiAktif || ! $this->selectedSesi) {
            $this->showAlert('Tidak Ada Sesi Aktif. Pemindaian dinonaktifkan sampai sesi berikutnya dimulai.', 'danger');
            $this->dispatch('play-sound', type: 'danger');

            return ['success' => false, 'message' => 'Tidak Ada Sesi Aktif'];
        }

        // 1. Cek apakah siswa terdaftar berdasarkan NISN atau NIS (QR Code)
        $siswa = Siswa::where('nisn', $nis)->orWhere('nis', $nis)->first();
        if (! $siswa) {
            $this->showAlert("NIS: {$nis} tidak ditemukan dalam database!", 'danger');
            $this->dispatch('play-sound', type: 'danger');

            return ['success' => false, 'message' => "NIS: {$nis} tidak ditemukan"];
        }

        $sesi = SesiAbsensi::find($this->selectedSesi);
        if (! $sesi) {
            $this->showAlert('Sesi absensi tidak valid atau telah dinonaktifkan!', 'danger');
            $this->dispatch('play-sound', type: 'danger');

            return ['success' => false, 'message' => 'Sesi absensi tidak valid'];
        }

        $tanggalHariIni = Carbon::now()->toDateString();
        $jamSekarang = Carbon::now()->toTimeString();

        // 2. Cek apakah siswa sudah absen di sesi dan hari yang sama (mencegah absen ganda)
        $sudahAbsen = Absensi::where('nis', $siswa->nis)
            ->where('sesi_id', $this->selectedSesi)
            ->where('tanggal', $tanggalHariIni)
            ->exists();

        if ($sudahAbsen) {
            $this->showAlert("Siswa {$siswa->nama} ({$siswa->kelas}) sudah melakukan absensi pada sesi ini!", 'warning');
            $this->lastSiswa = $siswa;
            $this->dispatch('play-sound', type: 'warning');

            return ['success' => false, 'message' => 'Sudah absen', 'siswa' => $siswa];
        }

        // 3. Tentukan status kehadiran berdasarkan aturan waktu sesi
        $status = 'Tepat Waktu';
        if ($jamSekarang > $sesi->jam_terlambat && $jamSekarang <= $sesi->jam_selesai) {
            $status = 'Terlambat';
        } elseif ($jamSekarang > $sesi->jam_selesai) {
            $status = 'Alfa'; // Atau lewat dari batas sesi
        }

        // 4. Simpan ke database dengan mencatat identitas petugas (guru/admin) yang memindai.
        // Unique constraint (nis, sesi_id, tanggal) melindungi dari duplikat akibat scan bersamaan.
        try {
            Absensi::create([
                'nis' => $siswa->nis,
                'sesi_id' => $sesi->id,
                'user_id' => Auth::id(),
                'tanggal' => $tanggalHariIni,
                'jam_scan' => $jamSekarang,
                'status' => $status,
            ]);
        } catch (QueryException $e) {
            if (Absensi::isDuplicateEntry($e)) {
                $this->showAlert("Siswa {$siswa->nama} ({$siswa->kelas}) sudah melakukan absensi pada sesi ini!", 'warning');
                $this->lastSiswa = $siswa;
                $this->dispatch('play-sound', type: 'warning');

                return ['success' => false, 'message' => 'Sudah absen', 'siswa' => $siswa];
            }

            throw $e;
        }

        $this->lastSiswa = $siswa;
        $this->showAlert("Berhasil! {$siswa->nama} ({$siswa->kelas}) - Status: {$status}", 'success');

        // Kirim event ke frontend untuk memicu suara beep sukses
        $this->dispatch('play-sound', type: 'success');

        return [
            'success' => true,
            'status' => $status,
            'nama' => $siswa->nama,
            'kelas' => $siswa->kelas,
        ];
    }

    private function showAlert($msg, $type)
    {
        $this->alertMessage = $msg;
        $this->alertType = $type;
    }

    public function render()
    {
        return view('livewire.absensi-scanner');
    }
}
