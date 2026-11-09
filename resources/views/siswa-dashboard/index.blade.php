<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - e-absen MAN 2 Balam</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo1.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo1.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo1.png') }}">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- QRCode JS Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --font-sans: 'Plus Jakarta Sans', system-ui, sans-serif;
            --primary-emerald: #059669;
            --primary-dark: #064e3b;
        }

        body {
            font-family: var(--font-sans);
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
        }

        .navbar-siswa {
            background: linear-gradient(135deg, #064e3b 0%, #047857 100%);
            box-shadow: 0 4px 20px rgba(6, 78, 59, 0.15);
        }

        .card-custom {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
            transition: all 0.2s ease;
        }

        .card-custom:hover {
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.07);
        }

        /* Digital ID Card Styling */
        .digital-id-card {
            background: #ffffff;
            border-radius: 22px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        .card-top-banner {
            background: linear-gradient(135deg, #064e3b 0%, #047857 50%, #059669 100%);
            color: white;
            padding: 18px 20px 84px;
            text-align: center;
            position: relative;
        }

        .school-emblem {
            width: 44px;
            height: 44px;
            margin: 0 auto 6px;
            background: white;
            border-radius: 12px;
            padding: 4px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .student-avatar-frame {
            width: 144px;
            height: 144px;
            border-radius: 50%;
            border: 4.5px solid #ffffff;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.16);
            object-fit: cover;
            margin-top: -72px;
            background: #ffffff;
        }

        .qr-screen-box {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            padding: 14px;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* Scan Mode Modal */
        .scan-modal-body {
            background: #ffffff;
            text-align: center;
            padding: 2.5rem 1.5rem;
        }
    </style>
</head>
<body>

    <!-- Siswa Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-siswa py-3 sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-white" href="{{ route('siswa.dashboard') }}">
                @php
                    $logoPath = file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : asset('images/logo.svg');
                @endphp
                <img src="{{ $logoPath }}" alt="Logo" width="34" height="34" class="rounded-2 bg-white p-1" onerror="this.src='{{ asset('images/logo.svg') }}'">
                <div>
                    <div style="font-size: 0.95rem; letter-spacing: -0.01em;">MAN 2 BANDAR LAMPUNG</div>
                    <small class="text-white-50 d-block" style="font-size: 0.7rem; font-weight: 500;">Portal Presensi Siswa Digital</small>
                </div>
            </a>

            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-3 py-2 d-none d-sm-inline">
                    <i class="bi bi-person-circle me-1"></i> {{ $siswa->nama }} ({{ $siswa->kelas }})
                </span>

                <button type="button" class="btn btn-warning btn-sm rounded-pill px-3 py-2 fw-semibold shadow-sm text-dark" data-bs-toggle="modal" data-bs-target="#modalGantiPasswordSiswa">
                    <i class="bi bi-key-fill me-1"></i> Ganti Password
                </button>

                <form action="{{ route('logout') }}" method="POST" class="d-inline m-0">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5 text-white" style="background-color: #ef4444; border: none;">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container py-4">

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center gap-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                <div>
                    <strong>Berhasil!</strong> {{ session('success') }}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center gap-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-4 text-danger"></i>
                <div>
                    <strong>Perhatian!</strong> {{ session('error') }}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center gap-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-4 text-danger"></i>
                <div>
                    <strong>Gagal Memproses:</strong>
                    <ul class="mb-0 ps-3 mt-1 small">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">

            <!-- Left Column: Student Digital Attendance Card & Actions -->
            <div class="col-lg-5 col-xl-4">
                <div class="digital-id-card mb-4">
                    <!-- Top Card Banner -->
                    <div class="card-top-banner">
                        <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                            <div class="school-emblem" style="margin: 0; width: 38px; height: 38px;" title="Kementerian Agama RI">
                                <img src="{{ file_exists(public_path('images/logo2.png')) ? asset('images/logo2.png') : asset('images/logo.png') }}" alt="Kemenag" style="width: 100%; height: 100%; object-fit: contain;" onerror="this.src='{{ asset('images/logo.svg') }}'">
                            </div>
                            <div class="school-emblem" style="margin: 0; width: 38px; height: 38px;" title="MAN 2 Bandar Lampung">
                                <img src="{{ file_exists(public_path('images/logo1.png')) ? asset('images/logo1.png') : asset('images/logo.png') }}" alt="MAN 2" style="width: 100%; height: 100%; object-fit: contain;" onerror="this.src='{{ asset('images/logo.svg') }}'">
                            </div>
                        </div>
                        <h6 class="fw-bold mb-0 text-white" style="font-size: 0.88rem; letter-spacing: 0.04em;">MAN 2 BANDAR LAMPUNG</h6>
                        <small class="text-white-50" style="font-size: 0.72rem;">KARTU PRESENSI DIGITAL SISWA</small>
                    </div>

                    <!-- Card Body -->
                    <div class="text-center p-4 pt-0 bg-white">
                        <!-- Student Avatar -->
                        <div class="position-relative d-inline-block mb-3">
                            <img src="{{ $siswa->foto_url }}" alt="{{ $siswa->nama }}" class="student-avatar-frame shadow">
                            <button type="button" class="btn btn-sm btn-light border rounded-circle shadow position-absolute bottom-0 end-0 p-1" style="width: 32px; height: 32px;" data-bs-toggle="modal" data-bs-target="#modalUploadFotoSiswa" title="Upload Foto Baru">
                                <i class="bi bi-camera-fill text-primary" style="font-size: 0.8rem;"></i>
                            </button>
                        </div>

                        <!-- Name & Identity -->
                        <h5 class="fw-bold text-dark mb-1">{{ $siswa->nama }}</h5>
                        <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
                            <span class="badge bg-success rounded-pill px-3 py-1">Kelas {{ $siswa->kelas }}</span>
                            <span class="badge bg-light text-dark border">NIS: {{ $siswa->nis }}</span>
                        </div>

                        <!-- QR Code Container (qrcode = nisn) -->
                        <div class="qr-screen-box mb-3">
                            <div id="qrcode-siswa" class="d-flex justify-content-center"></div>
                            <div class="small fw-bold text-dark mt-2" style="font-size: 0.78rem;">
                                QR Code NISN: <span class="text-primary">{{ $qrValue }}</span>
                            </div>
                        </div>

                        <div class="alert alert-info border-0 rounded-3 p-2 small mb-3 text-start" style="font-size: 0.75rem;">
                            <i class="bi bi-info-circle-fill text-info me-1"></i> <strong>Lupa Kartu Fisik?</strong> Buka mode scan di bawah dan tunjukkan langsung layar HP Anda ke kamera scanner gerbang sekolah!
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2 mb-3">
                            <button type="button" class="btn btn-success fw-bold py-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalScanHP">
                                <i class="bi bi-fullscreen me-1"></i> Tampilkan Mode Scan Layar HP
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill py-2" data-bs-toggle="modal" data-bs-target="#modalUploadFotoSiswa">
                                <i class="bi bi-cloud-arrow-up-fill me-1"></i> Unggah Foto Profil (Folder FotoSiswa)
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm rounded-pill py-2 text-dark fw-semibold" data-bs-toggle="modal" data-bs-target="#modalGantiPasswordSiswa">
                                <i class="bi bi-key-fill me-1 text-warning"></i> Ganti Password Akun
                            </button>
                            <form action="{{ route('logout') }}" method="POST" class="d-grid m-0">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill py-2 fw-bold d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Keluar dari Akun</span>
                                </button>
                            </form>
                        </div>

                        <!-- Card Bottom Strip (Identik dengan Cetak ID Card) -->
                        <div class="py-2 px-3 bg-light border-top text-center text-muted fw-semibold rounded-3" style="font-size: 0.72rem; letter-spacing: 0.03em;">
                            KEMENTERIAN AGAMA RI • MAN 2 BANDAR LAMPUNG
                        </div>
                    </div>
                </div>

                <!-- Card Pengaturan Nomor HP Siswa & Nomor HP Orang Tua -->
                <div class="card-custom p-4 mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="bi bi-whatsapp fs-5"></i>
                            </span>
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Kontak Siswa & Orang Tua</h6>
                                <small class="text-muted" style="font-size: 0.74rem;">Koordinasi informasi kehadiran madrasah</small>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('siswa.update-kontak') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="no_hp_siswa" class="form-label small fw-bold text-dark mb-1">
                                <i class="bi bi-phone text-primary me-1"></i> Nomor HP Siswa
                            </label>
                            <input type="text" name="no_hp_siswa" id="no_hp_siswa" class="form-control form-control-sm @error('no_hp_siswa') is-invalid @enderror" value="{{ old('no_hp_siswa', $siswa->no_hp_siswa) }}" placeholder="Contoh: 081234567890">
                            @error('no_hp_siswa')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="no_hp_ortu" class="form-label small fw-bold text-dark mb-1">
                                <i class="bi bi-people-fill text-success me-1"></i> Nomor HP Orang Tua / Wali
                            </label>
                            <input type="text" name="no_hp_ortu" id="no_hp_ortu" class="form-control form-control-sm @error('no_hp_ortu') is-invalid @enderror" value="{{ old('no_hp_ortu', $siswa->no_hp_ortu) }}" placeholder="Contoh: 081298765432">
                            @error('no_hp_ortu')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Nomor WhatsApp aktif orang tua/wali untuk koordinasi wali kelas.</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-sm rounded-pill py-2 fw-semibold shadow-sm">
                                <i class="bi bi-save me-1"></i> Simpan Nomor HP
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column: Monthly Recap & Semester Recap -->
            <div class="col-lg-7 col-xl-8">

                <!-- 1. Rekapitulasi Bulan Berjalan -->
                <div class="card-custom p-4 mb-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                        <div>
                            <span class="badge bg-emerald-subtle text-success px-3 py-1 rounded-pill fw-bold mb-1" style="background-color: #d1fae5; color: #065f46;">
                                <i class="bi bi-calendar-check me-1"></i> Bulan Berjalan
                            </span>
                            <h5 class="fw-bold text-dark mb-0">Kehadiran Bulan {{ $namaBulan }}</h5>
                        </div>
                        <div class="text-end">
                            <span class="small text-muted d-block">Tingkat Kehadiran:</span>
                            <span class="fs-4 fw-bold text-success">{{ $bulanPersentase }}%</span>
                        </div>
                    </div>

                    <!-- 5 Metric Badges for Current Month -->
                    <div class="row g-2 mb-4 text-center">
                        <div class="col">
                            <div class="p-2 rounded-3 border bg-light">
                                <span class="text-muted d-block small" style="font-size: 0.72rem;">Tepat Waktu</span>
                                <strong class="fs-5 text-success">{{ $bulanTepatWaktu }}</strong>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-2 rounded-3 border bg-light">
                                <span class="text-muted d-block small" style="font-size: 0.72rem;">Terlambat</span>
                                <strong class="fs-5 text-warning">{{ $bulanTerlambat }}</strong>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-2 rounded-3 border bg-light">
                                <span class="text-muted d-block small" style="font-size: 0.72rem;">Izin</span>
                                <strong class="fs-5 text-info">{{ $bulanIzin }}</strong>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-2 rounded-3 border bg-light">
                                <span class="text-muted d-block small" style="font-size: 0.72rem;">Sakit</span>
                                <strong class="fs-5 text-secondary">{{ $bulanSakit }}</strong>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-2 rounded-3 border bg-light">
                                <span class="text-muted d-block small" style="font-size: 0.72rem;">Alfa</span>
                                <strong class="fs-5 text-danger">{{ $bulanAlfa }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Table of attendance records for current month -->
                    <h6 class="fw-bold text-dark mb-2 small text-uppercase tracking-wider">
                        <i class="bi bi-clock-history text-primary me-1"></i> Riwayat Presensi Bulan Ini
                    </h6>
                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Sesi</th>
                                    <th>Jam Scan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($absensiBulan as $ab)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold text-dark">{{ \Carbon\Carbon::parse($ab->tanggal)->translatedFormat('d M Y') }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $ab->sesi?->nama_sesi ?? 'Presensi Pagi' }}</small>
                                        </td>
                                        <td>
                                            <span class="fw-mono">{{ substr($ab->jam_scan, 0, 5) }} WIB</span>
                                        </td>
                                        <td>
                                            @if($ab->status === 'Tepat Waktu')
                                                <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1">Tepat Waktu</span>
                                            @elseif($ab->status === 'Terlambat')
                                                <span class="badge bg-warning-subtle text-warning rounded-pill px-2 py-1">Terlambat</span>
                                            @elseif(in_array($ab->status, ['Izin', 'Ijin']))
                                                <span class="badge bg-info-subtle text-info rounded-pill px-2 py-1">Izin</span>
                                            @elseif($ab->status === 'Sakit')
                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1">Sakit</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger rounded-pill px-2 py-1">Alfa</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted small">
                                            Belum ada catatan presensi yang terekam pada bulan ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. Rekapitulasi Satu Semester -->
                <div class="card-custom p-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                        <div>
                            <span class="badge bg-indigo-subtle text-primary px-3 py-1 rounded-pill fw-bold mb-1" style="background-color: #e0e7ff; color: #4338ca;">
                                <i class="bi bi-award-fill me-1"></i> Akumulasi Semester
                            </span>
                            <h5 class="fw-bold text-dark mb-0">{{ $namaSemester }}</h5>
                        </div>
                        <div class="text-end">
                            <span class="small text-muted d-block">Persentase Semester:</span>
                            <span class="fs-4 fw-bold" style="color: #4f46e5;">{{ $semesterPersentase }}%</span>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                            <span>Kepatuhan Kehadiran Semester:</span>
                            <strong>{{ $semesterTotalHadir }} Hadir</strong>
                        </div>
                        <div class="progress" style="height: 10px; border-radius: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $semesterPersentase }}%;" aria-valuenow="{{ $semesterPersentase }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>

                    <!-- Metric Grid for Semester -->
                    <div class="row g-3 text-center">
                        <div class="col-sm-4">
                            <div class="p-3 rounded-4 bg-light border">
                                <span class="text-muted d-block small">Tepat Waktu</span>
                                <h4 class="fw-bold text-success mb-0">{{ $semesterTepatWaktu }}</h4>
                                <small class="text-muted" style="font-size: 0.72rem;">Hari</small>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 rounded-4 bg-light border">
                                <span class="text-muted d-block small">Terlambat</span>
                                <h4 class="fw-bold text-warning mb-0">{{ $semesterTerlambat }}</h4>
                                <small class="text-muted" style="font-size: 0.72rem;">Hari</small>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 rounded-4 bg-light border">
                                <span class="text-muted d-block small">Izin & Sakit</span>
                                <h4 class="fw-bold text-info mb-0">{{ $semesterIzin + $semesterSakit }}</h4>
                                <small class="text-muted" style="font-size: 0.72rem;">Hari</small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Modal Fullscreen / High Contrast Mode Scan Layar HP -->
    <div class="modal fade" id="modalScanHP" tabindex="-1" aria-labelledby="modalScanHPLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom py-3 bg-dark text-white">
                    <h6 class="modal-title fw-bold text-white mb-0" id="modalScanHPLabel">
                        <i class="bi bi-qr-code-scan text-warning me-2"></i> Mode Scan Layar HP - MAN 2 Bandar Lampung
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body scan-modal-body">
                    <p class="text-muted small mb-3">Arahkan layar HP ini tepat di depan lensa kamera scanner di gerbang sekolah.</p>

                    <!-- Huge QR Code Box -->
                    <div class="p-3 bg-white rounded-4 shadow-sm border border-2 border-dark d-inline-block mb-3">
                        <div id="qrcode-fullscreen" class="d-flex justify-content-center"></div>
                    </div>

                    <h4 class="fw-bold text-dark mb-1">{{ $siswa->nama }}</h4>
                    <p class="text-muted mb-2">Kelas: <strong class="text-dark">{{ $siswa->kelas }}</strong> | NISN: <strong class="text-primary">{{ $qrValue }}</strong></p>
                    <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">
                        <i class="bi bi-check-circle-fill me-1"></i> QR Code Terverifikasi
                    </span>
                </div>
                <div class="modal-footer bg-light py-2 justify-content-center">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Upload Foto Siswa (Tampung dalam folder FotoSiswa) -->
    <div class="modal fade" id="modalUploadFotoSiswa" tabindex="-1" aria-labelledby="modalUploadFotoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4 text-start">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark" id="modalUploadFotoLabel">
                        <i class="bi bi-camera-fill text-success me-2"></i> Upload Foto Profil Siswa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('siswa.upload-foto') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 rounded-3 small mb-3">
                            <i class="bi bi-folder-check text-info me-1"></i> Foto Anda akan disimpan ke dalam folder <strong>FotoSiswa</strong> dan langsung tampil pada kartu absen digital HP Anda.
                        </div>

                        <!-- Live Preview -->
                        <div class="text-center mb-3">
                            <img id="previewFotoUpload" src="{{ $siswa->foto_url }}" alt="Preview" class="rounded-circle border border-3 shadow-sm mb-2" style="width: 100px; height: 100px; object-fit: cover;">
                            <div class="text-muted small">Pratinjau Foto</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Pilih File Foto (JPG, PNG, WebP) <span class="text-danger">*</span></label>
                            <input type="file" name="foto" class="form-control @error('foto') is-invalid @enderror" accept="image/*" required onchange="previewFotoSiswa(this)">
                            @error('foto')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted" style="font-size: 0.72rem;">Gunakan pas foto resmi berseragam madrasah. Maksimal 5MB.</small>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light py-3">
                        <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm">
                            <i class="bi bi-upload me-1"></i> Simpan ke FotoSiswa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ganti Password Siswa -->
    <div class="modal fade" id="modalGantiPasswordSiswa" tabindex="-1" aria-labelledby="modalGantiPasswordLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #064e3b 0%, #047857 100%);">
                    <h6 class="modal-title fw-bold" id="modalGantiPasswordLabel">
                        <i class="bi bi-key-fill me-2"></i> Ganti Password Akun Siswa
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('siswa.ganti-password') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 rounded-3 p-3 small mb-3">
                            <i class="bi bi-shield-lock-fill text-info me-1"></i>
                            Username Anda: <strong>{{ $siswa->nisn ?: $siswa->nis }}</strong> (NISN).<br>
                            Password awal adalah nomor NISN. Anda dapat mengganti kata sandi demi keamanan akun Anda (minimal 6 karakter).
                        </div>

                        @if ($errors->has('password_lama') || $errors->has('password_baru'))
                            <div class="alert alert-danger border-0 rounded-3 p-2 small mb-3">
                                <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>
                                {{ $errors->first('password_lama') ?: $errors->first('password_baru') }}
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Password Saat Ini / Password Lama <span class="text-danger">*</span></label>
                            <input type="password" name="password_lama" class="form-control" placeholder="Masukkan password saat ini (atau nomor NISN)" required>
                            <small class="text-muted" style="font-size: 0.72rem;">Jika belum pernah diganti, isikan nomor NISN Anda.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Password Baru <span class="text-danger">*</span></label>
                            <input type="password" name="password_baru" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                            <input type="password" name="password_baru_confirmation" class="form-control" placeholder="Ulangi password baru" required minlength="6">
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light py-3">
                        <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm">
                            <i class="bi bi-check-circle-fill me-1"></i> Simpan Password Baru
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const qrText = "{{ $qrValue }}";

            // 1. Render Card QR Code (qrcode = nisn)
            const qrcodeElem = document.getElementById('qrcode-siswa');
            if (qrcodeElem && qrText) {
                new QRCode(qrcodeElem, {
                    text: qrText,
                    width: 180,
                    height: 180,
                    colorDark: "#064e3b",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }

            // 2. Render Fullscreen Scan Modal QR Code (big & clear)
            const qrcodeFullscreenElem = document.getElementById('qrcode-fullscreen');
            if (qrcodeFullscreenElem && qrText) {
                new QRCode(qrcodeFullscreenElem, {
                    text: qrText,
                    width: 230,
                    height: 230,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
        });

        function previewFotoSiswa(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewFotoUpload').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
