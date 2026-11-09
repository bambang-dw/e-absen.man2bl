@extends('layouts.admin', [
    'title' => 'Pengaturan Akun',
    'pageTitle' => 'Pengaturan Akun Pengguna',
    'pageSubtitle' => 'Kelola akun pengguna pegawai, akun login siswa berbasis NISN, dan reset kata sandi'
])

@section('content')
<div class="container-fluid px-0">

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-5 text-danger flex-shrink-0"></i>
                <div>
                    <strong>Terdapat beberapa kesalahan:</strong>
                    <ul class="mb-0 ps-3 small mt-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Tab Navigation: Akun Pegawai vs Akun Siswa -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="nav nav-pills custom-pills p-1 bg-white rounded-pill border shadow-sm">
            <a class="nav-link rounded-pill px-4 py-2 fw-semibold {{ $activeTab === 'pegawai' ? 'active bg-primary text-white shadow-sm' : 'text-muted' }}" 
               href="{{ route('pengaturan-akun.index', ['tab' => 'pegawai']) }}">
                <i class="bi bi-person-badge-fill me-1"></i> Akun Pegawai & Guru
                <span class="badge {{ $activeTab === 'pegawai' ? 'bg-white text-primary' : 'bg-light text-dark border' }} ms-1">{{ $totalUsers }}</span>
            </a>
            <a class="nav-link rounded-pill px-4 py-2 fw-semibold {{ $activeTab === 'siswa' ? 'active text-white shadow-sm' : 'text-muted' }}" 
               style="{{ $activeTab === 'siswa' ? 'background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white !important;' : '' }}"
               href="{{ route('pengaturan-akun.index', ['tab' => 'siswa']) }}">
                <i class="bi bi-mortarboard-fill me-1"></i> Akun Siswa (Tabel Siswa)
                <span class="badge {{ $activeTab === 'siswa' ? 'bg-white text-success' : 'bg-light text-dark border' }} ms-1">{{ $totalSiswa }}</span>
            </a>
        </div>

        <div class="text-muted small">
            @if($activeTab === 'siswa')
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2">
                    <i class="bi bi-info-circle-fill me-1"></i> Username & Password Awal Siswa: <strong>Nomor NISN</strong>
                </span>
            @else
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2">
                    <i class="bi bi-shield-lock-fill me-1"></i> Otoritas Akses Sistem & Operator Scanner
                </span>
            @endif
        </div>
    </div>

    @if($activeTab === 'pegawai')
    <!-- ========================================== -->
    <!-- TAB 1: AKUN PEGAWAI & OPERATOR SISTEM      -->
    <!-- ========================================== -->

    <!-- Top Statistics Badges -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card h-100 border-start border-4 border-primary">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold small">Total Pengguna</span>
                    <div class="stat-icon bg-primary-subtle text-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-dark">{{ $totalUsers }}</h3>
                <small class="text-muted">Akun terdaftar dalam sistem</small>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card h-100 border-start border-4 border-indigo-500" style="border-left-color: #6366f1 !important;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold small">Administrator</span>
                    <div class="stat-icon text-white" style="background-color: #4f46e5;">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0" style="color: #4f46e5;">{{ $totalAdmin }}</h3>
                <small class="text-muted">Akses penuh sistem</small>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card h-100 border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold small">Guru & Pendidik</span>
                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-success">{{ $totalGuru }}</h3>
                <small class="text-muted">Pendidik & operator scanner</small>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card h-100 border-start border-4 border-info">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold small">Wali Kelas</span>
                    <div class="stat-icon bg-info-subtle text-info">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-info">{{ $totalWaliKelas }}</h3>
                <small class="text-muted">Pembina rombongan belajar</small>
            </div>
        </div>
    </div>

    <!-- Search, Filter & Action Bar Pegawai -->
    <div class="card-custom p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-lg-5">
                <form action="{{ route('pengaturan-akun.index') }}" method="GET" class="d-flex flex-wrap gap-2">
                    <input type="hidden" name="tab" value="pegawai">
                    <div style="min-width: 170px;">
                        <select name="role" class="form-select bg-light" onchange="this.form.submit()">
                            <option value="">-- Semua Role --</option>
                            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                            <option value="guru" {{ request('role') === 'guru' ? 'selected' : '' }}>Guru</option>
                            <option value="wali_kelas" {{ request('role') === 'wali_kelas' ? 'selected' : '' }}>Wali Kelas</option>
                            <option value="kepsek" {{ request('role') === 'kepsek' ? 'selected' : '' }}>Kepala Sekolah</option>
                        </select>
                    </div>
                    <div class="input-group" style="max-width: 230px;">
                        <input type="text" name="search" class="form-control bg-light" placeholder="Cari username / HP..." value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                    @if(request('search') || request('role'))
                        <a href="{{ route('pengaturan-akun.index', ['tab' => 'pegawai']) }}" class="btn btn-light border" title="Reset Filter"><i class="bi bi-x-circle"></i></a>
                    @endif
                </form>
            </div>

            <div class="col-lg-7 text-lg-end">
                <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                    <!-- Template Excel Pegawai -->
                    <a href="{{ route('pengaturan-akun.template') }}" class="btn btn-outline-success btn-sm rounded-pill px-3 py-2 fw-semibold shadow-sm" title="Unduh Format Excel untuk Pegawai & Guru">
                        <i class="bi bi-file-earmark-arrow-down-fill me-1"></i> Template Excel
                    </a>

                    <!-- Import Excel Pegawai -->
                    <button type="button" class="btn btn-success btn-sm rounded-pill px-3 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalImportPegawai" title="Import Banyak Akun Guru Sekaligus">
                        <i class="bi bi-file-earmark-excel-fill me-1"></i> Import Excel
                    </button>

                    <!-- Export PDF -->
                    <a href="{{ route('pengaturan-akun.export.pdf', ['tab' => 'pegawai', 'role' => request('role')]) }}" class="btn btn-danger btn-sm rounded-pill px-3 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Ekspor PDF
                    </a>

                    <!-- Export Excel -->
                    <a href="{{ route('pengaturan-akun.export.excel', ['tab' => 'pegawai', 'role' => request('role')]) }}" class="btn btn-light border btn-sm rounded-pill px-3 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-file-earmark-excel me-1 text-success"></i> Ekspor Excel
                    </a>

                    <!-- Tambah User Baru -->
                    <button type="button" class="btn btn-primary-gradient btn-sm rounded-pill px-4 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                        <i class="bi bi-person-plus-fill me-1"></i> Tambah Akun
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card-custom p-0 overflow-hidden mb-4">
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-person-lines-fill me-2 text-primary"></i> Daftar Akun Pengguna Sistem
            </h6>
            <span class="badge bg-light text-dark border">{{ $users->total() }} Akun Ditemukan</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">
                        <th class="ps-4" style="width: 50px;">No</th>
                        <th style="min-width: 140px;">Username / NIP</th>
                        <th style="min-width: 200px;">Nama Lengkap</th>
                        <th style="min-width: 160px;">Role Akses</th>
                        <th style="min-width: 140px;">Nomor HP</th>
                        <th style="min-width: 130px;">Dibuat Pada</th>
                        <th class="text-end pe-4" style="width: 110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $idx => $u)
                        <tr>
                            <!-- No -->
                            <td class="ps-4 text-muted small">
                                {{ $users->firstItem() + $idx }}
                            </td>

                            <!-- Username / NIP -->
                            <td>
                                <span class="badge bg-light text-dark border font-monospace px-2.5 py-1.5" style="font-size: 0.82rem;">
                                    {{ $u->username ?: '-' }}
                                </span>
                            </td>

                            <!-- Nama Lengkap -->
                            <td>
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm flex-shrink-0" style="width: 36px; height: 36px; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); font-size: 0.85rem;">
                                        {{ strtoupper(substr($u->name ?: ($u->username ?: 'U'), 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 0.92rem;">
                                            {{ $u->name ?: '-' }}
                                        </div>
                                        @if($u->email)
                                            <small class="text-muted" style="font-size: 0.72rem;">{{ $u->email }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Role -->
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($u->roles as $roleItem)
                                        @if($roleItem === 'admin')
                                            <span class="badge rounded-pill px-2.5 py-1 fw-semibold text-white shadow-sm" style="background-color: #4f46e5; font-size: 0.75rem;">
                                                <i class="bi bi-shield-lock me-1"></i> Administrator
                                            </span>
                                        @elseif($roleItem === 'guru')
                                            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.75rem;">
                                                <i class="bi bi-mortarboard me-1"></i> Guru
                                            </span>
                                        @elseif($roleItem === 'wali_kelas')
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.75rem;">
                                                <i class="bi bi-person-workspace me-1"></i> Wali Kelas
                                            </span>
                                        @elseif($roleItem === 'kepsek')
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.75rem;">
                                                <i class="bi bi-award me-1"></i> Kepsek
                                            </span>
                                        @else
                                            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.75rem;">
                                                {{ ucfirst($roleItem) }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </td>

                            <!-- Nomor HP -->
                            <td>
                                @if($u->no_hp)
                                    @php
                                        $cleanPhone = preg_replace('/[^0-9]/', '', $u->no_hp);
                                        if (str_starts_with($cleanPhone, '0')) {
                                            $cleanPhone = '62' . substr($cleanPhone, 1);
                                        }
                                    @endphp
                                    <a href="https://wa.me/{{ $cleanPhone }}" target="_blank" class="text-decoration-none text-success small fw-semibold" title="Hubungi via WhatsApp">
                                        <i class="bi bi-whatsapp me-1"></i> {{ $u->no_hp }}
                                    </a>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>

                            <!-- Tanggal Dibuat -->
                            <td>
                                <span class="small text-muted">{{ $u->created_at ? $u->created_at->format('d M Y, H:i') : '-' }}</span>
                            </td>

                            <!-- Aksi -->
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" 
                                            class="btn btn-light border text-primary btn-edit-user" 
                                            data-action="{{ route('pengaturan-akun.update', $u->id) }}"
                                            data-username="{{ $u->username }}"
                                            data-name="{{ $u->name }}"
                                            data-nohp="{{ $u->no_hp }}"
                                            data-roles="{{ implode(',', $u->roles) }}"
                                            title="Edit Akun">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-light border text-danger btn-hapus-user" 
                                            data-action="{{ route('pengaturan-akun.destroy', $u->id) }}"
                                            data-label="{{ $u->name ?: ($u->username ?: 'Akun') }}"
                                            title="Hapus Akun">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                Tidak ada akun pengguna yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="p-3 border-top d-flex justify-content-between align-items-center bg-white">
                <small class="text-muted">
                    Menampilkan {{ $users->firstItem() }} - {{ $users->lastItem() }} dari {{ $users->total() }} akun
                </small>
                <div>
                    {{ $users->links() }}
                </div>
            </div>
        @endif
    </div>

    @else
    <!-- ========================================== -->
    <!-- TAB 2: AKUN SISWA (BERDASARKAN TABEL SISWA)-->
    <!-- ========================================== -->

    <!-- Top Statistics Badges Siswa -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card h-100 border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold small">Total Akun Siswa</span>
                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-success">{{ $totalSiswa }}</h3>
                <small class="text-muted">Terdaftar di tabel siswa</small>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card h-100 border-start border-4 border-secondary">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold small">Password Default (NISN)</span>
                    <div class="stat-icon bg-secondary-subtle text-secondary">
                        <i class="bi bi-key-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-secondary">{{ $totalSiswaDefault }}</h3>
                <small class="text-muted">Password = nomor NISN</small>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card h-100 border-start border-4 border-primary">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold small">Password Kustom</span>
                    <div class="stat-icon bg-primary-subtle text-primary">
                        <i class="bi bi-shield-check"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-primary">{{ $totalSiswaKustom }}</h3>
                <small class="text-muted">Sudah diganti mandiri oleh siswa</small>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card h-100 border-start border-4 border-info">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold small">Total Kelas</span>
                    <div class="stat-icon bg-info-subtle text-info">
                        <i class="bi bi-door-open-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-info">{{ count($daftarKelas) }}</h3>
                <small class="text-muted">Rombongan belajar terdaftar</small>
            </div>
        </div>
    </div>

    <!-- Search, Filter & Action Bar Siswa -->
    <div class="card-custom p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-lg-7">
                <form action="{{ route('pengaturan-akun.index') }}" method="GET" class="d-flex flex-wrap gap-2">
                    <input type="hidden" name="tab" value="siswa">
                    <div style="min-width: 150px;">
                        <select name="kelas_siswa" class="form-select bg-light" onchange="this.form.submit()">
                            <option value="">-- Semua Kelas --</option>
                            @foreach($daftarKelas as $k)
                                <option value="{{ $k }}" {{ request('kelas_siswa') === $k ? 'selected' : '' }}>Kelas {{ $k }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="min-width: 170px;">
                        <select name="status_siswa" class="form-select bg-light" onchange="this.form.submit()">
                            <option value="">-- Semua Status --</option>
                            <option value="default" {{ request('status_siswa') === 'default' ? 'selected' : '' }}>Password Default (NISN)</option>
                            <option value="kustom" {{ request('status_siswa') === 'kustom' ? 'selected' : '' }}>Password Kustom (Diubah)</option>
                        </select>
                    </div>
                    <div class="input-group" style="max-width: 250px;">
                        <input type="text" name="search_siswa" class="form-control bg-light" placeholder="Cari NISN / Nama..." value="{{ request('search_siswa') }}">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                    @if(request('search_siswa') || request('kelas_siswa') || request('status_siswa'))
                        <a href="{{ route('pengaturan-akun.index', ['tab' => 'siswa']) }}" class="btn btn-light border" title="Reset Filter"><i class="bi bi-x-circle"></i></a>
                    @endif
                </form>
            </div>

            <div class="col-lg-5 text-lg-end">
                <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                    <!-- Export PDF Siswa -->
                    <a href="{{ route('pengaturan-akun.export.pdf', ['tab' => 'siswa', 'kelas_siswa' => request('kelas_siswa')]) }}" class="btn btn-danger btn-sm rounded-pill px-3 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Ekspor PDF
                    </a>

                    <!-- Export Excel Siswa -->
                    <a href="{{ route('pengaturan-akun.export.excel', ['tab' => 'siswa', 'kelas_siswa' => request('kelas_siswa')]) }}" class="btn btn-success btn-sm rounded-pill px-3 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-file-earmark-excel-fill me-1"></i> Ekspor Excel
                    </a>

                    <!-- Reset Massal per Kelas -->
                    <button type="button" class="btn btn-warning btn-sm rounded-pill px-3 py-2 fw-semibold shadow-sm text-dark" data-bs-toggle="modal" data-bs-target="#modalResetKelas">
                        <i class="bi bi-arrow-repeat me-1"></i> Reset per Kelas
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Siswa Table Card -->
    <div class="card-custom p-0 overflow-hidden mb-4">
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-mortarboard me-2 text-success"></i> Daftar Akun Siswa (Login via NISN)
            </h6>
            <span class="badge bg-light text-dark border">{{ $siswas->total() }} Siswa Ditemukan</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">
                        <th class="ps-4" style="width: 50px;">No</th>
                        <th style="width: 140px;">NISN (Username)</th>
                        <th style="width: 110px;">NIS</th>
                        <th>Nama Siswa</th>
                        <th style="width: 100px;">Kelas</th>
                        <th style="width: 170px;">Status Password</th>
                        <th class="text-end pe-4" style="width: 160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($siswas as $idx => $s)
                        @php
                            $isKustom = $s->user && $s->user->password_changed_at;
                        @endphp
                        <tr>
                            <!-- No -->
                            <td class="ps-4 text-muted small">
                                {{ $siswas->firstItem() + $idx }}
                            </td>

                            <!-- NISN / Username -->
                            <td>
                                <span class="fw-mono fw-bold text-dark px-2 py-1 rounded bg-light border" style="font-size: 0.85rem;">
                                    {{ $s->nisn ?: '-' }}
                                </span>
                            </td>

                            <!-- NIS -->
                            <td>
                                <span class="text-muted small font-monospace">{{ $s->nis ?: '-' }}</span>
                            </td>

                            <!-- Nama Siswa -->
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $s->foto_url }}" alt="Avatar" class="rounded-circle border" style="width: 32px; height: 32px; object-fit: cover;">
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 0.92rem;">
                                            {{ $s->nama }}
                                        </div>
                                        @if($s->no_hp_ortu)
                                            <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $s->no_hp_ortu }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Kelas -->
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1">{{ $s->kelas }}</span>
                            </td>

                            <!-- Status Password -->
                            <td>
                                @if($isKustom)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                                        <i class="bi bi-shield-check me-1"></i> Password Kustom
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-1">
                                        <i class="bi bi-key me-1"></i> Default (NISN)
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi Reset Password -->
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-3 py-1 fw-semibold text-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#modalResetSiswa{{ $s->id }}" title="Reset password siswa ke NISN">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset ke NISN
                                </button>
                            </td>
                        </tr>

                        <!-- Modal Konfirmasi Reset Password Siswa -->
                        <div class="modal fade" id="modalResetSiswa{{ $s->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow rounded-4 text-start">
                                    <div class="modal-header border-bottom py-3">
                                        <h5 class="modal-title fw-bold text-dark">
                                            <i class="bi bi-arrow-counterclockwise text-warning me-2"></i> Reset Password Siswa
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form action="{{ route('pengaturan-akun.siswa.reset-password', $s->id) }}" method="POST">
                                        @csrf
                                        <div class="modal-body p-4">
                                            <p class="mb-3">
                                                Apakah Anda yakin ingin mereset password akun siswa ini kembali ke nomor NISN?
                                            </p>
                                            <div class="bg-light p-3 rounded-3 mb-3 border">
                                                <div class="row g-2 small">
                                                    <div class="col-4 text-muted">Nama Siswa:</div>
                                                    <div class="col-8 fw-bold text-dark">{{ $s->nama }}</div>
                                                    <div class="col-4 text-muted">Kelas:</div>
                                                    <div class="col-8 fw-bold text-dark">{{ $s->kelas }}</div>
                                                    <div class="col-4 text-muted">Username:</div>
                                                    <div class="col-8 fw-mono fw-bold text-primary">{{ $s->nisn ?: $s->nis }}</div>
                                                    <div class="col-4 text-muted">Password Baru:</div>
                                                    <div class="col-8 fw-mono fw-bold text-success">{{ $s->nisn ?: $s->nis }} (Nomor NISN)</div>
                                                </div>
                                            </div>
                                            <div class="alert alert-warning border-0 rounded-3 p-2 small mb-0">
                                                <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Setelah direset, siswa dapat login dengan password awal yaitu nomor NISN miliknya.
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top bg-light py-3">
                                            <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-warning rounded-pill px-4 fw-semibold text-dark shadow-sm">
                                                <i class="bi bi-check-circle-fill me-1"></i> Ya, Reset ke NISN
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                Tidak ada data siswa yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Siswa -->
        @if($siswas->hasPages())
            <div class="p-3 border-top d-flex justify-content-between align-items-center bg-white">
                <small class="text-muted">
                    Menampilkan {{ $siswas->firstItem() }} - {{ $siswas->lastItem() }} dari {{ $siswas->total() }} siswa
                </small>
                <div>
                    {{ $siswas->links() }}
                </div>
            </div>
        @endif
    </div>
    @endif

</div>

<!-- Modal Reset Password per Kelas -->
<div class="modal fade" id="modalResetKelas" tabindex="-1" aria-labelledby="modalResetKelasLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 text-start">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark" id="modalResetKelasLabel">
                    <i class="bi bi-arrow-repeat text-warning me-2"></i> Reset Password Seluruh Siswa per Kelas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('pengaturan-akun.siswa.reset-kelas') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Pilih kelas yang ingin direset. Seluruh akun siswa di kelas terpilih akan direset passwordnya menjadi nomor NISN masing-masing.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Kelas Sasaran <span class="text-danger">*</span></label>
                        <select name="kelas" class="form-select" required>
                            <option value="">-- Pilih Rombel / Kelas --</option>
                            @foreach($daftarKelas as $k)
                                <option value="{{ $k }}">Kelas {{ $k }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-warning border-0 rounded-3 p-3 small mb-0">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Tindakan ini cocok digunakan saat pergantian semester atau awal tahun ajaran baru jika banyak siswa lupa password.
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-3">
                    <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-semibold text-dark shadow-sm">
                        <i class="bi bi-check-circle-fill me-1"></i> Reset Password Kelas Ini
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Akun Baru -->
<div class="modal fade" id="modalTambahUser" tabindex="-1" aria-labelledby="modalTambahUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 text-start">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark" id="modalTambahUserLabel">
                    <i class="bi bi-person-plus-fill text-primary me-2"></i> Tambah Akun Pengguna Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('pengaturan-akun.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Username / NIP <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" placeholder="Contoh: 198501012010011001 atau guru_matematika" value="{{ old('username') }}" required>
                            <small class="text-muted" style="font-size: 0.72rem;">Hanya huruf, angka, garis bawah tanpa spasi</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Drs. H. Ahmad Fauzi, M.Pd." value="{{ old('name') }}" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold mb-1">
                                Role Hak Akses <span class="text-danger">*</span>
                                <span class="text-muted fw-normal" style="font-size: 0.75rem;">(Bisa pilih lebih dari satu role)</span>
                            </label>
                            <div class="card p-2.5 bg-light border rounded-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="roles[]" value="admin" id="add_role_admin" {{ in_array('admin', old('roles', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold text-dark" for="add_role_admin">
                                                <i class="bi bi-shield-lock-fill text-primary me-1"></i> Admin
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="roles[]" value="guru" id="add_role_guru" {{ in_array('guru', old('roles', ['guru'])) ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold text-dark" for="add_role_guru">
                                                <i class="bi bi-mortarboard-fill text-info me-1"></i> Guru
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="roles[]" value="wali_kelas" id="add_role_walikelas" {{ in_array('wali_kelas', old('roles', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold text-dark" for="add_role_walikelas">
                                                <i class="bi bi-person-workspace text-warning me-1"></i> Wali Kelas
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="roles[]" value="kepsek" id="add_role_kepsek" {{ in_array('kepsek', old('roles', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold text-dark" for="add_role_kepsek">
                                                <i class="bi bi-award-fill text-secondary me-1"></i> Kepsek
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted" style="font-size: 0.72rem;">Misal: centang <strong>Guru</strong> & <strong>Wali Kelas</strong> sekaligus untuk wali kelas.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Nomor HP / WhatsApp</label>
                            <input type="text" name="no_hp" class="form-control" placeholder="Contoh: 08123456789" value="{{ old('no_hp') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-3">
                    <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Akun
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit User (Single Dynamic Modal) -->
<div class="modal fade" id="modalEditUser" tabindex="-1" aria-labelledby="modalEditUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 text-start">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark" id="modalEditUserLabel">
                    <i class="bi bi-pencil-square text-primary me-2"></i> Edit Akun Pengguna
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditUser" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Username / NIP <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="edit_username" class="form-control" required>
                            <small class="text-muted" style="font-size: 0.72rem;">Digunakan untuk login (huruf, angka, strip)</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_name" class="form-control" placeholder="Contoh: Drs. H. Ahmad Fauzi, M.Pd." required>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold mb-1">
                                Role Hak Akses <span class="text-danger">*</span>
                                <span class="text-muted fw-normal" style="font-size: 0.75rem;">(Bisa pilih lebih dari satu role)</span>
                            </label>
                            <div class="card p-2.5 bg-light border rounded-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="roles[]" value="admin" id="edit_role_admin">
                                            <label class="form-check-label small fw-semibold text-dark" for="edit_role_admin">
                                                <i class="bi bi-shield-lock-fill text-primary me-1"></i> Admin
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="roles[]" value="guru" id="edit_role_guru">
                                            <label class="form-check-label small fw-semibold text-dark" for="edit_role_guru">
                                                <i class="bi bi-mortarboard-fill text-info me-1"></i> Guru
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="roles[]" value="wali_kelas" id="edit_role_walikelas">
                                            <label class="form-check-label small fw-semibold text-dark" for="edit_role_walikelas">
                                                <i class="bi bi-person-workspace text-warning me-1"></i> Wali Kelas
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="roles[]" value="kepsek" id="edit_role_kepsek">
                                            <label class="form-check-label small fw-semibold text-dark" for="edit_role_kepsek">
                                                <i class="bi bi-award-fill text-secondary me-1"></i> Kepsek
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted" style="font-size: 0.72rem;">Centang <strong>Guru</strong> & <strong>Wali Kelas</strong> sekaligus untuk wali kelas.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Nomor HP / WhatsApp</label>
                            <input type="text" name="no_hp" id="edit_no_hp" class="form-control" placeholder="Contoh: 08123456789">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Password Baru (Opsional)</label>
                            <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengganti password">
                            <small class="text-muted" style="font-size: 0.72rem;">Minimal 6 karakter jika ingin mengganti</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-3">
                    <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus User (Single Dynamic Modal) -->
<div class="modal fade" id="modalHapusUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4 text-center p-3">
            <div class="modal-body">
                <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                    <i class="bi bi-trash3-fill fs-3"></i>
                </div>
                <h5 class="fw-bold mb-1">Hapus Akun?</h5>
                <p class="text-muted small mb-3">Yakin ingin menghapus akun <strong id="hapus_user_name"></strong>?</p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <form id="formHapusUser" method="POST" action="">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger rounded-pill px-3 fw-semibold">Ya, Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Import Pegawai & Guru dari Excel -->
<div class="modal fade" id="modalImportPegawai" tabindex="-1" aria-labelledby="modalImportPegawaiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 text-start">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark" id="modalImportPegawaiLabel">
                    <i class="bi bi-file-earmark-excel-fill text-success me-2"></i> Import Akun Pegawai & Guru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('pengaturan-akun.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 rounded-3 p-3 mb-3 small">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-info-circle-fill fs-5 text-info"></i>
                            <div>
                                <strong>Panduan Import Data Excel:</strong>
                                <ul class="ps-3 mb-1 mt-1">
                                    <li>Format file: <strong>.xlsx</strong> atau <strong>.xls</strong> (maks. 5 MB).</li>
                                    <li>1 akun dapat memiliki <strong>dua role sekaligus</strong> dengan tanda koma pada kolom ROLE (misal: <code>guru,wali_kelas</code> atau <code>Guru, Wali Kelas</code>).</li>
                                    <li>Jika password kosong di file Excel, otomatis dibuat <code>guru123</code>.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 text-center p-3 border rounded-3 bg-light">
                        <p class="small text-muted mb-2">Belum memiliki format template Excel? Unduh template resmi di bawah ini:</p>
                        <a href="{{ route('pengaturan-akun.template') }}" class="btn btn-outline-success btn-sm rounded-pill px-3 py-1.5 fw-semibold shadow-sm">
                            <i class="bi bi-file-earmark-arrow-down-fill me-1"></i> Unduh Template Excel (.xlsx)
                        </a>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold">Pilih File Excel (.xlsx / .xls) <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                        <small class="text-muted" style="font-size: 0.72rem;">File harus memiliki baris header sesuai template</small>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-3">
                    <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm">
                        <i class="bi bi-upload me-1"></i> Mulai Import Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Edit User
    document.querySelectorAll('.btn-edit-user').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const action = this.getAttribute('data-action');
            const username = this.getAttribute('data-username') || '';
            const name = this.getAttribute('data-name') || '';
            const noHp = this.getAttribute('data-nohp') || '';
            const rolesStr = this.getAttribute('data-roles') || '';
            const roles = rolesStr.split(',').map(r => r.trim().toLowerCase());

            document.getElementById('formEditUser').setAttribute('action', action);
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_no_hp').value = noHp;

            document.getElementById('edit_role_admin').checked = roles.includes('admin');
            document.getElementById('edit_role_guru').checked = roles.includes('guru');
            document.getElementById('edit_role_walikelas').checked = roles.includes('wali_kelas');
            document.getElementById('edit_role_kepsek').checked = roles.includes('kepsek');

            const modal = new bootstrap.Modal(document.getElementById('modalEditUser'));
            modal.show();
        });
    });

    // Hapus User
    document.querySelectorAll('.btn-hapus-user').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const action = this.getAttribute('data-action');
            const label = this.getAttribute('data-label') || 'Pengguna';

            document.getElementById('formHapusUser').setAttribute('action', action);
            document.getElementById('hapus_user_name').textContent = label;

            const modal = new bootstrap.Modal(document.getElementById('modalHapusUser'));
            modal.show();
        });
    });
});
</script>
@endpush
@endsection
