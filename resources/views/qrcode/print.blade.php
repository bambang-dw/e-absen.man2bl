<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Kartu Absen Siswa (Portrait A4) - e-absen MAN 2 Balam</title>
    
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
    
    <!-- QRCode JS Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --font-sans: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --primary-emerald: #059669;
            --primary-dark: #064e3b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-sans);
            background-color: #f1f5f9;
            color: #0f172a;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        /* Top Action Toolbar on Screen */
        .no-print-toolbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 1050;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
            padding: 0.85rem 1.5rem;
        }

        /* Printable A4 Container */
        .a4-sheet-container {
            max-width: 210mm;
            margin: 20px auto;
            background: #ffffff;
            padding: 10mm 10mm;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            min-height: 297mm;
        }

        /* Grid of Portrait ID Cards (3 columns x 3 rows = 9 cards per A4 page) */
        .cards-grid-portrait {
            display: grid;
            grid-template-columns: repeat(3, 53.98mm);
            gap: 5mm 5mm;
            justify-content: center;
        }

        /* Portrait ID Card (CR-80 / ISO 7810 ID-1 Standard: 53.98mm x 85.60mm) */
        .digital-id-card-portrait {
            width: 53.98mm;
            height: 85.60mm;
            min-height: 85.60mm;
            max-height: 85.60mm;
            background: #ffffff;
            border-radius: 3.18mm; /* Standar radius sudut kartu PVC CR-80 */
            overflow: hidden;
            border: 0.75px dashed #94a3b8; /* Garis pemotong presisi untuk mesin plong */
            position: relative;
            page-break-inside: avoid;
            break-inside: avoid;
            display: flex;
            flex-direction: column;
            text-align: center;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
            margin: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Top Gradient Banner */
        .card-top-banner {
            background: linear-gradient(135deg, #064e3b 0%, #047857 50%, #059669 100%) !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color: #ffffff !important;
            padding: 2.2mm 2mm 15mm;
            text-align: center;
            position: relative;
        }

        .dual-emblem-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            margin-bottom: 2px;
        }

        .school-emblem-mini {
            width: 14px;
            height: 14px;
            background: #ffffff;
            border-radius: 3px;
            padding: 1.5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.18);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .school-emblem-mini img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .card-institution-title {
            font-size: 6.8pt;
            font-weight: 800;
            color: #ffffff !important;
            letter-spacing: 0.02em;
            margin: 0;
            line-height: 1.15;
            text-transform: uppercase;
        }

        .card-institution-sub {
            font-size: 4.8pt;
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 600;
            letter-spacing: 0.03em;
            margin: 1px 0 0;
            text-transform: uppercase;
        }

        /* Card Content Area */
        .card-body-portrait {
            padding: 0 2.5mm 1mm;
            background: #ffffff;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
        }

        /* Student Avatar Frame (Proporsi 150%: 17.5mm x 17.5mm, tertutup latar hijau 50%) */
        .student-avatar-frame {
            width: 17.5mm;
            height: 17.5mm;
            border-radius: 50%;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.16);
            object-fit: cover;
            margin-top: -8.75mm;
            background: #ffffff;
            margin-bottom: 1.5px;
            position: relative;
            z-index: 2;
        }

        .student-name-title {
            font-size: 7.2pt;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 1px;
            line-height: 1.15;
            max-width: 48mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .badge-kelas-pill {
            background-color: #059669 !important;
            color: #ffffff !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            font-size: 5.5pt;
            font-weight: 700;
            border-radius: 12px;
            padding: 1px 6px;
            display: inline-block;
            line-height: 1.2;
        }

        .badge-nis-box {
            font-size: 5.5pt;
            color: #334155;
            background: #f1f5f9;
            border: 0.5px solid #cbd5e1;
            border-radius: 12px;
            padding: 1px 5px;
            font-weight: 600;
            display: inline-block;
            line-height: 1.2;
        }

        /* QR Code Container (Dioptimalkan agar proporsional dan tidak ada sisa ruang berlebih) */
        .qr-screen-box-portrait {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 2px 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-top: 1px;
            margin-bottom: 1px;
            width: fit-content;
        }

        .qr-caption-text {
            font-size: 4.8pt;
            font-weight: 700;
            color: #064e3b;
            margin-top: 1.5px;
            letter-spacing: -0.01em;
            line-height: 1;
        }

        /* Card Bottom Strip */
        .card-bottom-strip {
            background: #f8fafc !important;
            border-top: 0.5px solid #e2e8f0;
            padding: 1.5mm 2mm;
            font-size: 4.5pt;
            color: #64748b !important;
            font-weight: 600;
            letter-spacing: 0.02em;
            margin-top: auto;
            border-radius: 0 0 3mm 3mm;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            line-height: 1;
        }

        /* PRINT STYLES */
        @media print {
            .no-print-toolbar {
                display: none !important;
            }

            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .a4-sheet-container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                min-height: auto !important;
            }

            .cards-grid-portrait {
                display: grid !important;
                grid-template-columns: repeat(3, 53.98mm) !important;
                gap: 4mm 4mm !important;
                justify-content: center !important;
            }

            .digital-id-card-portrait {
                width: 53.98mm !important;
                height: 85.60mm !important;
                min-height: 85.60mm !important;
                max-height: 85.60mm !important;
                border: 0.5px dashed #94a3b8 !important;
                box-shadow: none !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            @page {
                size: A4 portrait;
                margin: 8mm 6mm;
            }
        }
    </style>
</head>
<body>

    <!-- On-Screen Action Toolbar -->
    <header class="no-print-toolbar">
        <div class="container-fluid px-0 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm" title="Kembali ke Dashboard">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
                <div class="d-flex align-items-center gap-2">
                    <span class="p-1 rounded-3 bg-white border d-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px;">
                        <img src="{{ file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : asset('images/logo.svg') }}" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                    </span>
                    <div>
                        <h6 class="mb-0 fw-bold text-dark">Generate Kartu Absen Siswa (Portrait A4)</h6>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0" style="font-size: 0.72rem;">
                                <i class="bi bi-aspect-ratio me-1"></i> Standar CR-80: 85,60 mm × 53,98 mm
                            </span>
                            <small class="text-muted" style="font-size: 0.75rem;">(Pas untuk mesin plong/pemotong kartu PVC)</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Filter Kelas & Search Form -->
                <form action="{{ route('qrcode.index') }}" method="GET" class="d-flex align-items-center gap-2">
                    <select name="kelas" class="form-select form-select-sm bg-light" style="min-width: 160px;" onchange="this.form.submit()">
                        <option value="">-- Semua Kelas ({{ $totalSiswa }}) --</option>
                        @foreach($daftarKelas as $k)
                            <option value="{{ $k }}" {{ ($kelas ?? request('kelas')) == $k ? 'selected' : '' }}>Kelas {{ $k }}</option>
                        @endforeach
                    </select>

                    <div class="input-group input-group-sm" style="max-width: 220px;">
                        <input type="text" name="search" class="form-control bg-light" placeholder="Cari nama / NISN..." value="{{ $search ?? request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>

                    @if(request('kelas') || request('search'))
                        <a href="{{ route('qrcode.index') }}" class="btn btn-light btn-sm border" title="Hapus Filter"><i class="bi bi-x-circle"></i></a>
                    @endif
                </form>

                <!-- Big Print Button -->
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2" onclick="window.print()">
                    <i class="bi bi-printer-fill fs-6"></i>
                    <span>Cetak Kartu (Ctrl + P)</span>
                </button>
            </div>
        </div>
    </header>

    <!-- A4 Printable Sheet Area -->
    <main class="py-4">
        <div class="a4-sheet-container">
            <div class="cards-grid-portrait">
                @forelse($siswas as $s)
                    @php
                        // QR Code = NISN (fallback NIS)
                        $qrValue = $s->nisn ?: $s->nis;
                        $logo1 = file_exists(public_path('images/logo1.png')) ? asset('images/logo1.png') : asset('images/logo.png');
                        $logo2 = file_exists(public_path('images/logo2.png')) ? asset('images/logo2.png') : asset('images/logo.svg');
                    @endphp

                    <div class="digital-id-card-portrait">
                        <!-- Top Card Banner (Gradient Hijau Madrasah) -->
                        <div class="card-top-banner">
                            <div class="dual-emblem-box">
                                <div class="school-emblem-mini" title="Kementerian Agama RI">
                                    <img src="{{ $logo2 }}" alt="Kemenag" onerror="this.src='{{ asset('images/logo.svg') }}'">
                                </div>
                                <div class="school-emblem-mini" title="MAN 2 Bandar Lampung">
                                    <img src="{{ $logo1 }}" alt="MAN 2" onerror="this.src='{{ asset('images/logo.svg') }}'">
                                </div>
                            </div>
                            <h6 class="card-institution-title">MAN 2 BANDAR LAMPUNG</h6>
                            <p class="card-institution-sub">KARTU PRESENSI DIGITAL SISWA</p>
                        </div>

                        <!-- Card Body (Identik dengan Dashboard Siswa) -->
                        <div class="card-body-portrait">
                            <div class="w-100 d-flex flex-column align-items-center">
                                <!-- Student Avatar (150% Size, 50% Overlap) -->
                                <img src="{{ $s->foto_url }}" alt="{{ $s->nama }}" class="student-avatar-frame">

                                <!-- Student Name & Identity -->
                                <h5 class="student-name-title" title="{{ $s->nama }}">{{ $s->nama }}</h5>
                                <div class="d-flex justify-content-center align-items-center gap-1 mb-1">
                                    <span class="badge-kelas-pill">Kelas {{ $s->kelas }}</span>
                                    <span class="badge-nis-box">NIS: {{ $s->nis }}</span>
                                </div>
                            </div>

                            <!-- QR Code NISN Container (Diperbesar) -->
                            <div class="qr-screen-box-portrait">
                                <div id="qrcode-card-{{ $s->id }}" class="qrcode-render" data-qr-value="{{ $qrValue }}"></div>
                                <div class="qr-caption-text">
                                    QR Code NISN: {{ $qrValue }}
                                </div>
                            </div>
                        </div>

                        <!-- Card Bottom Strip -->
                        <div class="card-bottom-strip">
                            KEMENTERIAN AGAMA RI • MAN 2 BANDAR LAMPUNG
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-person-x fs-1 text-muted d-block mb-2"></i>
                        <h5 class="fw-bold text-dark">Tidak Ada Data Siswa</h5>
                        <p class="text-muted small">Coba ubah filter kelas atau kata kunci pencarian Anda.</p>
                        <a href="{{ route('qrcode.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-4">Tampilkan Semua Siswa</a>
                    </div>
                @endforelse
            </div>
        </div>
    </main>

    <!-- Render QR Codes using QRCode.js with crisp resolution -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.qrcode-render').forEach(function(el) {
                const qrValue = el.getAttribute('data-qr-value');
                if (qrValue) {
                    new QRCode(el, {
                        text: qrValue,
                        width: 82,
                        height: 82,
                        colorDark: "#064e3b",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.M
                    });
                }
            });
        });
    </script>
</body>
</html>
