<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($title) && $title ? $title . ' - ' : '' }}e-absen MAN 2 Balam</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo1.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo1.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo1.png') }}">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- QRCode JS Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        :root {
            --font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --sidebar-width: 260px;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-active: #4f46e5;
            --topbar-height: 70px;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #0ea5e9;
            --bg-page: #f8fafc;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--bg-page);
            color: #1e293b;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
        }

        /* Sidebar Styling */
        #sidebar-wrapper {
            width: var(--sidebar-width);
            height: 100vh;
            height: 100dvh;
            background: #006837 url('{{ asset('images/navbg.png') }}') center / cover no-repeat;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1040;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.12);
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            padding: 0 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(0, 50, 25, 0.45);
        }

        .sidebar-brand .logo-icon {
            width: 42px;
            height: 42px;
            background: #ffffff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .sidebar-menu {
            min-height: 0;
            padding: 0.55rem 0.75rem;
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .menu-category {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #cbd5e1;
            font-weight: 700;
            padding: 0.3rem 0.7rem 0.2rem;
            margin-top: 0.35rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.38rem 0.85rem;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.81rem;
            transition: all 0.2s ease;
            margin-bottom: 0.1rem;
            position: relative;
        }

        .sidebar-link i {
            font-size: 1rem;
            color: #94a3b8;
            transition: color 0.2s ease;
        }

        .sidebar-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.07);
        }

        .sidebar-link:hover i {
            color: #818cf8;
        }

        .sidebar-link.active {
            color: #ffffff;
            background: rgba(0, 55, 28, 0.78);
            box-shadow: 0 4px 12px rgba(0, 35, 18, 0.3);
            font-weight: 600;
        }

        .sidebar-link.active i {
            color: #ffffff;
        }

        .sidebar-link .badge-nav {
            margin-left: auto;
            font-size: 0.65rem;
            padding: 0.2rem 0.45rem;
            border-radius: 20px;
            flex-shrink: 0;
            white-space: nowrap;
        }

        .sidebar-link span:not(.badge-nav) {
            white-space: nowrap;
        }

        .sidebar-footer {
            flex-shrink: 0;
            position: sticky;
            bottom: 0;
            z-index: 1;
            background: rgba(0, 50, 25, 0.82);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Quick Scanner Sidebar Banner */
        .sidebar-cta {
            padding: 1rem;
            margin: 0.75rem;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.2) 0%, rgba(147, 51, 234, 0.2) 100%);
            border: 1px solid rgba(129, 140, 248, 0.25);
            text-align: center;
        }

        /* Main Content Wrapper */
        #content-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Sidebar disembunyikan (desktop) untuk memperluas area konten */
        @media (min-width: 992px) {
            body.sidebar-collapsed #sidebar-wrapper {
                margin-left: calc(-1 * var(--sidebar-width));
            }

            body.sidebar-collapsed #content-wrapper {
                margin-left: 0;
            }
        }

        /* Panah indikator arah pada tombol toggle sidebar */
        #sidebar-toggle-btn i {
            display: inline-block;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body.sidebar-collapsed #sidebar-toggle-btn i {
            transform: rotate(180deg);
        }

        /* Topbar Header */
        .topbar {
            height: var(--topbar-height);
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.75rem;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .clock-badge {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            padding: 0.45rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #334155;
        }

        .clock-badge .live-dot {
            width: 8px;
            height: 8px;
            background-color: var(--success);
            border-radius: 50%;
            animation: pulse-green 1.8s infinite;
        }

        @keyframes pulse-green {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }

            70% {
                transform: scale(1);
                box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
            }

            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        /* Modern Card System */
        .card-custom {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-custom:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
        }

        .stat-card {
            border-radius: 16px;
            padding: 1.35rem;
            position: relative;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            transition: all 0.25s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px -10px rgba(0, 0, 0, 0.08);
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        /* Buttons */
        .btn-primary-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: #ffffff;
            border: none;
            font-weight: 600;
            border-radius: 10px;
            padding: 0.55rem 1.25rem;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
            transition: all 0.2s ease;
        }

        .btn-primary-gradient:hover {
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
        }

        /* Mobile drawer */
        @media (max-width: 991.98px) {
            #sidebar-wrapper {
                margin-left: calc(-1 * var(--sidebar-width));
            }

            #sidebar-wrapper.toggled {
                margin-left: 0;
            }

            #content-wrapper {
                margin-left: 0;
            }

            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(15, 23, 42, 0.6);
                backdrop-filter: blur(4px);
                z-index: 1030;
            }

            .sidebar-overlay.active {
                display: block;
            }
        }
    </style>
    @stack('styles')
