<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($title) && $title ? $title . ' - ' : 'Scanner Presensi Siswa - ' }}e-absen MAN 2 Balam</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo1.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo1.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo1.png') }}">
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #f1f5f9;
        }
        .scanner-nav {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark scanner-nav py-3">
        <div class="container d-flex align-items-center justify-content-between">
            <a href="{{ route('dashboard') }}" class="navbar-brand mb-0 h1 d-flex align-items-center gap-2 fw-bold text-white text-decoration-none">
                <span class="p-1 rounded-3 bg-white d-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                    <img src="{{ file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : asset('images/logo.svg') }}" alt="MAN 2 Logo" style="width: 100%; height: 100%; object-fit: contain;">
                </span>
                <div>
                    <div style="font-size: 0.95rem; line-height: 1.2;">MAN 2 BANDAR LAMPUNG</div>
                    <small class="text-indigo-300" style="color: #a5b4fc; font-size: 0.72rem; font-weight: normal;">Scanner Presensi QR Code</small>
                </div>
            </a>

            <div class="d-flex align-items-center gap-2">
                @if(Auth::check())
                    @php $currentUser = Auth::user(); @endphp
                    @if($currentUser->hasRole('admin'))
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-light btn-sm rounded-pill px-3 py-2 fw-semibold d-flex align-items-center gap-1.5 shadow-sm">
                            <i class="bi bi-grid-1x2-fill"></i>
                            <span class="d-none d-sm-inline">Dashboard Admin</span>
                        </a>
                    @elseif($currentUser->hasRole('wali_kelas'))
                        <a href="{{ route('walikelas.dashboard') }}" class="btn btn-outline-light btn-sm rounded-pill px-3 py-2 fw-semibold d-flex align-items-center gap-1.5 shadow-sm">
                            <i class="bi bi-mortarboard-fill"></i>
                            <span class="d-none d-sm-inline">Dashboard Wali Kelas</span>
                        </a>
                    @elseif($currentUser->hasRole('guru'))
                        <a href="{{ route('guru.dashboard') }}" class="btn btn-outline-light btn-sm rounded-pill px-3 py-2 fw-semibold d-flex align-items-center gap-1.5 shadow-sm">
                            <i class="bi bi-person-workspace"></i>
                            <span class="d-none d-sm-inline">Dashboard Guru</span>
                        </a>
                    @endif

                    <span class="badge bg-white bg-opacity-20 text-white px-3 py-2 rounded-pill d-none d-md-inline">
                        <i class="bi bi-person-circle me-1"></i> {{ $currentUser->hasRole('guru') && ! $currentUser->hasRole('admin') ? 'Guru: ' : '' }}{{ $currentUser->name ?: $currentUser->username }}
                    </span>

                    <form action="{{ route('logout') }}" method="POST" class="d-inline m-0">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5 text-white" style="background-color: #ef4444; border: none;">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Keluar</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm rounded-pill px-3 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                    </a>
                @endif
            </div>
        </div>
    </nav>

    <main class="py-4">
        <div class="container">
            @yield('content')
        </div>
    </main>

    @livewireScripts
</body>
</html>
