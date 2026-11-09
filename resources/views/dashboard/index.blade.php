@extends('layouts.admin', [
'title' => 'Dashboard Presensi',
'pageTitle' => 'Dashboard Presensi Hari Ini',
'pageSubtitle' => 'Ringkasan data kehadiran siswa dan statistik real-time'
])

@section('content')
<div class="container-fluid px-0">

    <!-- Active Session & Control Banner -->
    <div class="card-custom p-4 mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); color: white;">
        <div class="row align-items-center g-3">
            <div class="col-lg-7">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge bg-white text-primary px-3 py-2 rounded-pill fw-bold" style="font-size: 0.8rem;">
                        <i class="bi bi-calendar3 me-1"></i> {{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }}
                    </span>
                    @if(!empty($statusHariIni['is_libur']))
                    <span class="badge bg-danger text-white px-3 py-2 rounded-pill fw-bold shadow-sm" style="font-size: 0.8rem;">
                        <i class="bi bi-calendar-x me-1"></i> Libur: {{ $statusHariIni['keterangan'] }}
                    </span>
                    @elseif(!empty($pulangAwal['aktif']))
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm" style="font-size: 0.8rem;">
                        <i class="bi bi-megaphone-fill me-1"></i> Pulang Lebih Awal: {{ $pulangAwal['keterangan'] }}
                    </span>
                    @elseif($sesiAktif)
                    <span class="badge bg-emerald-500 text-white px-3 py-2 rounded-pill fw-bold" style="background-color: #10b981; font-size: 0.8rem;">
                        <i class="bi bi-broadcast me-1"></i> Sesi Aktif: {{ $sesiAktif->nama_sesi }} ({{ substr($sesiAktif->jam_mulai, 0, 5) }} - {{ substr($sesiAktif->jam_selesai, 0, 5) }})
                    </span>
                    @else
                    <span class="badge bg-secondary text-white px-3 py-2 rounded-pill fw-semibold" style="font-size: 0.8rem;">
                        <i class="bi bi-clock me-1"></i> Tidak ada sesi yang aktif
                    </span>
                    @endif
                </div>
                <h3 class="fw-bold mb-1 text-white">Selamat Datang di Sistem Presensi Digital</h3>
                <p class="text-white-50 mb-0">Total <strong class="text-white">{{ $totalSiswa }} siswa</strong> terdaftar. Sekolah 5 hari (Senin s.d. Jumat, Sabtu libur).</p>
            </div>
            <div class="col-lg-5 text-lg-end">
                <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                    <!-- Tombol Menu Push Pulang Awal -->
                    <button type="button" class="btn btn-warning text-dark fw-bold px-3 py-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPulangAwal" title="Memulangkan seluruh siswa lebih awal jika ada kegiatan khusus">
                        <i class="bi bi-megaphone-fill me-1"></i> Pulangkan Siswa Lebih Awal
                    </button>
                    <!-- Tombol Pengaturan Hari Libur -->
                    <a href="{{ route('hari-libur.index') }}" class="btn btn-light text-dark fw-semibold px-3 py-2 rounded-pill shadow-sm">
                        <i class="bi bi-calendar-x me-1"></i> Pengaturan Hari Libur
                    </a>
                    <a href="{{ route('rekapitulasi.index') }}" class="btn btn-outline-light px-3 py-2 rounded-pill shadow-sm">
                        <i class="bi bi-file-earmark-text me-1"></i> Rekapitulasi Absen Siswa
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Status Khusus: Pulang Lebih Awal -->
    @if(!empty($pulangAwal['aktif']))
    <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between p-3 gap-3" role="alert">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-warning text-dark p-2 rounded-circle fs-4 flex-shrink-0">
                <i class="bi bi-megaphone-fill"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1 text-dark">Instruksi Pulang Lebih Awal Sedang Aktif Hari Ini!</h6>
                <p class="mb-0 text-muted small">
                    Alasan: <strong>{{ $pulangAwal['keterangan'] }}</strong> | Dipulangkan: <strong>{{ substr($pulangAwal['jam'], 0, 5) }} WIB</strong> | Petugas: <strong>{{ $pulangAwal['petugas'] }}</strong> | Otomatis tercatat: <strong>{{ $pulangAwal['total_dipulangkan'] ?? 0 }} siswa</strong>
                </p>
            </div>
        </div>
        <form action="{{ route('dashboard.batalkan-pulang-awal') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan status pulang lebih awal hari ini?')">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-dark rounded-pill px-3 text-nowrap">
                <i class="bi bi-x-circle me-1"></i> Batalkan Pulang Awal
            </button>
        </form>
    </div>
    @endif

    <!-- Alert Status Khusus: Hari Libur -->
    @if(!empty($statusHariIni['is_libur']))
    <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3 p-3" role="alert">
        <div class="bg-danger text-white p-2 rounded-circle fs-4 flex-shrink-0">
            <i class="bi bi-calendar-x"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-1 text-dark">Hari Ini Ditetapkan Sebagai Hari Libur: {{ $statusHariIni['keterangan'] }}</h6>
            <p class="mb-0 text-muted small">
                Sistem 5 hari belajar (Sabtu & Minggu libur). Sesi absensi dinonaktifkan dan seluruh siswa tidak terjadwal presensi.
            </p>
        </div>
    </div>
    @endif

    <!-- 6 Primary Stat Cards -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Siswa -->
        <div class="col-sm-6 col-xl-2">
            <div class="stat-card h-100" id="cardTotalSiswa">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold small">Jumlah Siswa</span>
                    <div class="stat-icon bg-primary-subtle text-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-dark" id="statTotalSiswa">{{ $totalSiswa }}</h2>
                <div class="text-muted small d-flex align-items-center gap-1">
                    <span class="badge bg-light text-secondary border">Total Siswa</span>
                </div>
            </div>
        </div>

        <!-- 2. Sudah Presensi -->
        <div class="col-sm-6 col-xl-2">
            <div class="stat-card h-100 border-start border-4 border-success" id="cardSudahPresensi">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold small">Sudah Presensi</span>
                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-success" id="statSudahPresensi">{{ $jumlahSudahPresensi }}</h2>
                <div class="text-muted small">
                    <span class="fw-bold text-success" id="statPersentaseKehadiran">{{ $persentaseKehadiran }}%</span> dari total siswa
                </div>
            </div>
        </div>

        <!-- 3. Tepat Waktu -->
        <div class="col-sm-6 col-xl-2">
            <div class="stat-card h-100 border-start border-4 border-primary" id="cardTepatWaktu">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold small">Tepat Waktu</span>
                    <div class="stat-icon bg-info-subtle text-primary">
                        <i class="bi bi-clock-fill"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-primary" id="statTepatWaktu">{{ $jumlahTepatWaktu }}</h2>
                <div class="text-muted small">
                    <span class="badge bg-primary-subtle text-primary">Sesuai Jadwal</span>
                </div>
            </div>
        </div>

        <!-- 4. Terlambat -->
        <div class="col-sm-6 col-xl-2">
            <div class="stat-card h-100 border-start border-4 border-warning" id="cardTerlambat">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold small">Terlambat</span>
                    <div class="stat-icon bg-warning-subtle text-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-warning" id="statTerlambat">{{ $jumlahTerlambat }}</h2>
                <div class="text-muted small">
                    <span class="badge bg-warning-subtle text-warning">Lewat Batas</span>
                </div>
            </div>
        </div>

        <!-- 5. Ijin / Sakit -->
        <div class="col-sm-6 col-xl-2">
            <div class="stat-card h-100 border-start border-4 border-info" id="cardIzinSakit">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold small">Ijin / Sakit</span>
                    <div class="stat-icon bg-secondary-subtle text-info">
                        <i class="bi bi-envelope-paper-fill"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-info" id="statTotalIzinSakit">{{ $totalIzinSakit }}</h2>
                <div class="text-muted small">
                    <span class="badge bg-info-subtle text-info" id="statDetailIzinSakit">{{ $jumlahIjin }} Izin / {{ $jumlahSakit }} Sakit</span>
                </div>
            </div>
        </div>

        <!-- 6. Alpha -->
        <div class="col-sm-6 col-xl-2">
            <div class="stat-card h-100 border-start border-4 border-danger" id="cardAlpha">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold small">Alpha / Belum</span>
                    <div class="stat-icon bg-danger-subtle text-danger">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-danger" id="statAlpha">{{ $jumlahAlpha }}</h2>
                <div class="text-muted small">
                    <span class="badge bg-danger-subtle text-danger">Tanpa Keterangan</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Kehadiran Doughnut Chart -->
        <div class="col-lg-5">
            <div class="card-custom p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Komposisi Presensi Hari Ini</h6>
                        <small class="text-muted">Distribusi status kehadiran seluruh siswa</small>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1" id="liveChartBadge">
                        <span class="spinner-grow spinner-grow-sm text-success" style="width: 7px; height: 7px;" role="status"></span>
                        <span>Real-time</span>
                    </span>
                </div>

                <div class="position-relative d-flex justify-content-center align-items-center my-3" style="min-height: 240px;">
                    <canvas id="kehadiranChart"
                        data-tepat="{{ $jumlahTepatWaktu }}"
                        data-terlambat="{{ $jumlahTerlambat }}"
                        data-izin="{{ $totalIzinSakit }}"
                        data-alpha="{{ $jumlahAlpha }}"
                        data-total="{{ max(1, $totalSiswa) }}"
                        style="max-height: 230px; max-width: 230px;"></canvas>
                </div>

                <div class="row text-center g-2 pt-2 border-top">
                    <div class="col-3">
                        <span class="d-block text-muted" style="font-size: 0.72rem;">Tepat Waktu</span>
                        <strong class="text-success" id="chartSummaryTepat">{{ $jumlahTepatWaktu }}</strong>
                    </div>
                    <div class="col-3">
                        <span class="d-block text-muted" style="font-size: 0.72rem;">Terlambat</span>
                        <strong class="text-warning" id="chartSummaryTerlambat">{{ $jumlahTerlambat }}</strong>
                    </div>
                    <div class="col-3">
                        <span class="d-block text-muted" style="font-size: 0.72rem;">Ijin/Sakit</span>
                        <strong class="text-info" id="chartSummaryIzin">{{ $totalIzinSakit }}</strong>
                    </div>
                    <div class="col-3">
                        <span class="d-block text-muted" style="font-size: 0.72rem;">Alpha</span>
                        <strong class="text-danger" id="chartSummaryAlpha">{{ $jumlahAlpha }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kehadiran Per Kelas Bar Chart -->
        <div class="col-lg-7">
            <div class="card-custom p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Persentase Kehadiran per Kelas</h6>
                        <small class="text-muted">Perbandingan tingkat kepatuhan presensi antar kelas</small>
                    </div>
                    <a href="{{ route('rekapitulasi.index') }}" class="btn btn-sm btn-outline-primary rounded-pill">
                        Tabel Lengkap <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div style="height: 260px;">
                    <canvas id="kelasChart"></canvas>
                    <div id="kelasChartDataList" class="d-none" aria-hidden="true">
                        @foreach($chartKelas as $ck)
                        <span class="kelas-chart-item"
                            data-kelas="{{ $ck['kelas'] ?? '' }}"
                            data-tepat="{{ $ck['tepat_waktu'] ?? 0 }}"
                            data-terlambat="{{ $ck['terlambat'] ?? 0 }}"
                            data-izin="{{ $ck['izin'] ?? 0 }}"></span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row: Sesi Absensi & Live Presensi Terkini -->
    <div class="row g-4">
        <!-- Sesi Absensi Aktif & Hari Ini -->
        <div class="col-lg-5">
            <div class="card-custom p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Jadwal Sesi Absensi</h6>
                        <small class="text-muted">Aturan batas waktu scan presensi</small>
                    </div>
                    <a href="{{ route('pengaturan-waktu.index') }}" class="btn btn-sm btn-light border text-primary rounded-pill">
                        <i class="bi bi-gear-fill me-1"></i> Kelola Sesi
                    </a>
                </div>

                <div class="list-group list-group-flush gap-2" style="max-height: 520px; overflow-y: auto; padding-right: 4px;">
                    @forelse($allSesi as $s)
                    @php
                    $isActive = in_array($s->id, $activeSesiIds ?? [$sesiAktif?->id]);
                    $currTime = $currentTime ?? \Carbon\Carbon::now()->toTimeString();
                    $isCurrentlyRunning = $currTime >= $s->jam_mulai && $currTime <= $s->jam_selesai;
                        $hasPassed = $currTime > $s->jam_selesai;
                        @endphp
                        <div class="list-group-item rounded-3 border p-3 {{ $isActive ? 'bg-indigo-50 border-primary' : 'bg-light' }}" style="{{ $isActive ? 'background-color: #eef2ff;' : '' }}">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $isActive ? 'bg-primary' : 'bg-secondary' }} rounded-pill">
                                        {{ $s->kode_sesi }}
                                    </span>
                                    <h6 class="mb-0 fw-bold {{ $isActive ? 'text-primary' : 'text-dark' }}">
                                        {{ $s->nama_sesi }}
                                    </h6>
                                </div>
                                @if($isCurrentlyRunning)
                                <span class="badge bg-success rounded-pill px-2.5 py-1"><i class="bi bi-broadcast me-1"></i>Sedang Berlangsung</span>
                                @elseif($hasPassed)
                                <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-0.5" style="font-size: 0.75rem;"><i class="bi bi-check2 me-1"></i>Selesai</span>
                                @else
                                <span class="badge bg-light text-muted border rounded-pill px-2 py-0.5" style="font-size: 0.75rem;"><i class="bi bi-clock me-1"></i>Belum Mulai</span>
                                @endif
                            </div>
                            <div class="d-flex flex-wrap gap-3 small text-muted">
                                <div><i class="bi bi-clock me-1 text-primary"></i> Mulai: <strong>{{ substr($s->jam_mulai, 0, 5) }}</strong></div>
                                <div><i class="bi bi-exclamation-circle me-1 text-warning"></i> Batas Tepat: <strong>{{ substr($s->jam_terlambat, 0, 5) }}</strong></div>
                                <div><i class="bi bi-check-circle me-1 text-danger"></i> Selesai: <strong>{{ substr($s->jam_selesai, 0, 5) }}</strong></div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-clock-slash fs-2 d-block mb-2"></i>
                            Belum ada sesi absensi yang dibuat.
                        </div>
                        @endforelse
                </div>
            </div>
        </div>

        <!-- Presensi Terbaru Hari Ini -->
        <div class="col-lg-7">
            <div class="card-custom p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Aktivitas Presensi Terbaru</h6>
                        <small class="text-muted">10 catatan scan QR code & izin/sakit siswa terakhir hari ini</small>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1.5" id="liveFeedBadgeAdmin">
                        <span class="spinner-grow spinner-grow-sm text-success" style="width: 7px; height: 7px;" role="status"></span>
                        <span id="liveFeedStatusText">Live Feed</span>
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">
                                <th>Siswa</th>
                                <th>Kelas</th>
                                <th>Sesi</th>
                                <th>Jam Scan</th>
                                <th>Status</th>
                                <th>Petugas Scan</th>
                            </tr>
                        </thead>
                        <tbody id="liveFeedBodyAdmin">
                            @forelse($presensiTerbaru as $p)
                            <tr data-absensi-id="{{ $p->id }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $p->siswa ? $p->siswa->foto_url : 'https://ui-avatars.com/api/?name=Siswa' }}" alt="" class="rounded-circle border" style="width: 36px; height: 36px; object-fit: cover;">
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.88rem;">{{ $p->siswa?->nama ?? 'Siswa Tidak Ditemukan' }}</div>
                                            <small class="text-muted" style="font-size: 0.75rem;">NIS: {{ $p->nis }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $p->siswa?->kelas ?? '-' }}</span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $p->sesi?->nama_sesi ?? '-' }}</small>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark" style="font-size: 0.85rem;">{{ substr($p->jam_scan, 0, 5) }} WIB</span>
                                </td>
                                <td>
                                    @if($p->status === 'Tepat Waktu')
                                    <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill">
                                        <i class="bi bi-check-circle me-1"></i> Tepat Waktu
                                    </span>
                                    @elseif($p->status === 'Terlambat')
                                    <span class="badge bg-warning-subtle text-warning px-2 py-1 rounded-pill">
                                        <i class="bi bi-exclamation-triangle me-1"></i> Terlambat
                                    </span>
                                    @elseif(in_array($p->status, ['Izin', 'Ijin']))
                                    <span class="badge bg-info-subtle text-info px-2 py-1 rounded-pill">
                                        <i class="bi bi-envelope-paper me-1"></i> Ijin
                                    </span>
                                    @elseif($p->status === 'Sakit')
                                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill">
                                        <i class="bi bi-hospital me-1"></i> Sakit
                                    </span>
                                    @else
                                    <span class="badge bg-danger-subtle text-danger px-2 py-1 rounded-pill">
                                        <i class="bi bi-x-circle me-1"></i> Alfa
                                    </span>
                                    @endif
                                </td>
                                <td>
                                    @if($p->petugas)
                                    <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1" style="font-size: 0.78rem;">
                                        <i class="bi bi-person-badge me-1"></i> {{ $p->petugas->name }}
                                    </span>
                                    @else
                                    <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2 text-muted"></i>
                                    Belum ada data presensi yang tercatat hari ini. Data presensi akan terdata otomatis saat petugas scanner atau guru memindai kartu siswa.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Baris Pengaturan Hari Libur & Informasi Kalender (5 Hari Belajar) -->
    <div class="row g-4 mt-1 mb-4">
        <div class="col-12">
            <div class="card-custom p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-calendar-x-fill text-danger me-1"></i> Pengaturan Hari Libur (5 Hari Belajar)
                            </h6>
                            <span class="badge bg-primary rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">Sabtu & Minggu Libur Otomatis</span>
                        </div>
                        <small class="text-muted">MAN 2 Bandar Lampung menerapkan 5 hari belajar (Senin-Jumat). Kelola hari libur khusus/nasional di bawah ini.</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahHariLibur">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Libur
                        </button>
                        <a href="{{ route('hari-libur.index') }}" class="btn btn-sm btn-light border text-dark rounded-pill px-3">
                            <i class="bi bi-calendar-week me-1"></i> Lihat Semua
                        </a>
                    </div>
                </div>

                <div class="row g-3">
                    @forelse($daftarHariLiburUpcoming as $libur)
                    @php
                    $tglCarbon = \Carbon\Carbon::parse($libur->tanggal);
                    $isToday = $tglCarbon->isToday();
                    @endphp
                    <div class="col-md-6 col-lg-4 col-xl">
                        <div class="p-3 rounded-3 border {{ $isToday ? 'bg-danger text-white border-danger' : 'bg-light' }}">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="badge {{ $isToday ? 'bg-white text-danger' : 'bg-danger-subtle text-danger' }} rounded-pill" style="font-size: 0.72rem;">
                                    {{ $tglCarbon->translatedFormat('d M Y') }}
                                </span>
                                <form action="{{ route('hari-libur.destroy', $libur->id) }}" method="POST" onsubmit="return confirm('Hapus hari libur ini?')" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-link p-0 text-decoration-none {{ $isToday ? 'text-white-50' : 'text-danger' }}" style="font-size: 0.85rem;" title="Hapus Libur">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="fw-bold {{ $isToday ? 'text-white' : 'text-dark' }} text-truncate" style="font-size: 0.88rem;" title="{{ $libur->keterangan }}">
                                {{ $libur->keterangan }}
                            </div>
                            <small class="{{ $isToday ? 'text-white-50' : 'text-muted' }}" style="font-size: 0.75rem;">
                                {{ $tglCarbon->translatedFormat('l') }} {{ $isToday ? '• Hari Ini' : '' }}
                            </small>
                        </div>
                    </div>
                    @empty
                    <div class="col-12">
                        <div class="p-3 rounded-3 bg-light border text-center text-muted small">
                            <i class="bi bi-calendar-check me-1"></i> Tidak ada agenda hari libur khusus terdekat. Hari Sabtu & Minggu otomatis libur.
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Baris Tabel Jam Belajar (jam_belajars) & Mata Pelajaran (mapels) -->
    <div class="row g-4 mt-1 mb-4">
        <!-- Tabel Jam Belajar (jam_belajars) -->
        <div class="col-lg-6">
            <div class="card-custom p-4 h-100">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-clock-fill text-primary me-1"></i> Tabel Jam Belajar (`jam_belajars`)
                        </h6>
                        <small class="text-muted">Kolom: <code>id | jam_ke | waktu_mulai | waktu_selesai</code></small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahJamBelajarDash">
                            <i class="bi bi-plus-circle me-1"></i> Tambah
                        </button>
                        <a href="{{ route('jam-belajar.index') }}" class="btn btn-sm btn-light border text-dark rounded-pill px-3">
                            Kelola Waktu Belajar <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>Jam Ke</th>
                                <th>Waktu Mulai</th>
                                <th>Waktu Selesai</th>
                                <th class="text-end" style="width: 70px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jamBelajars ?? [] as $jb)
                            <tr>
                                <td class="fw-bold text-secondary">#{{ $jb->id }}</td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary rounded-pill px-2.5 py-1 fw-semibold">
                                        Jam ke-{{ $jb->jam_ke }}
                                    </span>
                                </td>
                                <td class="fw-semibold text-dark">{{ \Carbon\Carbon::parse($jb->waktu_mulai)->format('H:i') }} WIB</td>
                                <td class="fw-semibold text-dark">{{ \Carbon\Carbon::parse($jb->waktu_selesai)->format('H:i') }} WIB</td>
                                <td class="text-end">
                                    <form action="{{ route('jam-belajar.destroy', $jb->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus Jam ke-{{ $jb->jam_ke }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border text-danger rounded-3" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-clock-history fs-4 d-block mb-1"></i>
                                    Belum ada data jam belajar. Klik <strong>Tambah</strong> untuk menambahkan.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tabel Mata Pelajaran (mapels) -->
        <div class="col-lg-6">
            <div class="card-custom p-4 h-100">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-journal-bookmark-fill text-success me-1"></i> Tabel Mata Pelajaran (`mapels`)
                        </h6>
                        <small class="text-muted">Kolom: <code>id | kode_mapel | mata_pelajaran</code></small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahMapelDash">
                            <i class="bi bi-plus-circle me-1"></i> Tambah
                        </button>
                        <a href="{{ route('mapel.index') }}" class="btn btn-sm btn-light border text-dark rounded-pill px-3">
                            Kelola Mata Pelajaran <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th style="width: 110px;">Kode Mapel</th>
                                <th>Mata Pelajaran</th>
                                <th class="text-end" style="width: 70px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mapels ?? [] as $m)
                            <tr>
                                <td class="fw-bold text-secondary">#{{ $m->id }}</td>
                                <td>
                                    <span class="badge bg-indigo-subtle text-primary border rounded-pill px-2.5 py-1 fw-bold" style="background-color: #e0e7ff; color: #4338ca;">
                                        {{ $m->kode_mapel ?: '-' }}
                                    </span>
                                </td>
                                <td class="fw-bold text-dark">{{ $m->mata_pelajaran }}</td>
                                <td class="text-end">
                                    <form action="{{ route('mapel.destroy', $m->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus mata pelajaran {{ $m->mata_pelajaran }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border text-danger rounded-3" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="bi bi-journal-x fs-4 d-block mb-1"></i>
                                    Belum ada data mata pelajaran. Klik <strong>Tambah</strong> untuk menambahkan.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal Push Pulangkan Siswa Lebih Awal -->
