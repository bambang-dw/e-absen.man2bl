<?php

use App\Http\Controllers\AbsensiMengajarController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardMengajarController;
use App\Http\Controllers\GuruDashboardController;
use App\Http\Controllers\HariLiburController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\JamBelajarController;
use App\Http\Controllers\JurnalMengajarController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\MapelController;
use App\Http\Controllers\PengaturanAkunController;
use App\Http\Controllers\PengaturanAplikasiController;
use App\Http\Controllers\PengaturanWaktuController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\RekapitulasiController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\SiswaDashboardController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Pengalihan Akses Utama Situs ke Halaman Login (atau dashboard sesuai role jika sudah login)
Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->hasRole('admin')) {
            return redirect()->route('dashboard');
        }
        if ($user->hasRole('wali_kelas')) {
            return redirect()->route('walikelas.dashboard');
        }
        if ($user->hasRole('guru')) {
            return redirect()->route('guru.dashboard');
        }
        if ($user->hasRole('siswa')) {
            return redirect()->route('siswa.dashboard');
        }

        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

// Halaman Login & Logout (Clear Look Theme dengan Pilihan Role: Siswa, Guru, Wali Kelas, Admin)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::middleware('auth')->group(function () {
    Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard Administrator (MAN 2 Bandar Lampung)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard-mengajar', [DashboardMengajarController::class, 'index'])->name('dashboard.mengajar');
    Route::post('/dashboard/pulang-awal', [DashboardController::class, 'pushPulangAwal'])->name('dashboard.pulang-awal');
    Route::post('/dashboard/batalkan-pulang-awal', [DashboardController::class, 'batalkanPulangAwal'])->name('dashboard.batalkan-pulang-awal');

    // Dashboard Khusus Siswa
    Route::get('/siswa/dashboard', [SiswaDashboardController::class, 'index'])->name('siswa.dashboard');
    Route::post('/siswa/upload-foto', [SiswaDashboardController::class, 'uploadFoto'])->name('siswa.upload-foto');
    Route::post('/siswa/update-kontak', [SiswaDashboardController::class, 'updateKontak'])->name('siswa.update-kontak');
    Route::post('/siswa/ganti-password', [SiswaDashboardController::class, 'gantiPassword'])->name('siswa.ganti-password');

    // Dashboard Guru & Wali Kelas (Kelola Izin & Sakit Siswa & Absensi Mengajar)
    Route::get('/guru/dashboard', [GuruDashboardController::class, 'indexGuru'])->name('guru.dashboard');
    Route::post('/guru/izin-sakit', [GuruDashboardController::class, 'storeIzinSakit'])->name('guru.izin-sakit.store');
    Route::put('/guru/izin-sakit/{absensi}', [GuruDashboardController::class, 'updateIzinSakit'])->name('guru.izin-sakit.update');
    Route::delete('/guru/izin-sakit/{absensi}', [GuruDashboardController::class, 'destroyIzinSakit'])->name('guru.izin-sakit.destroy');
    Route::get('/guru/absensi-mengajar', [AbsensiMengajarController::class, 'index'])->name('guru.absensi-mengajar');
    Route::post('/guru/absensi-mengajar', [AbsensiMengajarController::class, 'store'])->name('guru.absensi-mengajar.store');
    Route::get('/guru/absensi-mengajar/mapel/{kelas}', [AbsensiMengajarController::class, 'mapelByKelas'])->name('guru.absensi-mengajar.mapel');

    // Jurnal Mengajar Guru (Tabel Absensi & Jurnal Mengajar Guru pada halaman tersendiri)
    Route::get('/guru/jurnal-mengajar', [JurnalMengajarController::class, 'index'])->name('jurnal-mengajar.index');
    Route::get('/guru/jurnal-mengajar/export/excel', [JurnalMengajarController::class, 'exportExcel'])->name('jurnal-mengajar.export.excel');
    Route::get('/guru/jurnal-mengajar/export/pdf', [JurnalMengajarController::class, 'exportPdf'])->name('jurnal-mengajar.export.pdf');

    Route::get('/wali-kelas/dashboard', [GuruDashboardController::class, 'indexWaliKelas'])->name('walikelas.dashboard');
    Route::get('/wali-kelas', [GuruDashboardController::class, 'indexWaliKelas'])->name('wali-kelas.dashboard');

    // Scanner Presensi
    Route::get('/scanner', function () {
        return view('absensi-scanner');
    })->name('scanner');
    Route::get('/absensi-scanner', function () {
        return redirect()->route('scanner');
    })->name('absensi-scanner');

    // Kelola Siswa
    Route::prefix('siswa')->name('siswa.')->group(function () {
        Route::get('/', [SiswaController::class, 'index'])->name('index');
        Route::get('/template', [SiswaController::class, 'template'])->name('template');
        Route::get('/export', [SiswaController::class, 'export'])->name('export');
        Route::post('/import', [SiswaController::class, 'import'])->name('import');
        Route::post('/', [SiswaController::class, 'store'])->name('store');
        Route::put('/{siswa}', [SiswaController::class, 'update'])->name('update');
        Route::delete('/{siswa}', [SiswaController::class, 'destroy'])->name('destroy');
    });

    // Menu Generate QR Code Siswa (qrcode = nisn)
    Route::prefix('qrcode')->name('qrcode.')->group(function () {
        Route::get('/', [QrCodeController::class, 'index'])->name('index');
        Route::get('/print-all', [QrCodeController::class, 'printAll'])->name('print-all');
        Route::get('/kelas', [QrCodeController::class, 'printKelas'])->name('kelas');
    });

    // Rekapitulasi Presensi
    Route::prefix('rekapitulasi')->name('rekapitulasi.')->group(function () {
        Route::get('/', [RekapitulasiController::class, 'index'])->name('index');
        Route::get('/export', [RekapitulasiController::class, 'export'])->name('export');
    });

    // Pengaturan Waktu
    Route::prefix('pengaturan-waktu')->name('pengaturan-waktu.')->group(function () {
        Route::get('/', [PengaturanWaktuController::class, 'index'])->name('index');
        Route::post('/', [PengaturanWaktuController::class, 'store'])->name('store');
        Route::put('/{sesi}', [PengaturanWaktuController::class, 'update'])->name('update');
        Route::delete('/{sesi}', [PengaturanWaktuController::class, 'destroy'])->name('destroy');
        Route::post('/{sesi}/toggle', [PengaturanWaktuController::class, 'toggle'])->name('toggle');
    });

    // Pengaturan Akun (CRUD, Template & Import Excel, Ekspor Excel/PDF)
    Route::prefix('pengaturan-akun')->name('pengaturan-akun.')->group(function () {
        Route::get('/', [PengaturanAkunController::class, 'index'])->name('index');
        Route::get('/template', [PengaturanAkunController::class, 'template'])->name('template');
        Route::post('/import', [PengaturanAkunController::class, 'import'])->name('import');
        Route::post('/', [PengaturanAkunController::class, 'store'])->name('store');
        Route::get('/export/excel', [PengaturanAkunController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [PengaturanAkunController::class, 'exportPdf'])->name('export.pdf');
        Route::post('/siswa/{siswa}/reset-password', [PengaturanAkunController::class, 'resetPasswordSiswa'])->name('siswa.reset-password');
        Route::post('/siswa/reset-kelas', [PengaturanAkunController::class, 'resetPasswordKelas'])->name('siswa.reset-kelas');
        Route::put('/{user?}', [PengaturanAkunController::class, 'update'])->name('update');
        Route::delete('/{user}', [PengaturanAkunController::class, 'destroy'])->name('destroy');
    });

    // Kelola Kelas (id, tahun_pelajaran, kelas, wali_kelas CRUD)
    Route::prefix('kelas')->name('kelas.')->group(function () {
        Route::get('/', [KelasController::class, 'index'])->name('index');
        Route::post('/', [KelasController::class, 'store'])->name('store');
        Route::put('/{kela}', [KelasController::class, 'update'])->name('update');
        Route::delete('/{kela}', [KelasController::class, 'destroy'])->name('destroy');
    });

    // Kelola Jam Belajar (id, jam_ke, waktu_mulai, waktu_selesai CRUD)
    Route::prefix('jam-belajar')->name('jam-belajar.')->group(function () {
        Route::get('/', [JamBelajarController::class, 'index'])->name('index');
        Route::post('/', [JamBelajarController::class, 'store'])->name('store');
        Route::put('/{jamBelajar}', [JamBelajarController::class, 'update'])->name('update');
        Route::delete('/{jamBelajar}', [JamBelajarController::class, 'destroy'])->name('destroy');
    });

    // Kelola Mata Pelajaran (id, kelas, mata_pelajaran, nama_pengajar CRUD)
    Route::prefix('mapel')->name('mapel.')->group(function () {
        Route::get('/', [MapelController::class, 'index'])->name('index');
        Route::post('/', [MapelController::class, 'store'])->name('store');
        Route::put('/{mapel}', [MapelController::class, 'update'])->name('update');
        Route::delete('/{mapel}', [MapelController::class, 'destroy'])->name('destroy');
    });

    // Kelola Jadwal Pelajaran & Absensi Guru
    Route::prefix('jadwal')->name('jadwal.')->group(function () {
        Route::get('/', [JadwalController::class, 'index'])->name('index');
        Route::post('/', [JadwalController::class, 'store'])->name('store');
        Route::put('/{jadwal}', [JadwalController::class, 'update'])->name('update');
        Route::delete('/{jadwal}', [JadwalController::class, 'destroy'])->name('destroy');
        Route::post('/absensi-guru', [JadwalController::class, 'storeAbsensiGuru'])->name('absensi-guru.store');
        Route::delete('/absensi-guru/{absensiGuru}', [JadwalController::class, 'destroyAbsensiGuru'])->name('absensi-guru.destroy');
    });

    // Pengaturan Aplikasi (Kosongkan Data Presensi & Unduh Arsip Akhir Tahun Ajaran)
    Route::prefix('pengaturan-aplikasi')->name('pengaturan-aplikasi.')->group(function () {
        Route::get('/', [PengaturanAplikasiController::class, 'index'])->name('index');
        Route::get('/export-arsip', [PengaturanAplikasiController::class, 'exportArsip'])->name('export.arsip');
        Route::post('/reset-presensi', [PengaturanAplikasiController::class, 'resetPresensi'])->name('reset');
    });

    // Pengaturan Hari Libur (5 Hari Belajar: Sabtu Libur & Kalender Libur Khusus)
    Route::prefix('pengaturan-hari-libur')->name('hari-libur.')->group(function () {
        Route::get('/', [HariLiburController::class, 'index'])->name('index');
        Route::post('/', [HariLiburController::class, 'store'])->name('store');
        Route::delete('/{hariLibur}', [HariLiburController::class, 'destroy'])->name('destroy');
    });
});
