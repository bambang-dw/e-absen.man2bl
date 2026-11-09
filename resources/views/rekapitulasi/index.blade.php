@extends('layouts.admin', [
'title' => 'Rekapitulasi Presensi',
'pageTitle' => 'Rekapitulasi Presensi Siswa',
'pageSubtitle' => 'Laporan kehadiran harian, bulanan, dan semester (Juli - Des & Jan - Juli)'
])

@section('content')
<div class="container-fluid px-0">

    <!-- 1. FILTER TERPADU: PERIODE (HARIAN, BULAN, SEMESTER), KELAS, & SESI -->
    <div class="card-custom p-4 mb-4">
        <form action="{{ route('rekapitulasi.index') }}" method="GET" class="row g-3 align-items-end" id="filterRekapForm">
            <!-- Mode Periode -->
            <div class="col-md-2">
                <label class="form-label small fw-bold text-dark">Mode Periode</label>
                <select name="periode" id="periodeSelector" class="form-select bg-light" onchange="togglePeriodeInputs(this.value)">
                    <option value="harian" {{ $periode === 'harian' ? 'selected' : '' }}>Harian (Tanggal)</option>
                    <option value="bulan" {{ $periode === 'bulan' ? 'selected' : '' }}>Bulanan (Per Bulan)</option>
                    <option value="semester" {{ $periode === 'semester' ? 'selected' : '' }}>Semester</option>
                </select>
            </div>

            <!-- Input Harian: Tanggal -->
            <div class="col-md-3" id="groupInputHarian" style="{{ $periode === 'harian' ? '' : 'display: none;' }}">
                <label class="form-label small fw-bold text-dark">Tanggal Presensi</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-calendar-event"></i></span>
                    <input type="date" name="tanggal" class="form-control bg-light border-start-0" value="{{ $tanggal }}">
                </div>
            </div>

            <!-- Input Bulanan: Bulan & Tahun -->
            <div class="col-md-3" id="groupInputBulan" style="{{ $periode === 'bulan' ? '' : 'display: none;' }}">
                <label class="form-label small fw-bold text-dark">Pilih Bulan & Tahun</label>
                <div class="input-group">
                    <select name="bulan" class="form-select bg-light">
                        @foreach($namaBulanList as $num => $nama)
                        <option value="{{ $num }}" {{ $bulan == $num ? 'selected' : '' }}>{{ $nama }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="tahun_bulan" class="form-control bg-light" style="max-width: 90px;" value="{{ $tahun }}" min="2020" max="2035" placeholder="Tahun">
                </div>
            </div>

            <!-- Input Semester: Semester 1 (Juli - Des) / Semester 2 (Jan - Juli) & Tahun -->
            <div class="col-md-3" id="groupInputSemester" style="{{ $periode === 'semester' ? '' : 'display: none;' }}">
                <label class="form-label small fw-bold text-dark">Pilih Semester & Tahun</label>
                <div class="input-group">
                    <select name="semester" class="form-select bg-light">
                        <option value="1" {{ $semester === '1' ? 'selected' : '' }}>Semester 1 (Juli - Desember)</option>
                        <option value="2" {{ $semester === '2' ? 'selected' : '' }}>Semester 2 (Januari - Juli)</option>
                    </select>
                    <input type="number" name="tahun_semester" class="form-control bg-light" style="max-width: 90px;" value="{{ $tahun }}" min="2020" max="2035" placeholder="Tahun">
                </div>
            </div>

            <!-- PILIH KELAS (TERINTEGRASI DI DALAM FILTER) -->
            <div class="col-md-2">
                <label class="form-label small fw-bold text-dark">
                    {{ !empty($isWaliKelasOnly) ? 'Kelas Binaan' : 'Pilih Kelas' }}
                </label>
                @if(!empty($isWaliKelasOnly) && $semuaDaftarKelas->count() <= 1)
                    <input type="text" class="form-control bg-light border fw-bold text-primary" value="Kelas {{ $semuaDaftarKelas->first() }}" readonly>
                    <input type="hidden" name="kelas" value="{{ $semuaDaftarKelas->first() }}">
                    @else
                    <select name="kelas" class="form-select bg-light" id="filterPilihKelas">
                        @if(empty($isWaliKelasOnly))
                        <option value="">-- Semua Kelas --</option>
                        @endif
                        @foreach($semuaDaftarKelas as $k)
                        <option value="{{ $k }}" {{ $selectedKelas == $k ? 'selected' : '' }}>Kelas {{ $k }}</option>
                        @endforeach
                    </select>
                    @endif
            </div>

            <!-- Sesi Absensi -->
            <div class="col-md-2">
                <label class="form-label small fw-bold text-dark">Sesi Absensi</label>
                <select name="sesi_id" class="form-select bg-light">
                    <option value="all" {{ $sesiId === 'all' ? 'selected' : '' }}>-- Semua Sesi --</option>
                    @foreach($daftarSesi as $s)
                    <option value="{{ $s->id }}" {{ $sesiId == $s->id ? 'selected' : '' }}>
                        {{ $s->nama_sesi }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-md-3 d-flex flex-wrap gap-2 justify-content-md-end">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                    <i class="bi bi-funnel-fill me-1"></i> Terapkan
                </button>

                @php
                $exportParams = request()->all();
                @endphp
                <a href="{{ route('rekapitulasi.export', $exportParams) }}" class="btn btn-success rounded-pill px-3 fw-semibold shadow-sm" title="Download Excel Sesuai Filter">
                    <i class="bi bi-file-earmark-excel-fill me-1"></i> Excel
                </a>

                <button type="button" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm" onclick="window.print()">
                    <i class="bi bi-printer-fill me-1"></i> Cetak
                </button>
            </div>
        </form>
    </div>

    <!-- 2. SUMMARY STATISTIK PERIODE -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card-custom p-3 border-start border-4 border-primary h-100">
                <span class="text-muted small">Total Siswa</span>
                <h4 class="fw-bold mb-0 text-dark">{{ $grandTotal['total_siswa'] }}</h4>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card-custom p-3 border-start border-4 border-success h-100 cursor-pointer" onclick="selectKategori('Tepat Waktu', true)" title="Klik untuk lihat rincian Tepat Waktu" style="cursor: pointer;">
                <span class="text-muted small">Tepat Waktu</span>
                <h4 class="fw-bold mb-0 text-success">{{ $grandTotal['tepat_waktu'] }}</h4>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card-custom p-3 border-start border-4 border-warning h-100 cursor-pointer" onclick="selectKategori('Terlambat', true)" title="Klik untuk lihat rincian Terlambat" style="cursor: pointer;">
                <span class="text-muted small">Terlambat</span>
                <h4 class="fw-bold mb-0 text-warning">{{ $grandTotal['terlambat'] }}</h4>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card-custom p-3 border-start border-4 border-info h-100 cursor-pointer" onclick="selectKategori('Izin', true)" title="Klik untuk lihat rincian Izin" style="cursor: pointer;">
                <span class="text-muted small">Izin / Sakit</span>
                <h4 class="fw-bold mb-0 text-info">{{ $grandTotal['izin'] + $grandTotal['sakit'] }}</h4>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card-custom p-3 border-start border-4 border-danger h-100 cursor-pointer" onclick="selectKategori('Alfa', true)" title="Klik untuk lihat rincian Alpha" style="cursor: pointer;">
                <span class="text-muted small">Alpha / Belum</span>
                <h4 class="fw-bold mb-0 text-danger">{{ $grandTotal['alfa'] }}</h4>
            </div>
        </div>
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card-custom p-3 border-start border-4 border-indigo-500 h-100" style="border-left-color: #6366f1 !important;">
                <span class="text-muted small">% Kehadiran</span>
                <h4 class="fw-bold mb-0" style="color: #4f46e5;">{{ $grandTotal['persentase'] }}%</h4>
            </div>
        </div>
    </div>

    <!-- 3. DIAGRAM BATANG BERDASARKAN KATEGORI (CHART.JS) -->
    <div class="card-custom p-4 mb-4 shadow-sm border">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
            <div>
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-bar-chart-fill text-primary me-2"></i> Diagram Batang Rekapitulasi Presensi per Kategori
                </h5>
                <small class="text-muted">
                    Rekapitulasi periode: <strong>{{ $namaPeriode }}</strong> | Filter: <span class="badge bg-light text-dark border">{{ $selectedKelas ? 'Kelas ' . $selectedKelas : 'Semua Kelas' }}</span>. <span class="text-primary fw-bold"><i class="bi bi-hand-index-thumb"></i> Klik salah satu batang diagram untuk memfilter dan menampilkan rincian siswa di bawah.</span>
                </small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-emerald-subtle text-success px-3 py-2 rounded-pill fw-bold" style="background-color: #d1fae5; color: #065f46;">
                    <i class="bi bi-calendar-check me-1"></i> {{ $namaPeriode }}
                </span>
                <span id="activeCategoryBadge" class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-semibold">
                    <i class="bi bi-filter me-1"></i> <span id="activeCategoryName">Semua Status</span>
                </span>
            </div>
        </div>

        <!-- Chart Canvas -->
        <div class="position-relative" style="min-height: 320px; max-height: 380px;">
            <canvas id="categoryBarChart"
                data-category='@json($categoryData)'
                data-is-harian="{{ $isHarian ? '1' : '0' }}"
                data-selected-kategori="{{ $selectedKategori ?? '' }}"></canvas>
        </div>

        <!-- Clickable Category Legend -->
        <div class="d-flex flex-wrap align-items-center justify-content-center gap-3 pt-3 mt-3 border-top small">
            <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 btn-kategori-filter" data-kategori="Tepat Waktu" onclick="selectKategori('Tepat Waktu', true)">
                <span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background: #10b981;"></span> Tepat Waktu: <strong id="legendCountTepat">{{ $grandTotal['tepat_waktu'] }}</strong>
            </button>
            <button type="button" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3 py-1 btn-kategori-filter" data-kategori="Terlambat" onclick="selectKategori('Terlambat', true)">
                <span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background: #f59e0b;"></span> Terlambat: <strong id="legendCountTerlambat">{{ $grandTotal['terlambat'] }}</strong>
            </button>
            <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3 py-1 btn-kategori-filter" data-kategori="Izin" onclick="selectKategori('Izin', true)">
                <span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background: #0ea5e9;"></span> Izin: <strong id="legendCountIzin">{{ $grandTotal['izin'] }}</strong>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 btn-kategori-filter" data-kategori="Sakit" onclick="selectKategori('Sakit', true)">
                <span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background: #64748b;"></span> Sakit: <strong id="legendCountSakit">{{ $grandTotal['sakit'] }}</strong>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 btn-kategori-filter" data-kategori="Alfa" onclick="selectKategori('Alfa', true)">
                <span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background: #ef4444;"></span> Alpha: <strong id="legendCountAlfa">{{ $grandTotal['alfa'] }}</strong>
            </button>
            <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 py-1" onclick="selectKategori('', false)">
                <i class="bi bi-x-circle me-1"></i> Tampilkan Semua
            </button>
        </div>
    </div>

    <!-- 5. TABEL REKAPITULASI SEMUA KELAS & JUMLAH TOTAL (OVERVIEW LENGKAP) -->
    <div class="card-custom p-0 overflow-hidden mb-4 shadow-sm" id="printable-rekap">
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-table me-2 text-primary"></i> Tabel Presensi Siswa per Kelas
                </h6>
                <small class="text-muted">
                    Periode: <strong>{{ $namaPeriode }}</strong> | Sesi: <strong>{{ $selectedSesi ? $selectedSesi->nama_sesi : 'Semua Sesi' }}</strong>
                </small>
            </div>
            <span class="badge bg-primary rounded-pill px-3 py-2">
                {{ count($tabelKelas) }} Kelas
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light">
                    <tr style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">
                        <th style="width: 50px;">No</th>
                        <th class="text-start">Nama Kelas</th>
                        <th class="text-start">Wali Kelas</th>
                        <th>Total Siswa</th>
                        <th class="text-success">Tepat Waktu</th>
                        <th class="text-warning">Terlambat</th>
                        <th class="text-info">Izin</th>
                        <th class="text-secondary">Sakit</th>
                        <th class="text-danger">Alpha</th>
                        <th class="text-dark">Total Hadir</th>
                        <th>% Kehadiran</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tabelKelas as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="text-start">
                            <span class="fw-bold text-dark">Kelas {{ $row['kelas'] }}</span>
                        </td>
                        <td class="text-start text-muted small">
                            {{ $row['wali_kelas'] ?? '-' }}
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $row['total_siswa'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill">{{ $row['tepat_waktu'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-warning-subtle text-warning px-2 py-1 rounded-pill">{{ $row['terlambat'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-info-subtle text-info px-2 py-1 rounded-pill">{{ $row['izin'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill">{{ $row['sakit'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-danger-subtle text-danger px-2 py-1 rounded-pill">{{ $row['alfa'] }}</span>
                        </td>
                        <td>
                            <strong class="text-dark">{{ $row['total_hadir'] }}</strong>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <div class="progress flex-grow-1" style="height: 6px; max-width: 60px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $row['persentase'] }}%;"></div>
                                </div>
                                <span class="small fw-bold">{{ $row['persentase'] }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="py-5 text-center text-muted">
                            Tidak ada data kelas yang tersedia.
                        </td>
                    </tr>
                    @endforelse
                </tbody>

                <!-- BARIS JUMLAH TOTAL (GRAND TOTAL) -->
                <tfoot class="table-secondary border-top-2">
                    <tr class="fw-bold text-dark" style="font-size: 0.95rem; background-color: #e2e8f0;">
                        <td colspan="3" class="text-center py-3">
                            <span class="text-uppercase tracking-wider">JUMLAH TOTAL</span>
                        </td>
                        <td>
                            <span class="badge bg-dark text-white fs-6">{{ $grandTotal['total_siswa'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-success fs-6">{{ $grandTotal['tepat_waktu'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-warning text-dark fs-6">{{ $grandTotal['terlambat'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-info fs-6">{{ $grandTotal['izin'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-secondary fs-6">{{ $grandTotal['sakit'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-danger fs-6">{{ $grandTotal['alfa'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-primary fs-6">{{ $grandTotal['total_hadir'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-indigo-500 text-white fs-6" style="background-color: #4f46e5;">{{ $grandTotal['persentase'] }}%</span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- 4. TABEL DETAIL SISWA PRESENSI (MUNCUL & TERSARING KETIKA BATANG DIKLIK) -->
    <div class="card-custom p-0 overflow-hidden mb-4 shadow-sm border" id="detailSiswaSection">
        <div class="p-4 text-white" style="background: linear-gradient(135deg, #064e3b 0%, #047857 100%);">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-white text-success fw-bold px-3 py-1 rounded-pill" id="detailClassPill">
                            <i class="bi bi-door-open-fill me-1"></i> <span id="detailClassPillText">{{ $selectedKelas ? 'Kelas ' . $selectedKelas : 'Semua Kelas' }}</span>
                        </span>
                        <span class="badge bg-white bg-opacity-25 text-white rounded-pill px-3 py-1" id="detailStatusPill">
                            <i class="bi bi-tag-fill me-1"></i> <span id="detailStatusPillText">Semua Status</span>
                        </span>
                        <span class="badge bg-white bg-opacity-25 text-white rounded-pill px-3 py-1">
                            <i class="bi bi-calendar3 me-1"></i> {{ $namaPeriode }}
                        </span>
                    </div>
                    <h4 class="fw-bold mb-1 text-white" id="detailMainHeading">
                        Detail Presensi Siswa <span id="detailKelasHeadingSuffix">{{ $selectedKelas ? 'Kelas ' . $selectedKelas : 'Semua Kelas' }}</span>
                    </h4>
                    <small class="text-white-50">
                        Periode: <strong>{{ $namaPeriode }}</strong> | Sesi: <strong>{{ $selectedSesi ? $selectedSesi->nama_sesi : 'Semua Sesi' }}</strong> | Menampilkan: <strong id="visibleStudentCount" class="text-white">{{ count($semuaSiswa) }}</strong> Siswa
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-light btn-sm rounded-pill px-3 fw-bold text-dark shadow-sm" onclick="resetFilters()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Category Filter Tabs above Table -->
        <div class="p-3 bg-light border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="small fw-bold text-muted me-1">Kategori:</span>
                <button type="button" class="btn btn-sm btn-kategori-tab rounded-pill px-3 active" data-tab-kategori="" onclick="selectKategori('', false)">
                    Semua (<span id="tabCountSemua">{{ count($semuaSiswa) }}</span>)
                </button>
                <button type="button" class="btn btn-sm btn-kategori-tab rounded-pill px-3" data-tab-kategori="Tepat Waktu" onclick="selectKategori('Tepat Waktu', false)">
                    Tepat Waktu (<span id="tabCountTepat">{{ $grandTotal['tepat_waktu'] }}</span>)
                </button>
                <button type="button" class="btn btn-sm btn-kategori-tab rounded-pill px-3" data-tab-kategori="Terlambat" onclick="selectKategori('Terlambat', false)">
                    Terlambat (<span id="tabCountTerlambat">{{ $grandTotal['terlambat'] }}</span>)
                </button>
                <button type="button" class="btn btn-sm btn-kategori-tab rounded-pill px-3" data-tab-kategori="Izin" onclick="selectKategori('Izin', false)">
                    Izin (<span id="tabCountIzin">{{ $grandTotal['izin'] }}</span>)
                </button>
                <button type="button" class="btn btn-sm btn-kategori-tab rounded-pill px-3" data-tab-kategori="Sakit" onclick="selectKategori('Sakit', false)">
                    Sakit (<span id="tabCountSakit">{{ $grandTotal['sakit'] }}</span>)
                </button>
                <button type="button" class="btn btn-sm btn-kategori-tab rounded-pill px-3" data-tab-kategori="Alfa" onclick="selectKategori('Alfa', false)">
                    Alpha (<span id="tabCountAlfa">{{ $grandTotal['alfa'] }}</span>)
                </button>
            </div>
            <div>
                <input type="text" id="studentSearchInput" class="form-control form-control-sm bg-white" placeholder="Cari nama / NIS siswa..." style="max-width: 220px;" onkeyup="filterStudentTable()">
            </div>
        </div>

        <!-- Student Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="studentRosterTable">
                <thead class="table-light">
                    <tr style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">
                        <th class="ps-3" style="width: 50px;">No</th>
                        <th style="width: 55px;">Foto</th>
                        <th>NIS & NISN</th>
                        <th>Nama Lengkap Siswa</th>
                        <th>Kelas</th>
                        @if($isHarian)
                        <th class="text-center">Jam Scan</th>
                        @else
                        <th class="text-center" title="Tepat Waktu">Tepat</th>
                        <th class="text-center" title="Terlambat">Lambat</th>
                        <th class="text-center" title="Izin">Izin</th>
                        <th class="text-center" title="Sakit">Sakit</th>
                        <th class="text-center" title="Alpha">Alpha</th>
                        <th class="text-center">Total Hadir</th>
                        @endif
                        <th class="text-center">Status Presensi</th>
                        <th class="text-end pe-3">Kontak Orang Tua</th>
                    </tr>
                </thead>
                <tbody id="studentRosterBody">
                    @forelse($semuaSiswa as $idx => $s)
                    @php
                    $normalizedStatus = ($s['status'] === 'Ijin') ? 'Izin' : $s['status'];
                    @endphp
                    <tr class="student-row" data-kelas="{{ $s['kelas'] }}" data-status="{{ $normalizedStatus }}"
                        data-tepat="{{ $s['tepat'] ?? 0 }}" data-lambat="{{ $s['lambat'] ?? 0 }}"
                        data-izin="{{ $s['izin'] ?? 0 }}" data-sakit="{{ $s['sakit'] ?? 0 }}"
                        data-alfa="{{ $s['alfa'] ?? 0 }}">
                        <td class="ps-3 text-muted row-number">{{ $idx + 1 }}</td>
                        <td>
                            <img src="{{ $s['foto_url'] }}" alt="{{ $s['nama'] }}" class="rounded-circle border" style="width: 36px; height: 36px; object-fit: cover;">
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $s['nis'] }}</span>
                            <small class="text-muted d-block" style="font-size: 0.72rem;">NISN: {{ $s['nisn'] ?: '-' }}</small>
                        </td>
                        <td>
                            <span class="fw-bold text-dark d-block student-name">{{ $s['nama'] }}</span>
                        </td>
                        <td>
                            <span class="badge bg-emerald-subtle text-success px-2 py-1 rounded-pill fw-bold" style="background-color: #d1fae5; color: #065f46;">
                                Kelas {{ $s['kelas'] }}
                            </span>
                        </td>
                        @if($isHarian)
                        <td class="text-center">
                            @if($s['jam_scan'] && $s['jam_scan'] !== '-')
                            <span class="badge bg-light text-dark border font-monospace">
                                <i class="bi bi-clock me-1"></i> {{ substr($s['jam_scan'], 0, 5) }}
                            </span>
                            @else
                            <span class="text-muted small">-</span>
                            @endif
                        </td>
                        @else
                        <td class="text-center"><span class="badge bg-success-subtle text-success rounded-pill px-2">{{ $s['tepat'] ?? 0 }}</span></td>
                        <td class="text-center"><span class="badge bg-warning-subtle text-warning rounded-pill px-2">{{ $s['lambat'] ?? 0 }}</span></td>
                        <td class="text-center"><span class="badge bg-info-subtle text-info rounded-pill px-2">{{ $s['izin'] ?? 0 }}</span></td>
                        <td class="text-center"><span class="badge bg-secondary-subtle text-secondary rounded-pill px-2">{{ $s['sakit'] ?? 0 }}</span></td>
                        <td class="text-center"><span class="badge bg-danger-subtle text-danger rounded-pill px-2">{{ $s['alfa'] ?? 0 }}</span></td>
                        <td class="text-center">
                            <strong class="text-dark">{{ $s['total_hadir'] ?? 0 }}</strong>
                            <small class="text-muted d-block">({{ $s['persentase'] ?? 0 }}%)</small>
                        </td>
                        @endif
                        <td class="text-center">
                            @if($normalizedStatus === 'Tepat Waktu')
                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1">
                                <i class="bi bi-check-circle-fill me-1"></i> Tepat Waktu
                            </span>
                            @elseif($normalizedStatus === 'Terlambat')
                            <span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-1">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Terlambat
                            </span>
                            @elseif($normalizedStatus === 'Izin')
                            <span class="badge bg-info-subtle text-info rounded-pill px-3 py-1">
                                <i class="bi bi-envelope-paper-fill me-1"></i> Izin
                            </span>
                            @elseif($normalizedStatus === 'Sakit')
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">
                                <i class="bi bi-bandaid-fill me-1"></i> Sakit
                            </span>
                            @else
                            <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1">
                                <i class="bi bi-x-circle-fill me-1"></i> Alpha
                            </span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            @if(!empty($s['no_hp_ortu']))
                            @php
                            $cleanHp = preg_replace('/[^0-9]/', '', $s['no_hp_ortu']);
                            if (str_starts_with($cleanHp, '0')) {
                            $cleanHp = '62' . substr($cleanHp, 1);
                            }
                            $waText = "Halo Bapak/Ibu wali siswa {$s['nama']} (Kelas {$s['kelas']}), menginformasikan rekap presensi ({$namaPeriode}): {$normalizedStatus}. Terima kasih - MAN 2 Bandar Lampung.";
                            @endphp
                            <a href="https://wa.me/{{ $cleanHp }}?text={{ urlencode($waText) }}" target="_blank" class="btn btn-outline-success btn-sm rounded-pill px-3" title="Kirim Pesan WhatsApp ke Orang Tua">
                                <i class="bi bi-whatsapp me-1"></i> {{ $s['no_hp_ortu'] }}
                            </a>
                            @else
                            <span class="text-muted small">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr id="emptyStudentRow">
                        <td colspan="{{ $isHarian ? 8 : 13 }}" class="py-5 text-center text-muted">
                            Tidak ada data siswa yang tersedia untuk periode ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="noMatchingStudentsAlert" class="p-4 text-center text-muted d-none">
            <i class="bi bi-search fs-2 text-muted d-block mb-2"></i>
            <h6 class="fw-bold text-dark">Tidak Ada Data Siswa yang Cocok</h6>
            <p class="small text-muted mb-2">Tidak ditemukan siswa pada filter kategori ini.</p>
            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="resetFilters()">
                Tampilkan Semua Siswa
            </button>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    .btn-kategori-tab {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #475569;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .btn-kategori-tab:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .btn-kategori-tab.active {
        background: #0f172a !important;
        color: #ffffff !important;
        border-color: #0f172a !important;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.2);
    }

    @media print {

        #sidebar-wrapper,
        .topbar,
        footer,
        .card-custom form,
        .btn,
        .no-print,
        .btn-kategori-tab {
            display: none !important;
        }

        #content-wrapper {
            margin-left: 0 !important;
        }

        body {
            background: #ffffff !important;
        }

        #printable-rekap {
            border: none !important;
            box-shadow: none !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Data structures from controller dataset
    const chartEl = document.getElementById('categoryBarChart');
    let categoryData = {
        labels: [],
        values: [],
        colors: [],
        borderColors: []
    };
    try {
        categoryData = chartEl ? JSON.parse(chartEl.dataset.category || '{}') : categoryData;
    } catch (e) {
        categoryData = {
            labels: [],
            values: [],
            colors: [],
            borderColors: []
        };
    }
    const isHarianMode = chartEl ? (chartEl.dataset.isHarian === '1') : false;

    // Current State
    let currentSelectedKategori = chartEl ? (chartEl.dataset.selectedKategori || '') : '';
    let categoryChartInstance = null;

    const categoryNames = ['Tepat Waktu', 'Terlambat', 'Izin', 'Sakit', 'Alpha'];

    document.addEventListener('DOMContentLoaded', function() {
        initCategoryChart();

        if (currentSelectedKategori) {
            selectKategori(currentSelectedKategori, false);
        }
    });

    /**
     * Toggle input fields according to chosen period (Harian / Bulan / Semester)
     */
    function togglePeriodeInputs(mode) {
        const groupHarian = document.getElementById('groupInputHarian');
        const groupBulan = document.getElementById('groupInputBulan');
        const groupSemester = document.getElementById('groupInputSemester');

        if (groupHarian) groupHarian.style.display = (mode === 'harian') ? '' : 'none';
        if (groupBulan) groupBulan.style.display = (mode === 'bulan') ? '' : 'none';
        if (groupSemester) groupSemester.style.display = (mode === 'semester') ? '' : 'none';
    }

    /**
     * Initialize Category Bar Chart with 5 status categories
     */
    function initCategoryChart() {
        const ctx = document.getElementById('categoryBarChart');
        if (!ctx) return;

        categoryChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: categoryData.labels,
                datasets: [{
                    label: 'Jumlah Presensi Siswa',
                    data: categoryData.values,
                    backgroundColor: categoryData.colors,
                    borderColor: categoryData.borderColors,
                    borderWidth: 1.5,
                    borderRadius: 8,
                    barPercentage: 0.6,
                    categoryPercentage: 0.8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onHover: (event, chartElement) => {
                    event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                },
                onClick: (event, elements) => {
                    if (elements && elements.length > 0) {
                        const index = elements[0].index;
                        const clickedCategory = categoryNames[index];
                        if (clickedCategory) {
                            if (currentSelectedKategori === clickedCategory) {
                                selectKategori('', true);
                            } else {
                                selectKategori(clickedCategory, true);
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` Jumlah: ${context.parsed.y} Presensi`;
                            },
                            afterBody: function() {
                                return '\n💡 Klik batang ini untuk melihat rincian siswa!';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Handler when a Category is clicked (e.g. on Bar Chart or Filter buttons)
     */
    function selectKategori(kategori, shouldScroll = false) {
        currentSelectedKategori = kategori;

        // 1. Update category badge in chart
        const activeCategoryName = document.getElementById('activeCategoryName');
        if (activeCategoryName) {
            activeCategoryName.textContent = kategori || 'Semua Status';
        }

        // 2. Update pill status in table header
        const detailStatusPillText = document.getElementById('detailStatusPillText');
        if (detailStatusPillText) {
            detailStatusPillText.textContent = kategori ? `Status: ${kategori}` : 'Semua Status';
        }

        // 3. Update active tab styling above table
        document.querySelectorAll('.btn-kategori-tab').forEach(btn => {
            if (btn.getAttribute('data-tab-kategori') === kategori) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // 4. Update legend button styling
        document.querySelectorAll('.btn-kategori-filter').forEach(btn => {
            if (btn.getAttribute('data-kategori') === kategori) {
                btn.classList.add('shadow-sm');
                btn.style.fontWeight = 'bold';
            } else {
                btn.classList.remove('shadow-sm');
                btn.style.fontWeight = 'normal';
            }
        });

        // 5. Filter Student Table
        filterStudentTable();

        // 6. Update URL query without full reload
        updateUrlParams();

        if (shouldScroll) {
            const detailSection = document.getElementById('detailSiswaSection');
            if (detailSection) {
                detailSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    }

    /**
     * Filter the student table rows by category and search keyword
     */
    function filterStudentTable() {
        const searchVal = (document.getElementById('studentSearchInput')?.value || '').toLowerCase().trim();
        const rows = document.querySelectorAll('#studentRosterBody .student-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const rowStatus = row.getAttribute('data-status');
            const studentName = (row.querySelector('.student-name')?.textContent || '').toLowerCase();
            const studentNis = (row.textContent || '').toLowerCase();

            let matchKategori = true;
            if (currentSelectedKategori) {
                if (isHarianMode) {
                    matchKategori = (rowStatus === currentSelectedKategori);
                } else {
                    // In Month/Semester mode, check individual counts or primary status
                    if (currentSelectedKategori === 'Tepat Waktu') {
                        matchKategori = (parseInt(row.getAttribute('data-tepat') || 0) > 0 || rowStatus === 'Tepat Waktu');
                    } else if (currentSelectedKategori === 'Terlambat') {
                        matchKategori = (parseInt(row.getAttribute('data-lambat') || 0) > 0 || rowStatus === 'Terlambat');
                    } else if (currentSelectedKategori === 'Izin') {
                        matchKategori = (parseInt(row.getAttribute('data-izin') || 0) > 0 || rowStatus === 'Izin');
                    } else if (currentSelectedKategori === 'Sakit') {
                        matchKategori = (parseInt(row.getAttribute('data-sakit') || 0) > 0 || rowStatus === 'Sakit');
                    } else if (currentSelectedKategori === 'Alfa') {
                        matchKategori = (parseInt(row.getAttribute('data-alfa') || 0) > 0 || rowStatus === 'Alfa');
                    }
                }
            }

            let matchSearch = (!searchVal || studentName.includes(searchVal) || studentNis.includes(searchVal));

            if (matchKategori && matchSearch) {
                row.classList.remove('d-none');
                visibleCount++;
                const rowNumberEl = row.querySelector('.row-number');
                if (rowNumberEl) rowNumberEl.textContent = visibleCount;
            } else {
                row.classList.add('d-none');
            }
        });

        // Visible counter badge
        const countBadge = document.getElementById('visibleStudentCount');
        if (countBadge) countBadge.textContent = visibleCount;

        // No matching students alert
        const alertBox = document.getElementById('noMatchingStudentsAlert');
        const emptyRow = document.getElementById('emptyStudentRow');
        if (alertBox) {
            if (visibleCount === 0 && !emptyRow) {
                alertBox.classList.remove('d-none');
            } else {
                alertBox.classList.add('d-none');
            }
        }
    }

    /**
     * Reset filters to show all categories
     */
    function resetFilters() {
        const searchInput = document.getElementById('studentSearchInput');
        if (searchInput) searchInput.value = '';
        selectKategori('', false);
    }

    /**
     * Update URL query string parameters
     */
    function updateUrlParams() {
        const url = new URL(window.location.href);
        if (currentSelectedKategori) {
            url.searchParams.set('kategori', currentSelectedKategori);
        } else {
            url.searchParams.delete('kategori');
        }
        window.history.replaceState({}, '', url);
    }
</script>
@endpush