@extends('layouts.admin', [
    'title' => 'Dashboard Guru - Presensi Izin & Sakit',
    'pageTitle' => 'Dashboard Guru & Presensi Siswa',
    'pageSubtitle' => 'MAN 2 Bandar Lampung - Kelola presensi izin/sakit siswa dan pemindaian scanner QR Code',
])

@section('content')
    <div class="container-fluid px-0">

        <!-- Header Banner: Guru & Petugas Scanner -->
        <div class="card-custom p-4 mb-4 border-0 shadow-sm"
            style="background: linear-gradient(135deg, #059669 0%, #047857 50%, #064e3b 100%); color: white;">
            <div class="row align-items-center g-3">
                <div class="col-lg-7">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge bg-white text-success px-3 py-1.5 rounded-pill fw-bold"
                            style="font-size: 0.78rem;">
                            <i class="bi bi-mortarboard-fill me-1"></i> Portal Pendidik & Petugas Presensi
                        </span>
                        @if ($sesiAktif)
                            <span class="badge bg-emerald-400 text-dark px-3 py-1.5 rounded-pill fw-bold"
                                style="background-color: #34d399; font-size: 0.78rem;">
                                <i class="bi bi-broadcast me-1"></i> Sesi Aktif: {{ $sesiAktif->nama_sesi }}
                                ({{ substr($sesiAktif->jam_mulai, 0, 5) }} - {{ substr($sesiAktif->jam_selesai, 0, 5) }})
                            </span>
                        @else
                            <span class="badge bg-secondary text-white px-3 py-1.5 rounded-pill fw-semibold"
                                style="font-size: 0.78rem;">
                                <i class="bi bi-clock me-1"></i> Tidak ada sesi yang aktif
                            </span>
                        @endif
                    </div>
                    <h3 class="fw-bold text-white mb-1">Presensi Digital MAN 2 Bandar Lampung</h3>
                    <p class="text-white-50 mb-0" style="font-size: 0.92rem;">
                        Bapak/Ibu Guru otomatis bertugas sebagai operator pemindai kartu presensi sekaligus dapat memberi
                        catatan resmi siswa yang <strong>Izin</strong> atau <strong>Sakit</strong>.
                    </p>
                </div>
                <div class="col-lg-5 text-lg-end">
                    <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                        <!-- Tombol Absensi Mengajar Guru (Scan QR Kelas) -->
                        <a href="{{ route('guru.absensi-mengajar') }}"
                            class="btn btn-info text-white fw-bold px-3 py-2 rounded-pill shadow-sm d-flex align-items-center gap-2">
                            <i class="bi bi-easel2-fill fs-5"></i>
                            <span>Absensi Mengajar</span>
                        </a>

                        <!-- Tombol Buka Scanner Kamera -->
                        <a href="{{ route('scanner') }}"
                            class="btn btn-warning text-dark fw-bold px-3 py-2 rounded-pill shadow-sm d-flex align-items-center gap-2">
                            <i class="bi bi-camera-fill fs-5"></i>
                            <span>Buka Scanner Kamera</span>
                        </a>

                        <!-- Tombol Catat Izin / Sakit -->
                        <button type="button"
                            class="btn btn-light text-success fw-bold px-3 py-2 rounded-pill shadow-sm d-flex align-items-center gap-2"
                            data-bs-toggle="modal" data-bs-target="#modalTambahIzinSakit">
                            <i class="bi bi-plus-circle-fill fs-5"></i>
                            <span>+ Catat Izin / Sakit</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Pencatatan Absensi Mengajar Hari Ini (pencegahan duplikasi) -->
        {{-- @if (!empty($statusMengajar) && $statusMengajar['total_jadwal'] > 0)
        @php($belumTercatat = $statusMengajar['total_jadwal'] - $statusMengajar['total_tercatat'])
        <div class="card-custom p-3 p-md-4 mb-4 border-start border-4 {{ $statusMengajar['sudah_lengkap'] ? 'border-success' : 'border-warning' }}">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h6 class="fw-bold text-dark mb-1">
                        <i class="bi bi-clipboard2-check-fill {{ $statusMengajar['sudah_lengkap'] ? 'text-success' : 'text-warning' }} me-2"></i>
                        Absensi Mengajar &mdash; {{ $statusMengajar['tanggal'] }}
                    </h6>
                    <small class="text-muted">
                        {{ $statusMengajar['total_tercatat'] }} dari {{ $statusMengajar['total_jadwal'] }} jam mengajar sudah tercatat.
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if ($statusMengajar['sudah_lengkap'])
                        <span class="badge bg-success rounded-pill px-3 py-2 fw-semibold">
                            <i class="bi bi-check-circle-fill me-1"></i> Semua Jam Sudah Tercatat
                        </span>
                    @else
                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2 fw-semibold">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $belumTercatat }} Jam Belum Tercatat
                        </span>
                    @endif
                    <a href="{{ route('guru.absensi-mengajar') }}" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold">
                        <i class="bi bi-qr-code-scan me-1"></i> Catat Sekarang
                    </a>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                @foreach ($statusMengajar['jadwal'] as $jm)
                    <div class="rounded-3 border px-3 py-2 {{ $jm['sudah_tercatat'] ? 'bg-success-subtle border-success-subtle' : 'bg-light border-warning-subtle' }}"
                         title="{{ $jm['sudah_tercatat'] ? 'Sudah dicatat pukul '.($jm['jam_catat'] ?? '-').' WIB' : 'Belum dicatat' }}">
                        <div class="fw-bold" style="font-size: 0.8rem;">
                            <i class="bi {{ $jm['sudah_tercatat'] ? 'bi-check-circle-fill text-success' : 'bi-clock-history text-warning' }} me-1"></i>
                            Jam {{ $jm['jam_ke'] }} &middot; {{ $jm['kelas'] }}
                        </div>
                        <div class="text-muted" style="font-size: 0.72rem;">
                            {{ $jm['mapel'] }} &middot; {{ $jm['waktu'] }}
                            @if ($jm['sudah_tercatat'])
                                <span class="text-success fw-semibold">&middot; tepat {{ $jm['jam_catat'] }} WIB</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($belumTercatat > 0)
                <div class="alert alert-warning border-0 rounded-3 mt-3 mb-0 py-2 px-3 small d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>
                        Jam yang <strong>belum tercatat</strong> di atas wajib diisi melalui <strong>Scan QR Code Kelas</strong>.
                        Sistem mencegah pencatatan ganda untuk kelas &amp; jam yang sama pada tanggal yang sama.
                    </span>
                </div>
            @endif
        </div>
    @endif --}}

        <!-- Quick Stats Cards (4 Kartu) -->
        <div class="row g-3 mb-4">
            <!-- 1. Total Siswa -->
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card h-100 border-start border-4 border-primary" id="guruCardTotalSiswa">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-semibold small">Total Siswa</span>
                        <div class="stat-icon bg-primary-subtle text-primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-0 text-dark" id="guruStatTotalSiswa">{{ $totalSiswa }}</h3>
                    <small class="text-muted">Terdaftar di MAN 2</small>
                </div>
            </div>

            <!-- 2. Hadir (Tepat Waktu + Terlambat) -->
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card h-100 border-start border-4 border-success" id="guruCardHadir">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-semibold small">Hadir Hari Ini</span>
                        <div class="stat-icon bg-success-subtle text-success">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-0 text-success" id="guruStatHadir">{{ $jumlahTepatWaktu + $jumlahTerlambat }}</h3>
                    <small class="text-muted" id="guruStatDetailHadir">{{ $jumlahTepatWaktu }} tepat, {{ $jumlahTerlambat }}
                        terlambat</small>
                </div>
            </div>

            <!-- 3. Siswa Izin -->
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card h-100 border-start border-4 border-info" id="guruCardIzin">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-semibold small">Siswa Izin</span>
                        <div class="stat-icon bg-info-subtle text-info">
                            <i class="bi bi-envelope-paper-fill"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-0 text-info" id="guruStatIzin">{{ $jumlahIzin }}</h3>
                    <small class="text-muted">Keterangan izin resmi tercatat</small>
                </div>
            </div>

            <!-- 4. Siswa Sakit -->
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card h-100 border-start border-4 border-warning" id="guruCardSakit">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-semibold small">Siswa Sakit</span>
                        <div class="stat-icon bg-warning-subtle text-warning">
                            <i class="bi bi-hospital-fill"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-0 text-warning" id="guruStatSakit">{{ $jumlahSakit }}</h3>
                    <small class="text-muted">Keterangan sakit tercatat</small>
                </div>
            </div>
        </div>

        <!-- Section Utama: Menu & Tabel Siswa Izin / Sakit -->
        <div class="card-custom p-0 overflow-hidden mb-4">
            <!-- Header & Action Bar -->
            <div class="p-4 bg-white border-bottom">
                <div class="row align-items-center g-3">
                    <div class="col-lg-6">
                        <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                            <i class="bi bi-clipboard2-pulse-fill text-success"></i>
                            <span>Pencatatan Presensi Siswa Izin & Sakit</span>
                        </h5>
                        <p class="text-muted small mb-0">
                            Beri keterangan alasan ketidakhadiran siswa yang izin atau sakit agar rekapitulasi presensi
                            akurat.
                        </p>
                    </div>
                    <!-- <div class="col-lg-6 text-lg-end">
                        <button type="button" class="btn btn-success rounded-pill px-4 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahIzinSakit">
                            <i class="bi bi-plus-circle-fill me-1"></i> Catat Izin / Sakit Siswa
                        </button>
                    </div> -->
                </div>

                <hr class="my-3 opacity-25">

                <!-- Filter Tanggal, Kelas, Status, & Cari -->
                <form action="{{ route('guru.dashboard') }}" method="GET" class="row g-2 align-items-center">
                    <!-- Filter Tanggal -->
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Tanggal Presensi:</label>
                        <input type="date" name="tanggal" class="form-control form-control-sm bg-light"
                            value="{{ $tanggal }}" onchange="this.form.submit()">
                    </div>

                    <!-- Filter Kelas -->
                    <div class="col-sm-6 col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">Pilih Kelas:</label>
                        <select name="kelas" class="form-select form-select-sm bg-light" onchange="this.form.submit()">
                            <option value="">-- Semua Kelas --</option>
                            @foreach ($daftarKelas as $k)
                                <option value="{{ $k }}" {{ $kelasFilter === $k ? 'selected' : '' }}>Kelas
                                    {{ $k }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Status -->
                    <div class="col-sm-6 col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">Status Kehadiran:</label>
                        <select name="status" class="form-select form-select-sm bg-light" onchange="this.form.submit()">
                            <option value="">-- Izin & Sakit --</option>
                            <option value="Izin" {{ $statusFilter === 'Izin' ? 'selected' : '' }}>Hanya Izin</option>
                            <option value="Sakit" {{ $statusFilter === 'Sakit' ? 'selected' : '' }}>Hanya Sakit</option>
                        </select>
                    </div>

                    <!-- Input Pencarian (Live Search) -->
                    <div class="col-sm-6 col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Live Search Nama / NISN:</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="liveSearchTabelInput" class="form-control bg-light"
                                placeholder="Ketik nama atau NISN siswa..." value="{{ $search }}"
                                autocomplete="off">
                            @if ($search || $kelasFilter || $statusFilter || $tanggal !== $today)
                                <a href="{{ route('guru.dashboard') }}" class="btn btn-light border"
                                    title="Reset Filter"><i class="bi bi-x-circle"></i></a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabel Daftar Siswa Izin & Sakit -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tabelIzinSakit">
                    <thead class="table-light">
                        <tr style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">
                            <th class="ps-4" style="width: 50px;">No</th>
                            <th>Identitas Siswa</th>
                            <th>Kelas</th>
                            <th>Status</th>
                            <th>Sesi</th>
                            <th>Keterangan / Alasan</th>
                            <th>Waktu Input</th>
                            <th class="text-end pe-4" style="width: 130px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($daftarIzinSakit as $idx => $item)
                            <tr class="baris-izin-sakit"
                                data-search="{{ strtolower(($item->siswa?->nama ?? '') . ' ' . ($item->siswa?->nisn ?? '') . ' ' . $item->nis . ' ' . ($item->siswa?->kelas ?? '')) }}">
                                <!-- No -->
                                <td class="ps-4 text-muted small">
                                    {{ $daftarIzinSakit->firstItem() + $idx }}
                                </td>

                                <!-- Identitas Siswa -->
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="{{ $item->siswa ? $item->siswa->foto_url : 'https://ui-avatars.com/api/?name=Siswa' }}"
                                            alt="{{ $item->siswa?->nama ?? 'Siswa' }}"
                                            class="rounded-circle border shadow-sm flex-shrink-0"
                                            style="width: 38px; height: 38px; object-fit: cover;">
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.92rem;">
                                                {{ $item->siswa?->nama ?? '-' }}
                                            </div>
                                            <small class="text-muted">NISN:
                                                {{ $item->siswa?->nisn ?? $item->nis }}</small>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kelas -->
                                <td>
                                    <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill fw-semibold">
                                        {{ $item->siswa?->kelas ?? '-' }}
                                    </span>
                                </td>

                                <!-- Status (Izin / Sakit) -->
                                <td>
                                    @if (in_array($item->status, ['Izin', 'Ijin']))
                                        <span
                                            class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3 py-1 fw-semibold">
                                            <i class="bi bi-envelope-paper-fill me-1"></i> Izin
                                        </span>
                                    @elseif($item->status === 'Sakit')
                                        <span
                                            class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 fw-semibold">
                                            <i class="bi bi-hospital-fill me-1"></i> Sakit
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">
                                            {{ $item->status }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Sesi -->
                                <td>
                                    <small class="text-muted fw-semibold">
                                        {{ $item->sesi?->nama_sesi ?? 'Reguler' }}
                                    </small>
                                </td>

                                <!-- Keterangan / Alasan -->
                                <td>
                                    @if ($item->keterangan)
                                        <span class="text-dark small">{{ $item->keterangan }}</span>
                                    @else
                                        <span class="text-muted small italic">Tidak ada keterangan tertulis</span>
                                    @endif
                                </td>

                                <!-- Waktu Tercatat -->
                                <td>
                                    <small class="text-muted">
                                        {{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}
                                        <br>
                                        <span class="text-secondary"
                                            style="font-size: 0.72rem;">{{ substr($item->jam_scan, 0, 5) }} WIB</span>
                                    </small>
                                </td>

                                <!-- Aksi (Edit & Hapus) -->
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-light border text-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditIzinSakit{{ $item->id }}"
                                            title="Edit Keterangan">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-light border text-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalHapusIzinSakit{{ $item->id }}" title="Hapus Data">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Modal Edit Izin / Sakit -->
                            <div class="modal fade" id="modalEditIzinSakit{{ $item->id }}" tabindex="-1"
                                aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow rounded-4 text-start">
                                        <div class="modal-header border-bottom py-3">
                                            <h5 class="modal-title fw-bold text-dark">
                                                <i class="bi bi-pencil-square text-primary me-2"></i> Edit Presensi Siswa
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <form action="{{ route('guru.izin-sakit.update', $item->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body p-4">
                                                <!-- Info Siswa -->
                                                <div class="p-3 bg-light rounded-3 mb-3 border">
                                                    <div class="fw-bold text-dark">{{ $item->siswa?->nama ?? '-' }}</div>
                                                    <small class="text-muted">Kelas:
                                                        <strong>{{ $item->siswa?->kelas }}</strong> • NISN:
                                                        {{ $item->siswa?->nisn ?? $item->nis }}</small>
                                                </div>

                                                <!-- Status -->
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Status Kehadiran <span
                                                            class="text-danger">*</span></label>
                                                    <div class="d-flex gap-3">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="status"
                                                                id="editStatusIzin{{ $item->id }}" value="Izin"
                                                                {{ in_array($item->status, ['Izin', 'Ijin']) ? 'checked' : '' }}>
                                                            <label class="form-check-label small fw-semibold text-info"
                                                                for="editStatusIzin{{ $item->id }}">
                                                                <i class="bi bi-envelope-paper-fill me-1"></i> Izin
                                                            </label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="status"
                                                                id="editStatusSakit{{ $item->id }}" value="Sakit"
                                                                {{ $item->status === 'Sakit' ? 'checked' : '' }}>
                                                            <label
                                                                class="form-check-label small fw-semibold text-warning-emphasis"
                                                                for="editStatusSakit{{ $item->id }}">
                                                                <i class="bi bi-hospital-fill me-1"></i> Sakit
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Keterangan Alasan -->
                                                <div class="mb-2">
                                                    <label class="form-label small fw-bold">Keterangan / Alasan</label>
                                                    <textarea name="keterangan" class="form-control" rows="3"
                                                        placeholder="Contoh: Sakit demam tinggi / Izin keperluan keluarga">{{ old('keterangan', $item->keterangan) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top bg-light py-3">
                                                <button type="button" class="btn btn-light border rounded-pill px-4"
                                                    data-bs-dismiss="modal">Batal</button>
                                                <button type="submit"
                                                    class="btn btn-primary rounded-pill px-4 fw-semibold">
                                                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Hapus Izin / Sakit -->
                            <div class="modal fade" id="modalHapusIzinSakit{{ $item->id }}" tabindex="-1"
                                aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-sm">
                                    <div class="modal-content border-0 shadow rounded-4 text-center p-3">
                                        <div class="modal-body">
                                            <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center mb-3"
                                                style="width: 52px; height: 52px;">
                                                <i class="bi bi-trash3-fill fs-4"></i>
                                            </div>
                                            <h5 class="fw-bold mb-1">Hapus Presensi?</h5>
                                            <p class="text-muted small mb-3">
                                                Hapus catatan <strong>{{ $item->status }}</strong> untuk
                                                <strong>{{ $item->siswa?->nama }}</strong>?
                                            </p>
                                            <div class="d-flex justify-content-center gap-2">
                                                <button type="button" class="btn btn-light border rounded-pill px-3"
                                                    data-bs-dismiss="modal">Batal</button>
                                                <form action="{{ route('guru.izin-sakit.destroy', $item->id) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="btn btn-danger rounded-pill px-3 fw-semibold">Ya,
                                                        Hapus</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-clipboard2-check fs-2 text-muted d-block mb-2"></i>
                                    <span>Tidak ada siswa yang berstatus Izin atau Sakit pada filter tanggal/kelas yang
                                        dipilih.</span>
                                    <div class="mt-2">
                                        <button type="button"
                                            class="btn btn-outline-success btn-sm rounded-pill px-3 py-1.5"
                                            data-bs-toggle="modal" data-bs-target="#modalTambahIzinSakit">
                                            <i class="bi bi-plus-circle me-1"></i> Catat Siswa Izin / Sakit Sekarang
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($daftarIzinSakit->hasPages())
                <div class="p-3 border-top d-flex justify-content-between align-items-center bg-white">
                    <small class="text-muted">
                        Menampilkan {{ $daftarIzinSakit->firstItem() }} - {{ $daftarIzinSakit->lastItem() }} dari
                        {{ $daftarIzinSakit->total() }} catatan izin & sakit
                    </small>
                    <div>
                        {{ $daftarIzinSakit->links() }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Sesi Aktif & Presensi Terkini Grid -->
        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="card-custom p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-activity text-success me-1"></i> Aktivitas Pemindaian Kamera Terkini
                        </h6>
                        <span class="badge bg-success-subtle text-success border">Live Feed</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Siswa</th>
                                    <th>Kelas</th>
                                    <th>Jam</th>
                                    <th>Status</th>
                                    <th>Petugas</th>
                                </tr>
                            </thead>
                            <tbody id="guruLiveFeedBody" data-flash-success="{{ session('success') ? '1' : '0' }}">
                                @forelse($presensiTerbaru as $p)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ $p->siswa ? $p->siswa->foto_url : 'https://ui-avatars.com/api/?name=Siswa' }}"
                                                    alt="" class="rounded-circle border flex-shrink-0"
                                                    style="width: 34px; height: 34px; object-fit: cover;">
                                                <div>
                                                    <strong
                                                        class="text-dark d-block">{{ $p->siswa?->nama ?? '-' }}</strong>
                                                    <small class="text-muted">{{ $p->nis }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span
                                                class="badge bg-light text-dark border">{{ $p->siswa?->kelas ?? '-' }}</span>
                                        </td>
                                        <td><small class="text-muted">{{ substr($p->jam_scan, 0, 5) }} WIB</small></td>
                                        <td>
                                            @if ($p->status === 'Tepat Waktu')
                                                <span
                                                    class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1">Tepat
                                                    Waktu</span>
                                            @elseif($p->status === 'Terlambat')
                                                <span
                                                    class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2.5 py-1">Terlambat</span>
                                            @elseif(in_array($p->status, ['Izin', 'Ijin']))
                                                <span
                                                    class="badge bg-info-subtle text-info rounded-pill px-2.5 py-1">Izin</span>
                                            @elseif($p->status === 'Sakit')
                                                <span
                                                    class="badge bg-warning-subtle text-warning rounded-pill px-2.5 py-1">Sakit</span>
                                            @else
                                                <span
                                                    class="badge bg-danger-subtle text-danger rounded-pill px-2.5 py-1">{{ $p->status }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($p->petugas)
                                                <span
                                                    class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1"
                                                    style="font-size: 0.78rem;">
                                                    <i class="bi bi-person-badge me-1"></i> {{ $p->petugas->name }}
                                                </span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted small">Belum ada scan hari
                                            ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card-custom p-4 h-100">
                    <h6 class="fw-bold text-dark mb-3">
                        <i class="bi bi-clock-history text-primary me-1"></i> Jadwal Sesi Absensi
                    </h6>
                    <div class="d-flex flex-column gap-2">
                        @forelse ($sesis as $sesi)
                            <div
                                class="d-flex align-items-center justify-content-between gap-3 p-3 rounded-3 border {{ $sesiAktif && $sesiAktif->id === $sesi->id ? 'border-success bg-success-subtle' : 'bg-light' }}">
                                <div>
                                    <div class="fw-semibold text-dark">{{ $sesi->nama_sesi }}</div>
                                    <small class="text-muted">
                                        Batas tepat waktu {{ substr($sesi->jam_terlambat, 0, 5) }}
                                    </small>
                                </div>
                                <div class="text-end text-nowrap">
                                    <div class="fw-bold text-dark">
                                        {{ substr($sesi->jam_mulai, 0, 5) }} -
                                        {{ substr($sesi->jam_selesai, 0, 5) }}
                                    </div>
                                    @if ($sesiAktif && $sesiAktif->id === $sesi->id)
                                        <span class="badge bg-success rounded-pill">Sedang berlangsung</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">Belum ada jadwal sesi absensi aktif.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Tambah Izin / Sakit -->
    <div class="modal fade" id="modalTambahIzinSakit" tabindex="-1" aria-labelledby="modalTambahIzinSakitLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4 text-start">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark" id="modalTambahIzinSakitLabel">
                        <i class="bi bi-plus-circle-fill text-success me-2"></i> Catat Presensi Siswa Izin / Sakit
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('guru.izin-sakit.store') }}" method="POST" id="formTambahIzinSakit">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <!-- Filter Nama Siswa (Pengganti Dropdown Panjang) -->
                            <div class="col-12">
                                <label for="liveSearchSiswaInput" class="form-label small fw-bold mb-1">
                                    Cari / Filter Nama Siswa <span class="text-danger">*</span>
                                </label>

                                <!-- Nilai NIS siswa yang dipilih -->
                                <input type="hidden" name="nis" id="selectedSiswaNis" required>

                                <!-- Kartu Siswa Terpilih (Muncul setelah nama siswa diklik) -->
                                <div id="selectedSiswaPreview"
                                    class="p-3 rounded-3 border border-2 border-success bg-success-subtle d-none align-items-center justify-content-between gap-2 mb-2 shadow-sm">
                                    <div class="d-flex align-items-center gap-2.5 overflow-hidden">
                                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                            style="width: 38px; height: 38px;">
                                            <i class="bi bi-check-lg fs-5"></i>
                                        </div>
                                        <div class="overflow-hidden">
                                            <div id="selectedSiswaNama" class="fw-bold text-dark text-truncate"
                                                style="font-size: 0.95rem;">-</div>
                                            <small id="selectedSiswaMeta"
                                                class="text-success-emphasis fw-semibold d-block"
                                                style="font-size: 0.78rem;">-</small>
                                        </div>
                                    </div>
                                    <button type="button" id="resetSelectedSiswaBtn"
                                        class="btn btn-sm btn-light border border-success-subtle rounded-pill px-3 py-1 fw-semibold text-success flex-shrink-0">
                                        <i class="bi bi-arrow-repeat me-1"></i> Ganti Siswa
                                    </button>
                                </div>

                                <!-- Area Input Filter Nama & Filter Kelas -->
                                <div id="searchSiswaPickerArea">
                                    <div class="row g-2 mb-2">
                                        <div class="col-sm-4">
                                            <select id="filterKelasModal" class="form-select bg-light"
                                                title="Filter berdasarkan kelas">
                                                <option value="">Semua Kelas</option>
                                                @foreach ($daftarKelas as $k)
                                                    <option value="{{ $k }}">Kelas {{ $k }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-sm-8">
                                            <div class="input-group">
                                                <span class="input-group-text bg-white text-success border-end-0">
                                                    <i class="bi bi-search"></i>
                                                </span>
                                                <input type="text" id="liveSearchSiswaInput"
                                                    class="form-control border-start-0"
                                                    placeholder="Ketik nama siswa atau NISN di sini..."
                                                    autocomplete="off">
                                                <button type="button" id="clearLiveSearchSiswaBtn"
                                                    class="btn btn-light border d-none" title="Bersihkan pencarian">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Daftar Hasil Filter Nama Siswa (Langsung Klik Tanpa Scroll Panjang) -->
                                    <div id="liveSearchSiswaResults"
                                        class="border rounded-3 bg-white shadow-sm overflow-hidden"
                                        style="max-height: 220px; overflow-y: auto;"></div>

                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <small id="liveSearchHelperText" class="text-muted" style="font-size: 0.74rem;">
                                            <i class="bi bi-hand-index-thumb me-1"></i> Ketik nama siswa lalu klik namanya
                                            pada daftar di atas
                                        </small>
                                        <small id="liveSearchSiswaCount" class="text-success fw-semibold"
                                            style="font-size: 0.74rem;">
                                            {{ $semuaSiswa->count() }} siswa terdaftar
                                        </small>
                                    </div>
                                </div>

                                <!-- Data Siswa DOM untuk Filter Instan (Aman dari Auto-Formatter JS) -->
                                <div id="allSiswaDataList" class="d-none" aria-hidden="true">
                                    @foreach ($semuaSiswa as $s)
                                        <span class="siswa-data-item" data-nis="{{ $s->nis }}"
                                            data-nisn="{{ $s->nisn ?? '' }}" data-nama="{{ $s->nama }}"
                                            data-kelas="{{ $s->kelas }}"></span>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Pilihan Status (Izin / Sakit) -->
                            <div class="col-12">
                                <label class="form-label small fw-bold">Status Kehadiran <span
                                        class="text-danger">*</span></label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="status" id="statusIzinRadio"
                                            value="Izin" checked autocomplete="off">
                                        <label
                                            class="btn btn-outline-info w-100 py-2.5 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2"
                                            for="statusIzinRadio">
                                            <i class="bi bi-envelope-paper-fill"></i>
                                            <span>Izin</span>
                                        </label>
                                    </div>
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="status" id="statusSakitRadio"
                                            value="Sakit" autocomplete="off">
                                        <label
                                            class="btn btn-outline-warning w-100 py-2.5 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2 text-dark"
                                            for="statusSakitRadio">
                                            <i class="bi bi-hospital-fill text-warning"></i>
                                            <span>Sakit</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Tanggal Presensi -->
                            <div class="col-sm-6">
                                <label class="form-label small fw-bold">Tanggal Presensi <span
                                        class="text-danger">*</span></label>
                                <input type="date" name="tanggal" class="form-control" value="{{ $tanggal }}"
                                    required>
                            </div>

                            <!-- Sesi Absensi -->
                            <div class="col-sm-6">
                                <label class="form-label small fw-bold">Sesi Absensi</label>
                                <select name="sesi_id" class="form-select">
                                    @foreach ($sesis as $s)
                                        <option value="{{ $s->id }}"
                                            {{ $sesiAktif && $sesiAktif->id === $s->id ? 'selected' : '' }}>
                                            {{ $s->nama_sesi }} ({{ substr($s->jam_mulai, 0, 5) }} -
                                            {{ substr($s->jam_selesai, 0, 5) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Keterangan Alasan -->
                            <div class="col-12">
                                <label class="form-label small fw-bold">Keterangan / Alasan Tambahan</label>
                                <textarea name="keterangan" class="form-control" rows="3"
                                    placeholder="Contoh: Demam tinggi, surat dokter terlampir / Izin ada acara keluarga mendesak"></textarea>
                                <small class="text-muted" style="font-size: 0.72rem;">Catatan ini akan muncul pada laporan
                                    rekapitulasi presensi</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light py-3">
                        <button type="button" class="btn btn-light border rounded-pill px-4"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm">
                            <i class="bi bi-save me-1"></i> Simpan Catatan Presensi
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
            // =========================================================================
            // 1. FILTER NAMA SISWA (LIVE SEARCH) SAAT MENCATAT IZIN / SAKIT
            // =========================================================================
            const modalEl = document.getElementById('modalTambahIzinSakit');
            const formEl = document.getElementById('formTambahIzinSakit');
            const hiddenNisInput = document.getElementById('selectedSiswaNis');
            const pickerArea = document.getElementById('searchSiswaPickerArea');
            const searchInput = document.getElementById('liveSearchSiswaInput');
            const clearBtn = document.getElementById('clearLiveSearchSiswaBtn');
            const filterKelas = document.getElementById('filterKelasModal');
            const resultsContainer = document.getElementById('liveSearchSiswaResults');
            const countLabel = document.getElementById('liveSearchSiswaCount');
            const selectedPreview = document.getElementById('selectedSiswaPreview');
            const selectedNama = document.getElementById('selectedSiswaNama');
            const selectedMeta = document.getElementById('selectedSiswaMeta');
            const resetSelectedBtn = document.getElementById('resetSelectedSiswaBtn');
            const siswaDataListEl = document.getElementById('allSiswaDataList');

            if (searchInput && resultsContainer && siswaDataListEl) {
                const allSiswa = Array.from(siswaDataListEl.querySelectorAll('.siswa-data-item')).map(function(el) {
                    return {
                        nis: el.getAttribute('data-nis') || '',
                        nisn: el.getAttribute('data-nisn') || '',
                        nama: el.getAttribute('data-nama') || '',
                        kelas: el.getAttribute('data-kelas') || ''
                    };
                });

                let currentMatched = allSiswa.slice();

                function escapeHtml(str) {
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function pilihSiswa(nis) {
                    const found = allSiswa.find(function(s) {
                        return s.nis === nis;
                    });
                    if (!found) return;

                    hiddenNisInput.value = found.nis;
                    selectedNama.textContent = found.nama;
                    selectedMeta.textContent = 'Kelas ' + found.kelas + ' • NISN: ' + (found.nisn || found.nis);

                    selectedPreview.classList.remove('d-none');
                    selectedPreview.classList.add('d-flex');

                    if (pickerArea) {
                        pickerArea.classList.add('d-none');
                    }
                }

                function resetPilihanSiswa() {
                    hiddenNisInput.value = '';
                    selectedPreview.classList.add('d-none');
                    selectedPreview.classList.remove('d-flex');

                    if (pickerArea) {
                        pickerArea.classList.remove('d-none');
                    }
                    filterAndRenderSiswa();
                    searchInput.focus();
                }

                function filterAndRenderSiswa() {
                    const query = searchInput.value.trim().toLowerCase();
                    const kelas = filterKelas ? filterKelas.value : '';

                    if (clearBtn) {
                        clearBtn.classList.toggle('d-none', query.length === 0);
                    }

                    currentMatched = allSiswa.filter(function(s) {
                        const matchKelas = !kelas || s.kelas === kelas;
                        if (!matchKelas) return false;
                        if (!query) return true;
                        return (
                            s.nama.toLowerCase().includes(query) ||
                            s.nisn.toLowerCase().includes(query) ||
                            s.nis.toLowerCase().includes(query) ||
                            s.kelas.toLowerCase().includes(query)
                        );
                    });

                    if (countLabel) {
                        countLabel.textContent = currentMatched.length + ' siswa cocok';
                    }

                    if (currentMatched.length === 0) {
                        resultsContainer.innerHTML =
                            '<div class="p-3 text-center text-muted small">' +
                            '<i class="bi bi-search d-block mb-1 fs-5"></i>' +
                            'Tidak ditemukan siswa dengan nama/NISN <strong>"' + escapeHtml(searchInput.value
                            .trim()) + '"</strong>' +
                            '</div>';
                        return;
                    }

                    // Tampilkan maksimal 8 hasil teratas agar tidak perlu scroll panjang
                    const maxDisplay = 8;
                    const itemsHtml = currentMatched.slice(0, maxDisplay).map(function(s, idx) {
                        const initial = escapeHtml((s.nama || 'S').charAt(0).toUpperCase());
                        const firstBadge = (idx === 0 && query.length > 0) ?
                            '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5 me-1" style="font-size: 0.68rem;">Enter ↵</span>' :
                            '';
                        return (
                            '<button type="button" class="w-100 text-start btn btn-link text-decoration-none p-2 px-3 border-bottom d-flex align-items-center justify-content-between gap-2 item-hasil-siswa" data-nis="' +
                            escapeHtml(s.nis) + '">' +
                            '<div class="d-flex align-items-center gap-2.5 overflow-hidden">' +
                            '<div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.8rem;">' +
                            initial +
                            '</div>' +
                            '<div class="overflow-hidden">' +
                            '<div class="fw-bold text-dark text-truncate" style="font-size: 0.88rem;">' +
                            escapeHtml(s.nama) + '</div>' +
                            '<small class="text-muted d-block" style="font-size: 0.74rem;">NISN: ' +
                            escapeHtml(s.nisn || s.nis) + '</small>' +
                            '</div>' +
                            '</div>' +
                            '<div class="d-flex align-items-center flex-shrink-0">' +
                            firstBadge +
                            '<span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.74rem;">Kelas ' +
                            escapeHtml(s.kelas) + '</span>' +
                            '</div>' +
                            '</button>'
                        );
                    }).join('');

                    const moreInfo = currentMatched.length > maxDisplay ?
                        '<div class="p-2 text-center bg-light text-muted border-top" style="font-size: 0.74rem;">Menampilkan ' +
                        maxDisplay + ' dari ' + currentMatched.length +
                        ' siswa. Ketik nama siswa untuk memfilter lebih spesifik.</div>' :
                        '';

                    resultsContainer.innerHTML = itemsHtml + moreInfo;
                }

                // Render daftar awal (8 teratas)
                filterAndRenderSiswa();

                searchInput.addEventListener('input', filterAndRenderSiswa);

                // Tekan Enter pada kotak filter nama langsung memilih siswa teratas
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (currentMatched.length > 0) {
                            pilihSiswa(currentMatched[0].nis);
                        }
                    }
                });

                if (filterKelas) {
                    filterKelas.addEventListener('change', function() {
                        filterAndRenderSiswa();
                        searchInput.focus();
                    });
                }

                if (clearBtn) {
                    clearBtn.addEventListener('click', function() {
                        searchInput.value = '';
                        filterAndRenderSiswa();
                        searchInput.focus();
                    });
                }

                resultsContainer.addEventListener('click', function(e) {
                    const btn = e.target.closest('.item-hasil-siswa');
                    if (!btn) return;
                    pilihSiswa(btn.getAttribute('data-nis'));
                });

                if (resetSelectedBtn) {
                    resetSelectedBtn.addEventListener('click', resetPilihanSiswa);
                }

                if (formEl) {
                    formEl.addEventListener('submit', function(e) {
                        if (!hiddenNisInput.value) {
                            // Jika guru sudah mengetik nama dan ada tepat 1 siswa yang cocok, otomatis pilih
                            if (searchInput.value.trim().length > 0 && currentMatched.length === 1) {
                                pilihSiswa(currentMatched[0].nis);
                                return;
                            }
                            e.preventDefault();
                            searchInput.focus();
                            searchInput.classList.add('is-invalid');
                            setTimeout(function() {
                                searchInput.classList.remove('is-invalid');
                            }, 2000);
                        }
                    });
                }

                if (modalEl) {
                    modalEl.addEventListener('shown.bs.modal', function() {
                        if (!hiddenNisInput.value) {
                            searchInput.focus();
                        }
                    });
                }
            }

            // =========================================================================
            // 2. LIVE SEARCH PADA TABEL DAFTAR IZIN & SAKIT
            // =========================================================================
            const tabelSearchInput = document.getElementById('liveSearchTabelInput');
            const tabelIzinSakit = document.getElementById('tabelIzinSakit');

            if (tabelSearchInput && tabelIzinSakit) {
                let debounceTimer = null;
                const rows = tabelIzinSakit.querySelectorAll('tbody tr.baris-izin-sakit');

                tabelSearchInput.addEventListener('input', function() {
                    const keyword = tabelSearchInput.value.trim().toLowerCase();

                    if (rows.length > 0) {
                        let visibleCount = 0;
                        rows.forEach(function(row) {
                            const haystack = row.getAttribute('data-search') || '';
                            const isMatch = !keyword || haystack.includes(keyword);
                            row.style.display = isMatch ? '' : 'none';
                            if (isMatch) visibleCount++;
                        });

                        let emptyNotice = document.getElementById('liveSearchEmptyRow');
                        if (visibleCount === 0 && keyword.length > 0) {
                            if (!emptyNotice) {
                                emptyNotice = document.createElement('tr');
                                emptyNotice.id = 'liveSearchEmptyRow';
                                emptyNotice.innerHTML =
                                    '<td colspan="8" class="text-center py-4 text-muted small">' +
                                    '<i class="bi bi-search me-1"></i> Tidak ada baris yang cocok di halaman ini. Mencari di seluruh data...' +
                                    '</td>';
                                tabelIzinSakit.querySelector('tbody').appendChild(emptyNotice);
                            }
                        } else if (emptyNotice) {
                            emptyNotice.remove();
                        }
                    }

                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() {
                        tabelSearchInput.form.submit();
                    }, 600);
                });
            }

            // =========================================================================
            // 3. REAL-TIME BROADCAST & AUTO-UPDATE STATISTIK GURU DASHBOARD
            // =========================================================================
            function broadcastAbsensiUpdate() {
                try {
                    if ('BroadcastChannel' in window) {
                        const bc = new BroadcastChannel('e_absen_live_channel');
                        bc.postMessage({
                            type: 'absensi_updated',
                            timestamp: Date.now()
                        });
                        bc.close();
                    }
                    localStorage.setItem('e_absen_last_transaction', String(Date.now()));
                } catch (e) {}
            }

            const liveFeedBodyEl = document.getElementById('guruLiveFeedBody');
            if (liveFeedBodyEl && liveFeedBodyEl.getAttribute('data-flash-success') === '1') {
                broadcastAbsensiUpdate();
            }

            function escapeHtmlStr(str) {
                return String(str ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function updateGuruStatText(elementId, newValue, cardId) {
                const el = document.getElementById(elementId);
                if (!el) return;
                const strVal = String(newValue);
                if (el.textContent.trim() !== strVal) {
                    el.textContent = strVal;
                    const card = cardId ? document.getElementById(cardId) : null;
                    if (card) {
                        card.style.transition = 'transform 0.25s ease, box-shadow 0.25s ease';
                        card.style.transform = 'scale(1.03)';
                        setTimeout(function() {
                            card.style.transform = '';
                        }, 500);
                    }
                }
            }

            function renderGuruLiveStatusBadge(status) {
                if (status === 'Tepat Waktu') {
                    return '<span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1">Tepat Waktu</span>';
                }
                if (status === 'Terlambat') {
                    return '<span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2.5 py-1">Terlambat</span>';
                }
                if (status === 'Izin' || status === 'Ijin') {
                    return '<span class="badge bg-info-subtle text-info rounded-pill px-2.5 py-1">Izin</span>';
                }
                if (status === 'Sakit') {
                    return '<span class="badge bg-warning-subtle text-warning rounded-pill px-2.5 py-1">Sakit</span>';
                }
                return '<span class="badge bg-danger-subtle text-danger rounded-pill px-2.5 py-1">' + escapeHtmlStr(
                    status) + '</span>';
            }

            let isFetchingGuruLive = false;

            function fetchGuruLiveStats() {
                if (isFetchingGuruLive || document.hidden) return;
                isFetchingGuruLive = true;

                const tanggalParam = document.querySelector('input[name="tanggal"]')?.value || '';
                const liveUrl = window.location.pathname + '?live=1&tanggal=' + encodeURIComponent(tanggalParam) +
                    '&_t=' + Date.now();

                fetch(liveUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        cache: 'no-store'
                    })
                    .then(function(res) {
                        if (!res.ok) throw new Error('Network error');
                        return res.json();
                    })
                    .then(function(data) {
                        updateGuruStatText('guruStatTotalSiswa', data.totalSiswa, 'guruCardTotalSiswa');
                        updateGuruStatText('guruStatHadir', data.jumlahHadir, 'guruCardHadir');
                        updateGuruStatText('guruStatDetailHadir', data.jumlahTepatWaktu + ' tepat, ' + data
                            .jumlahTerlambat + ' terlambat');
                        updateGuruStatText('guruStatIzin', data.jumlahIzin, 'guruCardIzin');
                        updateGuruStatText('guruStatSakit', data.jumlahSakit, 'guruCardSakit');

                        const tbody = document.getElementById('guruLiveFeedBody');
                        if (tbody && Array.isArray(data.presensiTerbaru)) {
                            if (data.presensiTerbaru.length === 0) {
                                tbody.innerHTML =
                                    '<tr><td colspan="5" class="text-center py-4 text-muted small">Belum ada scan hari ini.</td></tr>';
                            } else {
                                tbody.innerHTML = data.presensiTerbaru.map(function(p) {
                                    const petugasHtml = p.petugas ?
                                        '<span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1" style="font-size: 0.78rem;"><i class="bi bi-person-badge me-1"></i> ' +
                                        escapeHtmlStr(p.petugas) + '</span>' :
                                        '<span class="text-muted small">-</span>';
                                    const fotoUrl = p.foto_url ||
                                        'https://ui-avatars.com/api/?name=Siswa';
                                    return (
                                        '<tr>' +
                                        '<td><div class="d-flex align-items-center gap-2"><img src="' +
                                        escapeHtmlStr(fotoUrl) +
                                        '" alt="" class="rounded-circle border flex-shrink-0" style="width: 34px; height: 34px; object-fit: cover;"><div><strong class="text-dark d-block">' +
                                        escapeHtmlStr(p.nama) +
                                        '</strong><small class="text-muted">' + escapeHtmlStr(p
                                        .nis) + '</small></div></div></td>' +
                                        '<td><span class="badge bg-light text-dark border">' +
                                        escapeHtmlStr(p.kelas) + '</span></td>' +
                                        '<td><small class="text-muted">' + escapeHtmlStr(p
                                        .jam_scan) + ' WIB</small></td>' +
                                        '<td>' + renderGuruLiveStatusBadge(p.status) + '</td>' +
                                        '<td>' + petugasHtml + '</td>' +
                                        '</tr>'
                                    );
                                }).join('');
                            }
                        }
                    })
                    .catch(function() {})
                    .finally(function() {
                        isFetchingGuruLive = false;
                    });
            }

            setInterval(fetchGuruLiveStats, 3000);
            if ('BroadcastChannel' in window) {
                const guruChannel = new BroadcastChannel('e_absen_live_channel');
                guruChannel.onmessage = function() {
                    fetchGuruLiveStats();
                };
            }
            window.addEventListener('storage', function(e) {
                if (e.key === 'e_absen_last_transaction') {
                    fetchGuruLiveStats();
                }
            });
        });
    </script>
@endpush
