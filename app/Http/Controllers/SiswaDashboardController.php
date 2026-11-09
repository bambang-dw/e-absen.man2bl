<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SiswaDashboardController extends Controller
{
    /**
     * Display the student attendance dashboard.
     */
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();

        // Cari data siswa yang bersesuaian dengan akun login
        $siswa = null;
        if ($user) {
            $siswa = $user->siswa ?? Siswa::where('nis', $user->username)
                ->orWhere('nisn', $user->username)
                ->first();
        }

        // Fallback untuk testing atau preview
        if (! $siswa) {
            $siswa = Siswa::first();
        }

        if (! $siswa) {
            return redirect()->route('login')->with('error', 'Data siswa tidak ditemukan.');
        }

        $now = Carbon::now();

        // 1. REKAP BULAN BERJALAN
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();
        $namaBulan = $now->translatedFormat('F Y');

        $absensiBulan = Absensi::with('sesi')
            ->where('nis', $siswa->nis)
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam_scan', 'desc')
            ->get();

        $bulanTepatWaktu = $absensiBulan->where('status', 'Tepat Waktu')->count();
        $bulanTerlambat = $absensiBulan->where('status', 'Terlambat')->count();
        $bulanIzin = $absensiBulan->filter(fn ($a) => in_array($a->status, ['Izin', 'Ijin']))->count();
        $bulanSakit = $absensiBulan->where('status', 'Sakit')->count();
        $bulanAlfa = $absensiBulan->filter(fn ($a) => in_array($a->status, ['Alfa', 'Alpha']))->count();
        $bulanTotalHadir = $bulanTepatWaktu + $bulanTerlambat;

        // Total hari efektif aktif di bulan ini (hari kerja Senin-Jumat hingga hari ini)
        $hariEfektifBulanIni = max(1, $now->copy()->startOfMonth()->diffInDaysFiltered(function (Carbon $date) {
            return ! $date->isWeekend();
        }, $now) + 1);

        $bulanPersentase = round(($bulanTotalHadir / max(1, $hariEfektifBulanIni)) * 100, 1);
        $bulanPersentase = min(100, $bulanPersentase);

        // 2. REKAP SATU SEMESTER
        // Semester Ganjil: Juli - Desember | Semester Genap: Januari - Juni
        $currentMonth = $now->month;
        if ($currentMonth >= 7) {
            $startOfSemester = Carbon::create($now->year, 7, 1)->toDateString();
            $endOfSemester = Carbon::create($now->year, 12, 31)->toDateString();
            $namaSemester = 'Semester Ganjil TA '.$now->year.'/'.($now->year + 1);
        } else {
            $startOfSemester = Carbon::create($now->year, 1, 1)->toDateString();
            $endOfSemester = Carbon::create($now->year, 6, 30)->toDateString();
            $namaSemester = 'Semester Genap TA '.($now->year - 1).'/'.$now->year;
        }

        $absensiSemester = Absensi::where('nis', $siswa->nis)
            ->whereBetween('tanggal', [$startOfSemester, $endOfSemester])
            ->get();

        $semesterTepatWaktu = $absensiSemester->where('status', 'Tepat Waktu')->count();
        $semesterTerlambat = $absensiSemester->where('status', 'Terlambat')->count();
        $semesterIzin = $absensiSemester->filter(fn ($a) => in_array($a->status, ['Izin', 'Ijin']))->count();
        $semesterSakit = $absensiSemester->where('status', 'Sakit')->count();
        $semesterAlfa = $absensiSemester->filter(fn ($a) => in_array($a->status, ['Alfa', 'Alpha']))->count();
        $semesterTotalHadir = $semesterTepatWaktu + $semesterTerlambat;

        $hariEfektifSemester = max(1, Carbon::parse($startOfSemester)->diffInDaysFiltered(function (Carbon $date) {
            return ! $date->isWeekend();
        }, $now) + 1);

        $semesterPersentase = round(($semesterTotalHadir / max(1, $hariEfektifSemester)) * 100, 1);
        $semesterPersentase = min(100, $semesterPersentase);

        // QR Code value = NISN siswa (fallback NIS)
        $qrValue = $siswa->nisn ?: $siswa->nis;

        return view('siswa-dashboard.index', compact(
            'siswa',
            'qrValue',
            'namaBulan',
            'bulanTepatWaktu',
            'bulanTerlambat',
            'bulanIzin',
            'bulanSakit',
            'bulanAlfa',
            'bulanTotalHadir',
            'bulanPersentase',
            'absensiBulan',
            'namaSemester',
            'semesterTepatWaktu',
            'semesterTerlambat',
            'semesterIzin',
            'semesterSakit',
            'semesterAlfa',
            'semesterTotalHadir',
            'semesterPersentase'
        ));
    }

    /**
     * Upload and update student photo, storing it directly into folder FotoSiswa.
     */
    public function uploadFoto(Request $request): RedirectResponse
    {
        $request->validate([
            'foto' => ['required', 'file', 'mimes:jpeg,png,jpg,webp,gif,bmp,avif,heic', 'max:5120'],
        ], [
            'foto.required' => 'Pilih foto terlebih dahulu.',
            'foto.file' => 'File foto tidak valid.',
            'foto.mimes' => 'Format foto harus berupa JPG, JPEG, PNG, atau WebP.',
            'foto.max' => 'Ukuran foto maksimal 5MB.',
        ]);

        $user = Auth::user();
        $siswa = $user?->siswa ?? Siswa::where('nis', $user?->username)
            ->orWhere('nisn', $user?->username)
            ->first();

        if (! $siswa) {
            $siswa = Siswa::first();
        }

        if (! $siswa) {
            return back()->with('error', 'Data siswa tidak ditemukan.');
        }

        $file = $request->file('foto');
        if (! $file || ! $file->isValid()) {
            return back()->with('error', 'Gagal mengunggah file foto. Silakan pilih file foto yang lain.');
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: ($file->extension() ?: 'jpg'));
        $safeNis = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $siswa->nis) ?: $siswa->id;
        $filename = 'siswa_'.$safeNis.'_'.time().'.'.$ext;

        // 1. Pastikan folder FotoSiswa tersedia di public dan storage
        $publicFotoPath = public_path('FotoSiswa');
        $storageFotoPath = storage_path('app/public/FotoSiswa');

        if (! is_dir($publicFotoPath)) {
            @mkdir($publicFotoPath, 0777, true);
        }
        if (! is_dir($storageFotoPath)) {
            @mkdir($storageFotoPath, 0777, true);
        }

        // Hapus file foto lama jika ada
        $siswa->deleteExistingFoto();

        // 2. Simpan file ke dalam folder public/FotoSiswa & storage/app/public/FotoSiswa
        $file->move($publicFotoPath, $filename);
        @copy($publicFotoPath.DIRECTORY_SEPARATOR.$filename, $storageFotoPath.DIRECTORY_SEPARATOR.$filename);

        // 3. Update path foto siswa di database
        $siswa->foto = 'FotoSiswa/'.$filename;
        $siswa->save();

        return back()->with('success', 'Foto profil berhasil diunggah dan langsung ditampilkan pada kartu absen digital serta dashboard admin!');
    }

    /**
     * Simpan atau perbarui Nomor HP Siswa dan Nomor HP Orang Tua di tabel siswas.
     */
    public function updateKontak(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'no_hp_siswa' => ['nullable', 'string', 'max:20'],
            'no_hp_ortu' => ['nullable', 'string', 'max:20'],
        ], [
            'no_hp_siswa.max' => 'Nomor HP Siswa maksimal 20 karakter.',
            'no_hp_ortu.max' => 'Nomor HP Orang Tua maksimal 20 karakter.',
        ]);

        $user = Auth::user();
        $siswa = $user?->siswa ?? Siswa::where('nis', $user?->username)
            ->orWhere('nisn', $user?->username)
            ->first();

        if (! $siswa) {
            $siswa = Siswa::first();
        }

        if (! $siswa) {
            return back()->with('error', 'Data siswa tidak ditemukan.');
        }

        $siswa->no_hp_siswa = ! empty($validated['no_hp_siswa']) ? trim($validated['no_hp_siswa']) : null;
        $siswa->no_hp_ortu = ! empty($validated['no_hp_ortu']) ? trim($validated['no_hp_ortu']) : null;
        $siswa->save();

        return back()->with('success', 'Data Nomor HP Siswa dan Nomor HP Orang Tua berhasil disimpan!');
    }

    /**
     * Handle student password change.
     */
    public function gantiPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password_lama' => ['required', 'string'],
            'password_baru' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password_lama.required' => 'Password saat ini / password lama wajib diisi.',
            'password_baru.required' => 'Password baru wajib diisi.',
            'password_baru.min' => 'Password baru minimal 6 karakter.',
            'password_baru.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $siswa = $user->siswa ?? Siswa::where('nis', $user->username)
            ->orWhere('nisn', $user->username)
            ->first();

        $defaultPassword = $siswa ? ($siswa->nisn ?: $siswa->nis) : null;

        // Validasi kecocokan password lama dengan hash saat ini ATAU default password (NISN)
        $isOldValid = Hash::check($request->password_lama, $user->password)
            || ($defaultPassword && $request->password_lama === $defaultPassword);

        if (! $isOldValid) {
            return back()->withErrors(['password_lama' => 'Password lama tidak cocok. (Password awal adalah nomor NISN Anda).']);
        }

        // Simpan password baru dan tandai waktu perubahan
        $user->password = Hash::make($request->password_baru);
        $user->password_changed_at = now();
        $user->save();

        return back()->with('success', 'Password akun berhasil diubah! Silakan gunakan password baru ini saat login berikutnya.');
    }
}