<div class="modal fade" id="modalPulangAwal" tabindex="-1" aria-labelledby="modalPulangAwalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="{{ route('dashboard.pulang-awal') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="modalPulangAwalLabel">
                        <i class="bi bi-megaphone-fill text-warning me-2"></i>Pulangkan Seluruh Siswa Lebih Awal
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning border-0 rounded-3 mb-3 small d-flex gap-2">
                        <i class="bi bi-info-circle-fill fs-5 text-warning flex-shrink-0"></i>
                        <div>
                            Gunakan menu ini jika ada <strong>kegiatan khusus atau situasi darurat</strong> (rapat dewan guru, classmeeting, cuaca ekstrem, dll) sehingga sekolah memulangkan seluruh siswa lebih awal dari jadwal reguler.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="alasanPulangAwal" class="form-label small fw-bold">Alasan / Keterangan Pulang Awal <span class="text-danger">*</span></label>
                        <input type="text" name="keterangan" id="alasanPulangAwal" class="form-control" placeholder="Contoh: Rapat Dewan Guru, Kegiatan Classmeeting" required>
                    </div>

                    <div class="d-flex flex-wrap gap-1 mb-3">
                        <span class="small text-muted me-1">Pilihan Cepat:</span>
                        <button type="button" class="badge bg-light text-dark border rounded-pill px-2.5 py-1" onclick="document.getElementById('alasanPulangAwal').value = 'Rapat Dewan Guru'">Rapat Guru</button>
                        <button type="button" class="badge bg-light text-dark border rounded-pill px-2.5 py-1" onclick="document.getElementById('alasanPulangAwal').value = 'Kegiatan Classmeeting'">Classmeeting</button>
                        <button type="button" class="badge bg-light text-dark border rounded-pill px-2.5 py-1" onclick="document.getElementById('alasanPulangAwal').value = 'Hujan Lebat / Cuaca Ekstrem'">Cuaca Ekstrem</button>
                        <button type="button" class="badge bg-light text-dark border rounded-pill px-2.5 py-1" onclick="document.getElementById('alasanPulangAwal').value = 'Persiapan Acara Sekolah'">Acara Sekolah</button>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Jam Pulang Awal</label>
                            <input type="time" name="jam_pulang" class="form-control" value="{{ date('H:i') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Sesi Kepulangan</label>
                            <select name="sesi_id" class="form-select">
                                @foreach($allSesi as $s)
                                <option value="{{ $s->id }}" {{ str_contains(strtolower($s->nama_sesi), 'pulang') ? 'selected' : '' }}>
                                    {{ $s->nama_sesi }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-check form-switch bg-light p-3 rounded-3 border mb-0">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="catat_otomatis" value="1" id="catatOtomatis" checked>
                        <label class="form-check-label small fw-semibold text-dark" for="catatOtomatis">
                            Otomatis catat presensi "Pulang" bagi seluruh siswa yang hadir hari ini
                        </label>
                        <small class="d-block text-muted mt-1 ps-4" style="font-size: 0.75rem;">
                            Siswa yang hadir hari ini tidak perlu mengantri scan kartu satu-per-satu lagi. Scanner juga tetap dibuka untuk siswa yang ingin scan manual.
                        </small>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm">
                        <i class="bi bi-send-fill me-1"></i> Jalankan Push Pulang Awal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Hari Libur Cepat dari Dashboard -->
<div class="modal fade" id="modalTambahHariLibur" tabindex="-1" aria-labelledby="modalTambahHariLiburLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="{{ route('hari-libur.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="modalTambahHariLiburLabel">
                        <i class="bi bi-calendar-plus text-primary me-2"></i>Tambah Hari Libur Khusus
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Hari Sabtu dan Minggu otomatis libur (5 hari belajar). Daftarkan tanggal libur nasional atau libur khusus sekolah lainnya.
                    </p>

                    <div class="mb-3">
                        <label for="tanggal_libur_dash" class="form-label small fw-bold">Tanggal Libur <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" id="tanggal_libur_dash" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="keterangan_libur_dash" class="form-label small fw-bold">Keterangan / Nama Hari Libur <span class="text-danger">*</span></label>
                        <input type="text" name="keterangan" id="keterangan_libur_dash" class="form-control" placeholder="Contoh: Libur Hari Guru, Maulid Nabi" required>
                    </div>

                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <span class="small text-muted me-2">Template Cepat:</span>
                        <button type="button" class="badge bg-light text-dark border rounded-pill px-2 py-1" onclick="document.getElementById('keterangan_libur_dash').value = 'Hari Libur Nasional'">Libur Nasional</button>
                        <button type="button" class="badge bg-light text-dark border rounded-pill px-2 py-1" onclick="document.getElementById('keterangan_libur_dash').value = 'Libur Semester'">Libur Semester</button>
                        <button type="button" class="badge bg-light text-dark border rounded-pill px-2 py-1" onclick="document.getElementById('keterangan_libur_dash').value = 'Kegiatan Classmeeting'">Classmeeting</button>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-save me-1"></i> Simpan Hari Libur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Jam Belajar dari Dashboard -->
<div class="modal fade" id="modalTambahJamBelajarDash" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="{{ route('jam-belajar.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi bi-clock-fill text-primary me-2"></i>Tambah Jam Belajar
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Jam Ke <span class="text-danger">*</span></label>
                        <input type="text" name="jam_ke" class="form-control" placeholder="Contoh: 1, 2, atau Istirahat" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Waktu Mulai <span class="text-danger">*</span></label>
                            <input type="time" name="waktu_mulai" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Waktu Selesai <span class="text-danger">*</span></label>
                            <input type="time" name="waktu_selesai" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-save me-1"></i> Simpan Jam Belajar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Mata Pelajaran dari Dashboard -->
<div class="modal fade" id="modalTambahMapelDash" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="{{ route('mapel.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi bi-journal-plus text-success me-2"></i>Tambah Mata Pelajaran
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kode Mapel <span class="text-danger">*</span></label>
                        <input type="text" name="kode_mapel" class="form-control" placeholder="Contoh: MTK, BIN, FIS" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Mata Pelajaran <span class="text-danger">*</span></label>
                        <input type="text" name="mata_pelajaran" class="form-control" placeholder="Contoh: Matematika, Bahasa Indonesia, Fisika" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4">
                        <i class="bi bi-save me-1"></i> Simpan Mata Pelajaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let doughnutChartInstance = null;
        let barChartInstance = null;
        let currentTotalSiswa = Number(document.getElementById('kehadiranChart')?.dataset.total) || 1;

        // 1. Doughnut Chart Kehadiran
        const ctxDoughnut = document.getElementById('kehadiranChart');
        if (ctxDoughnut) {
            const tepat = Number(ctxDoughnut.dataset.tepat) || 0;
            const terlambat = Number(ctxDoughnut.dataset.terlambat) || 0;
            const izin = Number(ctxDoughnut.dataset.izin) || 0;
            const alpha = Number(ctxDoughnut.dataset.alpha) || 0;

            doughnutChartInstance = new Chart(ctxDoughnut, {
                type: 'doughnut',
                data: {
                    labels: ['Tepat Waktu', 'Terlambat', 'Ijin/Sakit', 'Alpha'],
                    datasets: [{
                        data: [tepat, terlambat, izin, alpha],
                        backgroundColor: [
                            '#10b981', // Emerald
                            '#f59e0b', // Amber
                            '#0ea5e9', // Sky blue
                            '#ef4444' // Rose red
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed || 0;
                                    const percentage = Math.round((value / Math.max(1, currentTotalSiswa)) * 100);
                                    return ' ' + context.label + ': ' + value + ' siswa (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    cutout: '72%'
                }
            });
        }

        // 2. Bar Chart Per Kelas
        const ctxBar = document.getElementById('kelasChart');
        if (ctxBar) {
            const chartData = Array.from(document.querySelectorAll('#kelasChartDataList .kelas-chart-item')).map(function(el) {
                return {
                    kelas: el.getAttribute('data-kelas') || '',
                    tepat_waktu: Number(el.getAttribute('data-tepat')) || 0,
                    terlambat: Number(el.getAttribute('data-terlambat')) || 0,
                    izin: Number(el.getAttribute('data-izin')) || 0
                };
            });
            const labels = chartData.map(function(item) {
                return item.kelas;
            });
            const tepatData = chartData.map(function(item) {
                return item.tepat_waktu;
            });
            const lambatData = chartData.map(function(item) {
                return item.terlambat;
            });
            const izinData = chartData.map(function(item) {
                return item.izin || 0;
            });

            barChartInstance = new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Tepat Waktu',
                            data: tepatData,
                            backgroundColor: '#4f46e5',
                            borderRadius: 6
                        },
                        {
                            label: 'Terlambat',
                            data: lambatData,
                            backgroundColor: '#fbbf24',
                            borderRadius: 6
                        },
                        {
                            label: 'Ijin/Sakit',
                            data: izinData,
                            backgroundColor: '#0ea5e9',
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // =========================================================================
        // 3. REAL-TIME AUTO-UPDATE STATISTIK, GRAFIK, & LIVE FEED TANPA REFRESH
        // =========================================================================
        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function updateTextWithPulse(elementId, newValue, cardId) {
            const el = document.getElementById(elementId);
            if (!el) return false;
            const strVal = String(newValue);
            if (el.textContent.trim() !== strVal) {
                el.textContent = strVal;
                const targetCard = cardId ? document.getElementById(cardId) : el;
                if (targetCard) {
                    targetCard.style.transition = 'transform 0.25s ease, box-shadow 0.25s ease';
                    targetCard.style.transform = 'scale(1.03)';
                    targetCard.style.boxShadow = '0 10px 25px rgba(16, 185, 129, 0.2)';
                    setTimeout(function() {
                        targetCard.style.transform = '';
                        targetCard.style.boxShadow = '';
                    }, 500);
                }
                return true;
            }
            return false;
        }

        function renderStatusBadgeHtml(status) {
            if (status === 'Tepat Waktu') {
                return '<span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill"><i class="bi bi-check-circle me-1"></i> Tepat Waktu</span>';
            }
            if (status === 'Terlambat') {
                return '<span class="badge bg-warning-subtle text-warning px-2 py-1 rounded-pill"><i class="bi bi-exclamation-triangle me-1"></i> Terlambat</span>';
            }
            if (status === 'Izin' || status === 'Ijin') {
                return '<span class="badge bg-info-subtle text-info px-2 py-1 rounded-pill"><i class="bi bi-envelope-paper me-1"></i> Ijin</span>';
            }
            if (status === 'Sakit') {
                return '<span class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill"><i class="bi bi-hospital me-1"></i> Sakit</span>';
            }
            return '<span class="badge bg-danger-subtle text-danger px-2 py-1 rounded-pill"><i class="bi bi-x-circle me-1"></i> Alfa</span>';
        }

        function renderLiveFeedTable(items) {
            const tbody = document.getElementById('liveFeedBodyAdmin');
            if (!tbody || !Array.isArray(items)) return;

            if (items.length === 0) {
                tbody.innerHTML =
                    '<tr>' +
                    '<td colspan="6" class="text-center py-4 text-muted">' +
                    '<i class="bi bi-inbox fs-3 d-block mb-2 text-muted"></i>' +
                    'Belum ada data presensi yang tercatat hari ini. Data presensi akan terdata otomatis saat petugas scanner atau guru memindai kartu siswa.' +
                    '</td>' +
                    '</tr>';
                return;
            }

            const existingFirstId = tbody.querySelector('tr')?.getAttribute('data-absensi-id');
            const newFirstId = String(items[0].id);

            const rowsHtml = items.map(function(p, index) {
                const petugasHtml = p.petugas ?
                    '<span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1" style="font-size: 0.78rem;"><i class="bi bi-person-badge me-1"></i> ' + escapeHtml(p.petugas) + '</span>' :
                    '<span class="text-muted small">-</span>';
                const highlightClass = (index === 0 && existingFirstId && existingFirstId !== newFirstId) ? 'table-success' : '';

                return (
                    '<tr data-absensi-id="' + escapeHtml(p.id) + '" class="' + highlightClass + '" style="transition: background-color 0.6s ease;">' +
                    '<td>' +
                    '<div class="d-flex align-items-center gap-2">' +
                    '<img src="' + escapeHtml(p.foto_url) + '" alt="" class="rounded-circle border" style="width: 36px; height: 36px; object-fit: cover;">' +
                    '<div>' +
                    '<div class="fw-bold text-dark" style="font-size: 0.88rem;">' + escapeHtml(p.nama) + '</div>' +
                    '<small class="text-muted" style="font-size: 0.75rem;">NIS: ' + escapeHtml(p.nis) + '</small>' +
                    '</div>' +
                    '</div>' +
                    '</td>' +
                    '<td><span class="badge bg-light text-dark border">' + escapeHtml(p.kelas) + '</span></td>' +
                    '<td><small class="text-muted">' + escapeHtml(p.nama_sesi) + '</small></td>' +
                    '<td><span class="fw-semibold text-dark" style="font-size: 0.85rem;">' + escapeHtml(p.jam_scan) + ' WIB</span></td>' +
                    '<td>' + renderStatusBadgeHtml(p.status) + '</td>' +
                    '<td>' + petugasHtml + '</td>' +
                    '</tr>'
                );
            }).join('');

            tbody.innerHTML = rowsHtml;

            if (existingFirstId && existingFirstId !== newFirstId) {
                const firstRow = tbody.querySelector('tr');
                if (firstRow) {
                    setTimeout(function() {
                        firstRow.classList.remove('table-success');
                    }, 1800);
                }
            }
        }

        let isFetchingLive = false;

        function fetchLiveDashboardStats() {
            if (isFetchingLive || document.hidden) return;
            isFetchingLive = true;

            fetch(window.location.pathname + '?live=1&_t=' + Date.now(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    cache: 'no-store'
                })
                .then(function(res) {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.json();
                })
                .then(function(data) {
                    currentTotalSiswa = Number(data.totalSiswa) || 1;

                    // 1. Update 6 Kartu Statistik Utama
                    updateTextWithPulse('statTotalSiswa', data.totalSiswa, 'cardTotalSiswa');
                    updateTextWithPulse('statSudahPresensi', data.jumlahSudahPresensi, 'cardSudahPresensi');
                    updateTextWithPulse('statPersentaseKehadiran', data.persentaseKehadiran + '%');
                    updateTextWithPulse('statTepatWaktu', data.jumlahTepatWaktu, 'cardTepatWaktu');
                    updateTextWithPulse('statTerlambat', data.jumlahTerlambat, 'cardTerlambat');
                    updateTextWithPulse('statTotalIzinSakit', data.totalIzinSakit, 'cardIzinSakit');
                    updateTextWithPulse('statDetailIzinSakit', data.jumlahIjin + ' Izin / ' + data.jumlahSakit + ' Sakit');
                    updateTextWithPulse('statAlpha', data.jumlahAlpha, 'cardAlpha');

                    // 2. Update Angka Ringkasan di Bawah Doughnut Chart
                    updateTextWithPulse('chartSummaryTepat', data.jumlahTepatWaktu);
                    updateTextWithPulse('chartSummaryTerlambat', data.jumlahTerlambat);
                    updateTextWithPulse('chartSummaryIzin', data.totalIzinSakit);
                    updateTextWithPulse('chartSummaryAlpha', data.jumlahAlpha);

                    // 3. Update Doughnut Chart
                    if (doughnutChartInstance) {
                        const newDoughnutData = [
                            Number(data.jumlahTepatWaktu) || 0,
                            Number(data.jumlahTerlambat) || 0,
                            Number(data.totalIzinSakit) || 0,
                            Number(data.jumlahAlpha) || 0
                        ];
                        const oldDoughnutData = doughnutChartInstance.data.datasets[0].data;
                        if (JSON.stringify(oldDoughnutData) !== JSON.stringify(newDoughnutData)) {
                            doughnutChartInstance.data.datasets[0].data = newDoughnutData;
                            doughnutChartInstance.update();
                        }
                    }

                    // 4. Update Bar Chart Per Kelas
                    if (barChartInstance && Array.isArray(data.chartKelas)) {
                        const labels = data.chartKelas.map(function(item) {
                            return item.kelas;
                        });
                        const tepatData = data.chartKelas.map(function(item) {
                            return item.tepat_waktu;
                        });
                        const lambatData = data.chartKelas.map(function(item) {
                            return item.terlambat;
                        });
                        const izinData = data.chartKelas.map(function(item) {
                            return item.izin || 0;
                        });

                        const changed =
                            JSON.stringify(barChartInstance.data.labels) !== JSON.stringify(labels) ||
                            JSON.stringify(barChartInstance.data.datasets[0].data) !== JSON.stringify(tepatData) ||
                            JSON.stringify(barChartInstance.data.datasets[1].data) !== JSON.stringify(lambatData) ||
                            JSON.stringify(barChartInstance.data.datasets[2]?.data) !== JSON.stringify(izinData);

                        if (changed) {
                            barChartInstance.data.labels = labels;
                            barChartInstance.data.datasets[0].data = tepatData;
                            barChartInstance.data.datasets[1].data = lambatData;
                            if (barChartInstance.data.datasets[2]) {
                                barChartInstance.data.datasets[2].data = izinData;
                            }
                            barChartInstance.update();
                        }
                    }

                    // 5. Update Tabel Aktivitas Presensi Terbaru
                    renderLiveFeedTable(data.presensiTerbaru);
                })
                .catch(function(err) {
                    console.warn('Gagal memuat pembaruan live dashboard:', err);
                })
                .finally(function() {
                    isFetchingLive = false;
                });
        }

        // Polling otomatis setiap 3 detik untuk menangkap transaksi dari perangkat manapun
        setInterval(fetchLiveDashboardStats, 3000);

        // Segera perbarui saat tab kembali aktif
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                fetchLiveDashboardStats();
            }
        });

        // Sinkronisasi instan (0ms) antar-tab browser menggunakan BroadcastChannel & Storage Event
        if ('BroadcastChannel' in window) {
            const liveChannel = new BroadcastChannel('e_absen_live_channel');
            liveChannel.onmessage = function() {
                fetchLiveDashboardStats();
            };
        }
        window.addEventListener('storage', function(e) {
            if (e.key === 'e_absen_last_transaction') {
                fetchLiveDashboardStats();
            }
        });
    });
</script>
@endpush