</head>

<body>
    @php
        $currentUser = Auth::user();
        $userName = $currentUser ? ($currentUser->name ?: $currentUser->username) : 'Administrator';
        $userRoles = $currentUser ? $currentUser->roles : ['admin'];
        $roleLabels = array_map(function ($r) {
            return match ($r) {
                'siswa' => 'Siswa',
                'guru' => 'Guru',
                'wali_kelas' => 'Wali Kelas',
                'kepsek' => 'Kepala Sekolah',
                default => 'Admin',
            };
        }, $userRoles);
        $primaryRoleLabel = implode(', ', $roleLabels);
    @endphp

    <!-- Mobile Overlay -->
    <div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar Wrapper -->
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            <div class="d-flex align-items-center gap-3">
                <div class="logo-icon">
                    <img src="{{ file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : asset('images/logo.svg') }}"
                        alt="MAN 2 Logo" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div>
                    <h6 class="mb-0 text-white fw-bold" style="letter-spacing: -0.01em; font-size: 0.88rem;">MAN 2
                        BANDAR LAMPUNG</h6>
                    <small class="text-indigo-300" style="color: #a5b4fc; font-size: 0.72rem;">Sistem Presensi QR
                        Code</small>
                </div>
            </div>
        </div>

        <div class="sidebar-menu">
            @if ($currentUser && $currentUser->hasRole('admin'))
                <div class="menu-category">Utama</div>
                <a href="{{ route('dashboard') }}"
                    class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard Absensi</span>
                </a>
                <a href="{{ route('dashboard.mengajar') }}"
                    class="sidebar-link {{ request()->routeIs('dashboard.mengajar*') ? 'active' : '' }}">
                    <i class="bi bi-easel2-fill"></i>
                    <span>Dashboard Mengajar</span>
                </a>

                <div class="menu-category">Manajemen Siswa dan Kelas</div>
                <a href="{{ route('siswa.index') }}"
                    class="sidebar-link {{ request()->routeIs('siswa.*') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i>
                    <span>Kelola Siswa</span>
                </a>
                <a href="{{ route('kelas.index') }}"
                    class="sidebar-link {{ request()->routeIs('kelas.*') ? 'active' : '' }}">
                    <i class="bi bi-door-open-fill"></i>
                    <span>Kelola Kelas</span>
                </a>
                <a href="{{ route('qrcode.index') }}"
                    class="sidebar-link {{ request()->routeIs('qrcode.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge-fill"></i>
                    <span>Generate Kartu Absen</span>
                </a>

                <div class="menu-category">Manajemen Jadwal</div>
                <a href="{{ route('jam-belajar.index') }}"
                    class="sidebar-link {{ request()->routeIs('jam-belajar.*') ? 'active' : '' }}">
                    <i class="bi bi-hourglass-split"></i>
                    <span>Kelola Waktu Belajar</span>
                </a>
                <a href="{{ route('mapel.index') }}"
                    class="sidebar-link {{ request()->routeIs('mapel.*') ? 'active' : '' }}">
                    <i class="bi bi-journal-bookmark-fill"></i>
                    <span>Kelola Mata Pelajaran</span>
                </a>
                <a href="{{ route('jadwal.index') }}"
                    class="sidebar-link {{ request()->routeIs('jadwal.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar2-week-fill"></i>
                    <span>Kelola Jadwal Pelajaran</span>
                </a>
                <a href="{{ route('jurnal-mengajar.index') }}"
                    class="sidebar-link {{ request()->routeIs('jurnal-mengajar.*') ? 'active' : '' }}">
                    <i class="bi bi-journal-text"></i>
                    <span>Rekapitulasi Absen Guru</span>
                </a>

                <div class="menu-category">Laporan & Pengaturan</div>
                <a href="{{ route('rekapitulasi.index') }}"
                    class="sidebar-link {{ request()->routeIs('rekapitulasi.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i>
                    <span>Rekapitulasi Absen Siswa</span>
                </a>
                <a href="{{ route('pengaturan-waktu.index') }}"
                    class="sidebar-link {{ request()->routeIs('pengaturan-waktu.*') ? 'active' : '' }}">
                    <i class="bi bi-clock-history"></i>
                    <span>Pengaturan Waktu</span>
                </a>
                <a href="{{ route('hari-libur.index') }}"
                    class="sidebar-link {{ request()->routeIs('hari-libur.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-x-fill"></i>
                    <span>Pengaturan Hari Libur</span>
                </a>
                <a href="{{ route('pengaturan-akun.index') }}"
                    class="sidebar-link {{ request()->routeIs('pengaturan-akun.*') ? 'active' : '' }}">
                    <i class="bi bi-person-gear"></i>
                    <span>Pengaturan Akun</span>
                </a>
                <a href="{{ route('pengaturan-aplikasi.index') }}"
                    class="sidebar-link {{ request()->routeIs('pengaturan-aplikasi.*') ? 'active' : '' }}">
                    <i class="bi bi-sliders2-vertical"></i>
                    <span>Pengaturan Aplikasi</span>
                </a>
            @elseif($currentUser && ($currentUser->hasRole('wali_kelas') || $currentUser->hasRole('guru')))
                @if ($currentUser->hasRole('guru') && $currentUser->hasRole('wali_kelas'))
                    <div class="menu-category">Menu Guru & Wali Kelas</div>
                @elseif($currentUser->hasRole('wali_kelas'))
                    <div class="menu-category">Menu Wali Kelas</div>
                @else
                    <div class="menu-category">Menu Guru</div>
                @endif
                @if ($currentUser->hasRole('wali_kelas'))
                    <a href="{{ route('walikelas.dashboard') }}"
                        class="sidebar-link {{ request()->routeIs('walikelas.*') || request()->routeIs('wali-kelas.*') ? 'active' : '' }}">
                        <i class="bi bi-mortarboard-fill"></i>
                        <span>Dashboard Wali Kelas</span>
                    </a>
                @endif
                @if ($currentUser->hasRole('guru'))
                    <a href="{{ route('guru.dashboard') }}"
                        class="sidebar-link {{ request()->routeIs('guru.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-person-workspace"></i>
                        <span>Dashboard Guru</span>
                    </a>
                @endif
                <a href="{{ route('guru.absensi-mengajar') }}"
                    class="sidebar-link {{ request()->routeIs('guru.absensi-mengajar*') ? 'active' : '' }}">
                    <i class="bi bi-easel2-fill"></i>
                    <span>Absensi Mengajar</span>
                </a>
                <a href="{{ route('jurnal-mengajar.index') }}"
                    class="sidebar-link {{ request()->routeIs('jurnal-mengajar.*') ? 'active' : '' }}">
                    <i class="bi bi-journal-text"></i>
                    <span>Jurnal Mengajar</span>
                </a>
                <a href="{{ route('scanner') }}"
                    class="sidebar-link {{ request()->routeIs('scanner') ? 'active' : '' }}">
                    <i class="bi bi-qr-code-scan"></i>
                    <span>Scanner Presensi</span>
                </a>
                @if ($currentUser->hasRole('wali_kelas'))
                    <a href="{{ route('rekapitulasi.index') }}"
                        class="sidebar-link {{ request()->routeIs('rekapitulasi.*') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-bar-graph-fill"></i>
                        <span>Rekapitulasi Kelas</span>
                    </a>
                @endif
            @endif
        </div>

        <!-- Sidebar Footer: User Card & Pinned Logout Button -->
        <div class="sidebar-footer p-3 border-top border-secondary border-opacity-25"
            style="background: rgba(0, 50, 25, 0.82);">
            <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom border-secondary border-opacity-25">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                    style="width: 36px; height: 36px; font-size: 0.9rem;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="overflow-hidden" style="line-height: 1.25;">
                    <div class="text-white fw-semibold text-truncate" style="font-size: 0.85rem;"
                        title="{{ $userName }}">{{ $userName }}</div>
                    <small class="text-indigo-300 text-truncate d-block"
                        style="font-size: 0.72rem; color: #a5b4fc;">{{ $primaryRoleLabel }}</small>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST" class="w-100 m-0">
                @csrf
                <button type="submit"
                    class="btn btn-danger btn-sm w-100 rounded-3 py-2 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm text-white"
                    style="background-color: #ef4444; border: none; font-size: 0.88rem;">
                    <i class="bi bi-box-arrow-right fs-6"></i>
                    <span>Keluar (Logout)</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div id="content-wrapper">
        <!-- Topbar Navbar -->
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                {{-- Mobile: buka drawer sidebar --}}
                <button class="btn btn-light d-lg-none border shadow-sm p-2" type="button" onclick="toggleSidebar()"
                    aria-label="Buka Menu Navigasi">
                    <i class="bi bi-list fs-5"></i>
                </button>
                {{-- Desktop: sembunyikan/tampilkan sidebar untuk memperluas konten --}}
                <button id="sidebar-toggle-btn" class="btn btn-light d-none d-lg-inline-flex border shadow-sm p-2"
                    type="button" onclick="toggleSidebarCollapsed()" aria-controls="sidebar-wrapper"
                    aria-expanded="true" title="Sembunyikan/tampilkan menu navigasi"
                    aria-label="Sembunyikan atau tampilkan menu navigasi">
                    <i class="bi bi-chevron-double-left fs-6"></i>
                </button>
                <div class="d-none d-md-block">
                    <h5 class="mb-0 fw-bold text-dark">{{ $pageTitle ?? 'Dashboard Presensi' }}</h5>
                    <small
                        class="text-muted">{{ $pageSubtitle ?? 'Pantau kehadiran siswa secara real-time dan akurat' }}</small>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <!-- Clock Badge -->
                <div class="clock-badge d-none d-sm-flex">
                    <span class="live-dot"></span>
                    <span id="current-day-date" class="text-muted">Memuat...</span>
                    <span class="text-secondary">|</span>
                    <span id="live-clock" class="text-primary fw-bold">00:00:00</span>
                </div>

                <!-- User Dropdown with Multi-Role Badges and Navigation -->
                <div class="dropdown">
                    <button
                        class="btn btn-light border d-flex align-items-center gap-2 rounded-pill px-3 py-1 shadow-sm"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                            style="width: 32px; height: 32px; font-size: 0.85rem;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div class="d-none d-md-flex flex-column text-start" style="line-height: 1.2;">
                            <span class="fw-semibold text-dark text-truncate"
                                style="font-size: 0.85rem; max-width: 140px;">{{ $userName }}</span>
                            <span class="badge bg-primary-subtle text-primary border text-truncate"
                                style="font-size: 0.65rem; max-width: 140px; padding: 2px 6px;">{{ $primaryRoleLabel }}</span>
                        </div>
                        <i class="bi bi-chevron-down text-muted" style="font-size: 0.75rem;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2"
                        style="min-width: 230px;">
                        <li>
                            <div class="px-3 py-2 border-bottom">
                                <p class="mb-0 fw-bold text-dark" style="font-size: 0.88rem;">{{ $userName }}</p>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    @foreach ($roleLabels as $lbl)
                                        <span class="badge bg-success-subtle text-success border"
                                            style="font-size: 0.68rem;">{{ $lbl }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </li>
                        @if ($currentUser && $currentUser->hasRole('wali_kelas'))
                            <li><a class="dropdown-item py-2" href="{{ route('walikelas.dashboard') }}"><i
                                        class="bi bi-mortarboard me-2 text-warning"></i> Dashboard Wali Kelas</a></li>
                        @endif
                        @if ($currentUser && $currentUser->hasRole('guru'))
                            <li><a class="dropdown-item py-2" href="{{ route('guru.dashboard') }}"><i
                                        class="bi bi-person-workspace me-2 text-info"></i> Dashboard Guru</a></li>
                        @endif
                        @if ($currentUser && $currentUser->hasRole('admin'))
                            <li><a class="dropdown-item py-2" href="{{ route('pengaturan-akun.index') }}"><i
                                        class="bi bi-person-gear me-2 text-primary"></i> Pengaturan Akun</a></li>
                            <li><a class="dropdown-item py-2" href="{{ route('pengaturan-waktu.index') }}"><i
                                        class="bi bi-clock me-2 text-primary"></i> Pengaturan Waktu</a></li>
                        @endif
                        @if ($currentUser && ($currentUser->hasRole('admin') || $currentUser->hasRole('wali_kelas')))
                            <li><a class="dropdown-item py-2" href="{{ route('rekapitulasi.index') }}"><i
                                        class="bi bi-file-earmark-bar-graph me-2 text-primary"></i>
                                    {{ $currentUser->hasRole('admin') ? 'Rekapitulasi Absen Siswa' : 'Rekapitulasi Kelas' }}</a>
                            </li>
                        @endif
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="d-inline w-100">
                                @csrf
                                <button type="submit"
                                    class="dropdown-item py-2 text-danger fw-semibold d-flex align-items-center">
                                    <i class="bi bi-box-arrow-right me-2"></i> Keluar (Logout)
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>

                <!-- Standalone Direct Keluar (Logout) Button in Topbar (Always visible) -->
                <form action="{{ route('logout') }}" method="POST" class="d-inline m-0">
                    @csrf
                    <button type="submit"
                        class="btn btn-danger btn-sm rounded-pill px-3 py-1.5 fw-bold d-flex align-items-center gap-1.5 shadow-sm text-white"
                        title="Keluar / Logout dari aplikasi" style="background-color: #ef4444; border: none;">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="d-none d-sm-inline">Keluar</span>
                    </button>
                </form>
            </div>
        </header>

        <!-- Flash Messages -->
        <main class="flex-grow-1 p-3 p-md-4">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 d-flex align-items-center gap-3 mb-4"
                    role="alert">
                    <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                    <div>
                        <strong>Sukses!</strong> {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 d-flex align-items-center gap-3 mb-4"
                    role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-4 text-danger"></i>
                    <div>
                        <strong>Perhatian!</strong> {{ session('error') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm rounded-3 d-flex align-items-center gap-3 mb-4"
                    role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
                    <div>
                        <strong>Data Duplikat Dicegah!</strong> {{ session('warning') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4"
                    role="alert">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-exclamation-octagon-fill text-danger fs-5"></i>
                        <strong>Terdapat beberapa kesalahan input:</strong>
                    </div>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white border-top py-3 px-4 text-center text-muted" style="font-size: 0.85rem;">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <span>&copy; {{ date('Y') }} <strong>MAN 2 Bandar Lampung</strong> - Sistem Presensi QR Code
                    Siswa.</span>
                <span class="text-secondary">Waktu Server: <span
                        class="badge bg-light text-dark border">{{ date('d M Y') }}</span></span>
            </div>
        </footer>
    </div>

    <!-- Livewire Scripts -->
    @livewireScripts

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar-wrapper');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('toggled');
            overlay.classList.toggle('active');
        }

        /**
         * Sembunyikan/tampilkan sidebar pada layar desktop untuk memperluas area konten.
         * Pilihan pengguna disimpan di localStorage agar bertahan antar halaman.
         */
        function toggleSidebarCollapsed(forceState) {
            const collapsed = typeof forceState === 'boolean' ?
                forceState :
                !document.body.classList.contains('sidebar-collapsed');

            document.body.classList.toggle('sidebar-collapsed', collapsed);

            const btn = document.getElementById('sidebar-toggle-btn');
            if (btn) {
                btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            }

            try {
                localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0');
            } catch (e) {
                // localStorage tidak tersedia (mode privat) - abaikan
            }
        }

        // Terapkan preferensi tersimpan sebelum halaman digambar agar tidak berkedip.
        (function restoreSidebarState() {
            try {
                if (localStorage.getItem('sidebar-collapsed') === '1') {
                    toggleSidebarCollapsed(true);
                }
            } catch (e) {
                // abaikan
            }
        })();

        // Di layar kecil sidebar memakai drawer, jadi state "collapsed" tidak berlaku.
        window.addEventListener('resize', function() {
            if (window.innerWidth < 992) {
                document.body.classList.remove('sidebar-collapsed');
                const sidebar = document.getElementById('sidebar-wrapper');
                const overlay = document.getElementById('sidebar-overlay');
                if (sidebar) sidebar.classList.remove('toggled');
                if (overlay) overlay.classList.remove('active');
            } else {
                try {
                    if (localStorage.getItem('sidebar-collapsed') === '1') {
                        document.body.classList.add('sidebar-collapsed');
                    }
                } catch (e) {
                    // abaikan
                }
            }
        });

        // Live Clock script in Indonesian locale
        function updateClock() {
            const now = new Date();
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

            const dayName = days[now.getDay()];
            const date = now.getDate();
            const monthName = months[now.getMonth()];
            const year = now.getFullYear();

            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');

            const dateStr = `${dayName}, ${date} ${monthName} ${year}`;
            const timeStr = `${hours}:${minutes}:${seconds} WIB`;

            const dateElem = document.getElementById('current-day-date');
            const clockElem = document.getElementById('live-clock');

            if (dateElem) dateElem.textContent = dateStr;
            if (clockElem) clockElem.textContent = timeStr;
        }

        setInterval(updateClock, 1000);
        updateClock();
    </script>
    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>

</html>
