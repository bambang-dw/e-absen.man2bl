@extends('layouts.admin', [
    'title' => 'Pengaturan Aplikasi',
    'pageTitle' => 'Pengaturan & Pemeliharaan Aplikasi',
    'pageSubtitle' => 'MAN 2 Bandar Lampung - Siklus Tahunan, Pencadangan Arsip & Pembersihan Database Presensi'
])

@section('content')
<div class="container-fluid px-0">

    <!-- Annual Cycle Information Banner -->
    <div class="alert alert-primary border-0 rounded-4 shadow-sm p-4 mb-4" style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%); border-left: 5px solid #059669 !important;">
        <div class="d-flex align-items-start gap-3">
            <div class="rounded-circle bg-white p-3 shadow-sm text-success d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="bi bi-calendar-range-fill fs-3"></i>
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-1">Panduan Siklus Tahunan Presensi (Akhir Tahun Ajaran - Bulan Juli)</h5>
                <p class="text-secondary small mb-2" style="line-height: 1.6;">
                    Agar kapasitas database tidak membengkak, rekapitulasi data presensi siswa wajib <strong>diunduh dan diarsipkan setiap akhir tahun ajaran (bulan Juli)</strong>. Setelah file arsip tersimpan dengan aman, tabel presensi dapat dikosongkan agar siap digunakan kembali untuk presensi tahun ajaran baru.
                </p>
                <div class="d-flex flex-wrap gap-2 pt-1">
                    <span class="badge bg-white text-dark border px-3 py-2"><i class="bi bi-1-circle-fill text-success me-1"></i> Langkah 1: Unduh Arsip Excel</span>
                    <span class="badge bg-white text-dark border px-3 py-2"><i class="bi bi-2-circle-fill text-primary me-1"></i> Langkah 2: Simpan di Google Drive / Komputer</span>
                    <span class="badge bg-white text-dark border px-3 py-2"><i class="bi bi-3-circle-fill text-danger me-1"></i> Langkah 3: Kosongkan Presensi untuk TP Baru</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Database Status Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card border-start border-4 border-primary">
                <span class="text-muted small d-block">Total Data Presensi</span>
                <h3 class="fw-bold mb-0 text-dark">{{ number_format($totalAbsensi) }}</h3>
                <small class="text-muted" style="font-size: 0.72rem;">Baris Catatan Tersimpan</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card border-start border-4 border-success">
                <span class="text-muted small d-block">Tepat Waktu</span>
                <h3 class="fw-bold mb-0 text-success">{{ number_format($totalTepatWaktu) }}</h3>
                <small class="text-success" style="font-size: 0.72rem;">{{ $totalAbsensi > 0 ? round(($totalTepatWaktu / $totalAbsensi) * 100, 1) : 0 }}% dari Total</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card border-start border-4 border-warning">
                <span class="text-muted small d-block">Terlambat</span>
                <h3 class="fw-bold mb-0 text-warning">{{ number_format($totalTerlambat) }}</h3>
                <small class="text-warning" style="font-size: 0.72rem;">{{ $totalAbsensi > 0 ? round(($totalTerlambat / $totalAbsensi) * 100, 1) : 0 }}% dari Total</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card border-start border-4 border-info">
                <span class="text-muted small d-block">Rentang Data Presensi</span>
                <h6 class="fw-bold mb-0 text-dark pt-1">
                    {{ $tanggalAwal ? \Carbon\Carbon::parse($tanggalAwal)->format('d/m/Y') : '-' }} s/d {{ $tanggalAkhir ? \Carbon\Carbon::parse($tanggalAkhir)->format('d/m/Y') : '-' }}
                </h6>
                <small class="text-muted" style="font-size: 0.72rem;">Total Siswa: {{ $totalSiswa }} Siswa</small>
            </div>
        </div>
    </div>

    <!-- Two Action Columns: 1. Backup Archive & 2. Reset Database -->
    <div class="row g-4 mb-4">

        <!-- Column 1: Unduh Arsip Data Presensi -->
        <div class="col-lg-6">
            <div class="card-custom h-100 p-4 border shadow-sm position-relative">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-success bg-opacity-10 text-success p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-file-earmark-excel-fill fs-2"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0">1. Unduh Cadangan / Arsip Presensi</h5>
                        <small class="text-muted">Unduh seluruh riwayat data presensi sebelum dikosongkan</small>
                    </div>
                </div>

                <p class="text-secondary small mb-4" style="line-height: 1.6;">
                    File arsip akan dibuat dalam format spreadsheet Excel (<code>.xlsx</code>) yang mencakup data lengkap: tanggal presensi, sesi, NIS, NISN, nama siswa, kelas, jam scan (WIB), dan status kehadiran. Simpan berkas ini sebagai arsip resmi madrasah.
                </p>

                <div class="p-3 bg-light rounded-3 border mb-4">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Format Arsip:</span>
                        <strong>Microsoft Excel (.xlsx)</strong>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Data Terkandung:</span>
                        <strong>Seluruh Tahun Ajaran ({{ number_format($totalAbsensi) }} Baris)</strong>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Rekomendasi Waktu:</span>
                        <strong class="text-success">Akhir Juni - Akhir Juli</strong>
                    </div>
                </div>

                <a href="{{ route('pengaturan-aplikasi.export.arsip') }}" class="btn btn-success fw-bold py-2 px-4 rounded-pill shadow-sm w-100 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-download"></i> Unduh Arsip Data Presensi Lengkap (.xlsx)
                </a>
            </div>
        </div>

        <!-- Column 2: Kosongkan Data Presensi (Reset) -->
        <div class="col-lg-6">
            <div class="card-custom h-100 p-4 border shadow-sm position-relative border-danger-subtle">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-danger bg-opacity-10 text-danger p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-trash3-fill fs-2"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-danger mb-0">2. Kosongkan Data Presensi</h5>
                        <small class="text-muted">Pembersihan database untuk presensi tahun ajaran baru</small>
                    </div>
                </div>

                <p class="text-secondary small mb-4" style="line-height: 1.6;">
                    Fitur ini akan menghapus riwayat catatan presensi dari tabel <code>absensis</code> agar ukuran database tetap ringan dan cepat. <strong>Data master siswa, data kelas, akun pengguna, dan pengaturan waktu tidak akan terhapus.</strong>
                </p>

                <div class="alert alert-warning border-0 rounded-3 p-3 small mb-4">
                    <div class="d-flex gap-2">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                        <div>
                            <strong>Penting:</strong> Pastikan Anda telah mengunduh arsip presensi di sebelah kiri sebelum menjalankan proses pembersihan ini.
                        </div>
                    </div>
                </div>

                @if($totalAbsensi > 0)
                    <button type="button" class="btn btn-outline-danger fw-bold py-2 px-4 rounded-pill shadow-sm w-100 d-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#modalKosongkanPresensi">
                        <i class="bi bi-shield-slash-fill"></i> Mulai Kosongkan Data Presensi
                    </button>
                @else
                    <button type="button" class="btn btn-secondary py-2 px-4 rounded-pill w-100" disabled>
                        <i class="bi bi-check-circle me-1"></i> Data Presensi Sudah Bersih (0 Catatan)
                    </button>
                @endif
            </div>
        </div>

    </div>

