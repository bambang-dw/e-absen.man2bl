@extends('layouts.admin', [
    'title' => 'Dashboard Wali Kelas',
    'pageTitle' => 'Dashboard Wali Kelas',
    'pageSubtitle' => 'MAN 2 Bandar Lampung - Pemantauan & Rekapitulasi Presensi Kelas Binaan'
])

@section('content')
<div class="container-fluid px-0">

    <!-- Top Navigation Tabs (Rekap Bulan, Rekap Semester, Presensi Harian) & Class Selector -->
    <div class="card-custom p-4 mb-4">
        <form action="{{ route('walikelas.dashboard') }}" method="GET" id="formFilterWali" class="row g-3 align-items-center">
            <input type="hidden" name="mode" id="filterMode" value="{{ $mode }}">

            <!-- Class Selector -->
            <div class="col-md-4 col-lg-3">
                <label class="form-label small fw-bold text-dark">
                    <i class="bi bi-door-open-fill text-primary me-1"></i> Kelas Binaan:
                </label>
                @if($daftarKelas->count() <= 1)
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white border-primary"><i class="bi bi-mortarboard-fill"></i></span>
                        <input type="text" class="form-control bg-light border fw-bold text-primary" value="Kelas {{ $kelas }}" readonly>
                        <input type="hidden" name="kelas" value="{{ $kelas }}">
                    </div>
                @else
                    <select name="kelas" class="form-select bg-light border fw-semibold" onchange="document.getElementById('formFilterWali').submit()">
                        @foreach($daftarKelas as $k)
                            <option value="{{ $k }}" {{ $kelas === $k ? 'selected' : '' }}>Kelas {{ $k }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <!-- Tab Switchers -->
            <div class="col-md-8 col-lg-6">
                <label class="form-label small fw-bold text-dark d-block">
                    <i class="bi bi-view-list text-primary me-1"></i> Pilihan Tampilan Rekap:
                </label>
                <div class="btn-group w-100 shadow-sm" role="group">
                    <button type="button" class="btn {{ $mode === 'bulan' ? 'btn-primary' : 'btn-outline-primary' }} py-2 fw-semibold" onclick="switchMode('bulan')">
                        <i class="bi bi-calendar-month me-1"></i> Rekap Bulan
                    </button>
                    <button type="button" class="btn {{ $mode === 'semester' ? 'btn-primary' : 'btn-outline-primary' }} py-2 fw-semibold" onclick="switchMode('semester')">
                        <i class="bi bi-award me-1"></i> Rekap Semester
                    </button>
                    <button type="button" class="btn {{ $mode === 'harian' ? 'btn-primary' : 'btn-outline-primary' }} py-2 fw-semibold" onclick="switchMode('harian')">
                        <i class="bi bi-calendar-check me-1"></i> Presensi Harian
                    </button>
                </div>
            </div>

            <!-- Specific Filter Inputs based on Active Tab -->
            <div class="col-12 col-lg-3 text-lg-end pt-lg-4">
                @if($mode === 'bulan')
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-calendar3"></i></span>
                        <input type="month" name="bulan" class="form-control bg-light" value="{{ $bulanInput }}" onchange="document.getElementById('formFilterWali').submit()">
                    </div>
                @elseif($mode === 'semester')
                    <div class="d-flex gap-2">
                        <select name="semester" class="form-select bg-light" onchange="document.getElementById('formFilterWali').submit()">
                            <option value="ganjil" {{ $semesterInput === 'ganjil' ? 'selected' : '' }}>Ganjil (Jul - Des)</option>
                            <option value="genap" {{ $semesterInput === 'genap' ? 'selected' : '' }}>Genap (Jan - Jun)</option>
                        </select>
                        <select name="tahun" class="form-select bg-light" style="max-width: 100px;" onchange="document.getElementById('formFilterWali').submit()">
                            @for($y = date('Y') + 1; $y >= date('Y') - 2; $y--)
                                <option value="{{ $y }}" {{ $tahunInput == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                @else
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-calendar-event"></i></span>
                        <input type="date" name="tanggal" class="form-control bg-light" value="{{ $tanggalHarian }}" onchange="document.getElementById('formFilterWali').submit()">
                    </div>
                @endif
            </div>
        </form>
    </div>

    {{-- ========================================================================= --}}
    {{-- 1. TAMPILAN MODE REKAP BULAN                                              --}}
    {{-- ========================================================================= --}}
    @if($mode === 'bulan')
        <!-- Metric Cards Rekap Bulan -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-4 col-xl-2">
                <div class="stat-card border-start border-4 border-primary">
                    <span class="text-muted small d-block">Siswa Kelas {{ $kelas }}</span>
                    <h3 class="fw-bold mb-0 text-dark">{{ $totalSiswaKelas }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Hari Efektif: {{ $hariEfektifBulan }} Hari</small>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl-2">
                <div class="stat-card border-start border-4 border-success">
                    <span class="text-muted small d-block">Rata-Rata Kehadiran</span>
                    <h3 class="fw-bold mb-0 text-success">{{ $bulanRataRataHadir }}%</h3>
                    <small class="text-success" style="font-size: 0.72rem;"><i class="bi bi-check2-circle"></i> Bulan {{ $namaBulan }}</small>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl-2">
                <div class="stat-card border-start border-4 border-primary">
                    <span class="text-muted small d-block">Tepat Waktu</span>
                    <h3 class="fw-bold mb-0 text-primary">{{ $bulanTotalTepat }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Total Akumulasi</small>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl-2">
                <div class="stat-card border-start border-4 border-warning">
                    <span class="text-muted small d-block">Terlambat</span>
                    <h3 class="fw-bold mb-0 text-warning">{{ $bulanTotalLambat }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Total Akumulasi</small>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl-2">
                <div class="stat-card border-start border-4 border-info">
                    <span class="text-muted small d-block">Izin & Sakit</span>
                    <h3 class="fw-bold mb-0 text-info">{{ $bulanTotalIzin + $bulanTotalSakit }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">I: {{ $bulanTotalIzin }} | S: {{ $bulanTotalSakit }}</small>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl-2">
                <div class="stat-card border-start border-4 border-danger">
                    <span class="text-muted small d-block">Alfa / Tanpa Ket.</span>
                    <h3 class="fw-bold mb-0 text-danger">{{ $bulanTotalAlfa }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Total Akumulasi</small>
                </div>
            </div>
        </div>

        <!-- Student Monthly Attendance Table -->
        <div class="card-custom p-0 overflow-hidden mb-4">
            <div class="p-3 bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary rounded-pill px-3 py-2">
                        <i class="bi bi-calendar-check me-1"></i> Rekap Bulan: {{ $namaBulan }}
                    </span>
                    <h6 class="fw-bold text-dark mb-0">Daftar Kehadiran Siswa Kelas {{ $kelas }}</h6>
                </div>
                <div class="text-muted small">
                    Total Siswa: <strong>{{ count($rekapBulanSiswa) }}</strong> | Hari Efektif Presensi: <strong>{{ $hariEfektifBulan }}</strong>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr style="font-size: 0.78rem; text-transform: uppercase;">
                            <th class="ps-4" style="width: 50px;">No</th>
                            <th>Siswa</th>
                            <th>NIS / NISN</th>
                            <th class="text-center text-success">Tepat Waktu</th>
                            <th class="text-center text-warning">Terlambat</th>
                            <th class="text-center text-info">Izin</th>
                            <th class="text-center text-secondary">Sakit</th>
                            <th class="text-center text-danger">Alfa</th>
                            <th class="text-center">Total Hadir</th>
                            <th class="text-center">% Kehadiran</th>
                            <th class="text-center pe-4">Koordinasi Ortu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rekapBulanSiswa as $idx => $row)
                            @php
                                $s = $row['siswa'];
                                $waMsg = urlencode("Assalamualaikum Wr. Wb. Bpk/Ibu wali dari {$s->nama} (Kelas {$s->kelas} MAN 2 Bandar Lampung). Menginformasikan rekap presensi ananda pada bulan {$namaBulan}: Hadir {$row['total_hadir']} hari, Terlambat {$row['lambat']} kali, Izin {$row['izin']}, Sakit {$row['sakit']}, Alfa {$row['alfa']} hari. Terima kasih atas perhatiannya.");
                            @endphp
                            <tr>
                                <td class="ps-4 text-muted fw-bold">{{ $idx + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $s->foto_url }}" alt="{{ $s->nama }}" class="rounded-circle border shadow-sm" style="width: 38px; height: 38px; object-fit: cover;">
                                        <div>
                                            <strong class="text-dark d-block">{{ $s->nama }}</strong>
                                            <small class="text-muted">Kelas {{ $s->kelas }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">NIS: <strong>{{ $s->nis }}</strong></div>
                                    <div class="text-muted small">NISN: {{ $s->nisn ?: '-' }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">{{ $row['tepat'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning-subtle text-warning fw-bold px-2 py-1">{{ $row['lambat'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info-subtle text-info fw-bold px-2 py-1">{{ $row['izin'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-secondary fw-bold px-2 py-1">{{ $row['sakit'] }}</span>
                                </td>
                                <td class="text-center">
                                    @if($row['alfa'] > 0)
                                        <span class="badge bg-danger text-white fw-bold px-2 py-1">{{ $row['alfa'] }}</span>
                                    @else
                                        <span class="badge bg-light text-muted px-2 py-1">0</span>
                                    @endif
                                </td>
                                <td class="text-center fw-bold text-dark">
                                    {{ $row['total_hadir'] }} <span class="text-muted fw-normal small">/ {{ $hariEfektifBulan }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <strong class="{{ $row['persentase'] >= 80 ? 'text-success' : ($row['persentase'] >= 65 ? 'text-warning' : 'text-danger') }}">
                                            {{ $row['persentase'] }}%
                                        </strong>
                                    </div>
                                    <div class="progress mt-1" style="height: 4px; width: 60px; margin: 0 auto;">
                                        <div class="progress-bar {{ $row['persentase'] >= 80 ? 'bg-success' : ($row['persentase'] >= 65 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $row['persentase'] }}%"></div>
                                    </div>
                                </td>
                                <td class="text-center pe-4">
                                    @if($s->no_hp_ortu)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $s->no_hp_ortu) }}?text={{ $waMsg }}" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-sm" title="Kirim Rekap WA ke Orang Tua">
                                            <i class="bi bi-whatsapp me-1"></i> WA Ortu
                                        </a>
                                    @else
                                        <span class="badge bg-light text-muted border">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">
                                    Belum ada data siswa yang terdaftar di kelas {{ $kelas }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    {{-- ========================================================================= --}}
    {{-- 2. TAMPILAN MODE REKAP SEMESTER                                           --}}
    {{-- ========================================================================= --}}
    @elseif($mode === 'semester')
        <!-- Metric Cards Rekap Semester -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-4 col-xl-2">
                <div class="stat-card border-start border-4 border-indigo" style="border-left-color: #6366f1 !important;">
                    <span class="text-muted small d-block">Siswa Kelas {{ $kelas }}</span>
                    <h3 class="fw-bold mb-0 text-dark">{{ $totalSiswaKelas }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Efektif: {{ $hariEfektifSemester }} Hari</small>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl-2">
                <div class="stat-card border-start border-4 border-success">
                    <span class="text-muted small d-block">Rata-Rata Semester</span>
                    <h3 class="fw-bold mb-0 text-success">{{ $semesterRataRataHadir }}%</h3>
                    <small class="text-success" style="font-size: 0.72rem;"><i class="bi bi-award-fill"></i> {{ $namaSemester }}</small>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl-2">
                <div class="stat-card border-start border-4 border-primary">
                    <span class="text-muted small d-block">Total Tepat Waktu</span>
                    <h3 class="fw-bold mb-0 text-primary">{{ $semesterTotalTepat }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Hari Presensi</small>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl-2">
                <div class="stat-card border-start border-4 border-warning">
                    <span class="text-muted small d-block">Total Terlambat</span>
                    <h3 class="fw-bold mb-0 text-warning">{{ $semesterTotalLambat }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Hari Presensi</small>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl-2">
                <div class="stat-card border-start border-4 border-info">
                    <span class="text-muted small d-block">Total Izin / Sakit</span>
                    <h3 class="fw-bold mb-0 text-info">{{ $semesterTotalIzin + $semesterTotalSakit }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">I: {{ $semesterTotalIzin }} | S: {{ $semesterTotalSakit }}</small>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl-2">
                <div class="stat-card border-start border-4 border-danger">
                    <span class="text-muted small d-block">Total Alfa</span>
                    <h3 class="fw-bold mb-0 text-danger">{{ $semesterTotalAlfa }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Hari Presensi</small>
                </div>
            </div>
        </div>

        <!-- Student Semester Attendance Table -->
        <div class="card-custom p-0 overflow-hidden mb-4">
            <div class="p-3 bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-indigo-500 text-white rounded-pill px-3 py-2" style="background-color: #4f46e5;">
                        <i class="bi bi-award me-1"></i> Rekapitulasi: {{ $namaSemester }}
                    </span>
                    <h6 class="fw-bold text-dark mb-0">Akumulasi Satu Semester Kelas {{ $kelas }}</h6>
                </div>
                <div class="text-muted small">
                    Hari Efektif Semester: <strong>{{ $hariEfektifSemester }} Hari</strong>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr style="font-size: 0.78rem; text-transform: uppercase;">
                            <th class="ps-4" style="width: 50px;">No</th>
                            <th>Siswa</th>
                            <th>NIS / NISN</th>
                            <th class="text-center text-success">Tepat Waktu</th>
                            <th class="text-center text-warning">Terlambat</th>
                            <th class="text-center text-info">Izin & Sakit</th>
                            <th class="text-center text-danger">Alfa</th>
                            <th class="text-center">Total Hadir</th>
                            <th class="text-center">% Kehadiran</th>
                            <th class="text-center">Predikat</th>
                            <th class="text-center pe-4">Aksi Wali Kelas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rekapSemesterSiswa as $idx => $row)
                            @php
                                $s = $row['siswa'];
                                $waMsg = urlencode("Assalamualaikum Wr. Wb. Bpk/Ibu wali dari {$s->nama} (Kelas {$s->kelas} MAN 2 Bandar Lampung). Kami menginformasikan rekapitulasi kehadiran ananda pada {$namaSemester}: Total Kehadiran {$row['persentase']}% ({$row['total_hadir']} hari hadir, {$row['lambat']} terlambat, {$row['alfa']} alfa). Predikat: {$row['predikat']}. Terima kasih.");
                            @endphp
                            <tr>
                                <td class="ps-4 text-muted fw-bold">{{ $idx + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $s->foto_url }}" alt="{{ $s->nama }}" class="rounded-circle border shadow-sm" style="width: 38px; height: 38px; object-fit: cover;">
                                        <div>
                                            <strong class="text-dark d-block">{{ $s->nama }}</strong>
                                            <small class="text-muted">Kelas {{ $s->kelas }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">NIS: <strong>{{ $s->nis }}</strong></div>
                                    <div class="text-muted small">NISN: {{ $s->nisn ?: '-' }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">{{ $row['tepat'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning-subtle text-warning fw-bold px-2 py-1">{{ $row['lambat'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info-subtle text-info fw-bold px-2 py-1">{{ $row['izin'] + $row['sakit'] }}</span>
                                </td>
                                <td class="text-center">
                                    @if($row['alfa'] > 0)
                                        <span class="badge bg-danger text-white fw-bold px-2 py-1">{{ $row['alfa'] }}</span>
                                    @else
                                        <span class="badge bg-light text-muted px-2 py-1">0</span>
                                    @endif
                                </td>
                                <td class="text-center fw-bold text-dark">
                                    {{ $row['total_hadir'] }} <span class="text-muted fw-normal small">Hari</span>
                                </td>
                                <td class="text-center">
                                    <strong class="{{ $row['persentase'] >= 80 ? 'text-success' : ($row['persentase'] >= 65 ? 'text-warning' : 'text-danger') }}">
                                        {{ $row['persentase'] }}%
                                    </strong>
                                    <div class="progress mt-1" style="height: 4px; width: 60px; margin: 0 auto;">
                                        <div class="progress-bar {{ $row['persentase'] >= 80 ? 'bg-success' : ($row['persentase'] >= 65 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $row['persentase'] }}%"></div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($row['predikat'] === 'Sangat Baik')
                                        <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1">Sangat Baik</span>
                                    @elseif($row['predikat'] === 'Baik')
                                        <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1">Baik</span>
                                    @elseif($row['predikat'] === 'Cukup')
                                        <span class="badge bg-warning-subtle text-warning rounded-pill px-2 py-1">Cukup</span>
                                    @else
                                        <span class="badge bg-danger text-white rounded-pill px-2 py-1">Perlu Perhatian</span>
                                    @endif
                                </td>
                                <td class="text-center pe-4">
                                    @if($s->no_hp_ortu)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $s->no_hp_ortu) }}?text={{ $waMsg }}" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-sm">
                                            <i class="bi bi-whatsapp me-1"></i> WA Ortu
                                        </a>
                                    @else
                                        <span class="badge bg-light text-muted border">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">
                                    Belum ada data siswa di kelas {{ $kelas }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    {{-- ========================================================================= --}}
    {{-- 3. TAMPILAN MODE PRESENSI HARIAN                                          --}}
    {{-- ========================================================================= --}}
    @else
        <!-- Metric Cards Harian -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-3 col-xl">
                <div class="stat-card border-start border-4 border-primary">
                    <span class="text-muted small">Total Siswa Kelas {{ $kelas }}</span>
                    <h3 class="fw-bold mb-0 text-dark">{{ $totalSiswaKelas }}</h3>
                </div>
            </div>
            <div class="col-sm-6 col-md-3 col-xl">
                <div class="stat-card border-start border-4 border-success">
                    <span class="text-muted small">Hadir Tepat Waktu</span>
                    <h3 class="fw-bold mb-0 text-success">{{ $harianTepatCount }}</h3>
                </div>
            </div>
            <div class="col-sm-6 col-md-3 col-xl">
                <div class="stat-card border-start border-4 border-warning">
                    <span class="text-muted small">Terlambat</span>
                    <h3 class="fw-bold mb-0 text-warning">{{ $harianLambatCount }}</h3>
                </div>
            </div>
            <div class="col-sm-6 col-md-3 col-xl">
                <div class="stat-card border-start border-4 border-info">
                    <span class="text-muted small">Izin & Sakit</span>
                    <h3 class="fw-bold mb-0 text-info">{{ $harianIzinCount + $harianSakitCount }}</h3>
                </div>
            </div>
            <div class="col-sm-6 col-md-3 col-xl">
                <div class="stat-card border-start border-4 border-danger">
                    <span class="text-muted small">Alpha / Belum Scan</span>
                    <h3 class="fw-bold mb-0 text-danger">{{ $harianAlfaCount }}</h3>
                </div>
            </div>
        </div>

        <!-- Student Daily Table -->
        <div class="card-custom p-0 overflow-hidden mb-4">
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-clock-history text-primary me-2"></i> Presensi Hari: {{ \Carbon\Carbon::parse($tanggalHarian)->translatedFormat('d F Y') }}
                </h6>
                <span class="badge bg-success rounded-pill px-3 py-2">
                    Kehadiran: {{ $harianPersentase }}%
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr style="font-size: 0.8rem; text-transform: uppercase;">
                            <th class="ps-4">Siswa</th>
                            <th>NIS & NISN</th>
                            <th>Jam Scan</th>
                            <th>Status Hari Ini</th>
                            <th class="text-center pe-4">Hubungi Orang Tua</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rekapHarianSiswa as $row)
                            @php
                                $s = $row['siswa'];
                                $waMsgHarian = urlencode("Assalamualaikum Wr. Wb. Bpk/Ibu wali dari {$s->nama} (Kelas {$s->kelas} MAN 2 Bandar Lampung). Menginformasikan bahwa ananda hari ini (" . \Carbon\Carbon::parse($tanggalHarian)->translatedFormat('d M Y') . ") berstatus: " . strtoupper($row['status']) . ". Terima kasih.");
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $s->foto_url }}" alt="{{ $s->nama }}" class="rounded-circle border shadow-sm" style="width: 38px; height: 38px; object-fit: cover;">
                                        <div>
                                            <strong class="text-dark d-block">{{ $s->nama }}</strong>
                                            <small class="text-muted">Kelas {{ $s->kelas }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="d-block small">NIS: <strong>{{ $s->nis }}</strong></span>
                                    <span class="text-muted small">NISN: {{ $s->nisn ?: '-' }}</span>
                                </td>
                                <td>
                                    <span class="fw-mono">{{ $row['jam_scan'] ? substr($row['jam_scan'], 0, 5) . ' WIB' : '-' }}</span>
                                </td>
                                <td>
                                    @if($row['status'] === 'Tepat Waktu')
                                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2">
                                            <i class="bi bi-check-circle-fill me-1"></i> Tepat Waktu
                                        </span>
                                    @elseif($row['status'] === 'Terlambat')
                                        <span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-2">
                                            <i class="bi bi-clock-fill me-1"></i> Terlambat
                                        </span>
                                    @elseif(in_array($row['status'], ['Izin', 'Ijin']))
                                        <span class="badge bg-info-subtle text-info rounded-pill px-3 py-2">
                                            <i class="bi bi-info-circle-fill me-1"></i> Izin
                                        </span>
                                    @elseif($row['status'] === 'Sakit')
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2">
                                            <i class="bi bi-hospital-fill me-1"></i> Sakit
                                        </span>
                                    @else
                                        <span class="badge bg-danger rounded-pill px-3 py-2">
                                            <i class="bi bi-x-circle-fill me-1"></i> Alpha
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center pe-4">
                                    @if($s->no_hp_ortu)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $s->no_hp_ortu) }}?text={{ $waMsgHarian }}" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                            <i class="bi bi-whatsapp me-1"></i> Chat WhatsApp
                                        </a>
                                    @else
                                        <span class="badge bg-light text-muted border">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    Belum ada data siswa di kelas {{ $kelas }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    function switchMode(newMode) {
        document.getElementById('filterMode').value = newMode;
        document.getElementById('formFilterWali').submit();
    }
</script>
@endpush