</div>

<!-- Modal Konfirmasi Kosongkan Data Presensi -->
<div class="modal fade" id="modalKosongkanPresensi" tabindex="-1" aria-labelledby="modalKosongkanPresensiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom py-3 bg-danger text-white">
                <h5 class="modal-title fw-bold text-white mb-0" id="modalKosongkanPresensiLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Konfirmasi Pembersihan Database Presensi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('pengaturan-aplikasi.reset') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-danger fw-semibold small mb-3">
                        PERHATIAN: Tindakan ini akan menghapus catatan presensi siswa dari database. Tindakan ini tidak dapat dibatalkan!
                    </p>

                    <!-- Pilihan Lingkup Pembersihan -->
                    <label class="form-label small fw-bold text-dark">Pilih Lingkup Data yang Dikosongkan:</label>
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="lingkup" id="lingkupSemua" value="semua" checked onchange="toggleTanggalBatas()">
                            <label class="form-check-label fw-bold small text-dark" for="lingkupSemua">
                                Kosongkan Seluruh Data Presensi (Reset Total Tahun Ajaran Baru)
                            </label>
                            <small class="text-muted d-block ps-1">Menghapus seluruh {{ number_format($totalAbsensi) }} catatan presensi.</small>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="lingkup" id="lingkupTanggal" value="sebelum_tanggal" onchange="toggleTanggalBatas()">
                            <label class="form-check-label fw-bold small text-dark" for="lingkupTanggal">
                                Kosongkan Hanya Data Sebelum Tanggal Tertentu
                            </label>
                            <small class="text-muted d-block ps-1">Contoh: hapus catatan sebelum 1 Juli tahun bersangkutan.</small>
                        </div>
                    </div>

                    <!-- Input Tanggal Batas (muncul jika pilih opsi 2) -->
                    <div class="mb-3 d-none" id="groupTanggalBatas">
                        <label class="form-label small fw-bold text-dark">Hapus Semua Data Pada & Sebelum Tanggal:</label>
                        <input type="date" name="tanggal_batas" id="tanggalBatasInput" class="form-control" value="{{ date('Y-07-01') }}">
                    </div>

                    <!-- Konfirmasi Ketik KOSONGKAN -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">
                            Ketik kata <span class="badge bg-danger">KOSONGKAN</span> untuk verifikasi keamanan: <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="konfirmasi" class="form-control text-center fw-bold text-danger border-danger" placeholder="Ketik KOSONGKAN di sini" required autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-3">
                    <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">
                        <i class="bi bi-trash3-fill me-1"></i> Eksekusi Pengosongan Database
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleTanggalBatas() {
        const isTanggal = document.getElementById('lingkupTanggal').checked;
        const group = document.getElementById('groupTanggalBatas');
        if (isTanggal) {
            group.classList.remove('d-none');
        } else {
            group.classList.add('d-none');
        }
    }
</script>
@endsection
