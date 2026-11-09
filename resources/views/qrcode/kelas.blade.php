<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Code Kelas (A4) - e-absen MAN 2 Bandar Lampung</title>

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

        /* Toolbar layar (tidak tercetak) */
        .no-print-toolbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 1050;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
            padding: 0.85rem 1.5rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .no-print-toolbar .btn {
            font-size: 0.82rem;
            font-weight: 600;
            border-radius: 50rem;
            padding: 0.45rem 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .btn-outline-secondary { border: 1px solid #cbd5e1; color: #475569; background: #fff; }
        .btn-success { background: var(--primary-emerald); border: 1px solid var(--primary-emerald); color: #fff; cursor: pointer; }
        .btn-success:hover { background: #047857; }
        .btn-light { background: #f8fafc; border: 1px solid #e2e8f0; color: #334155; }

        /* Lembar A4 */
        .a4-sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: #ffffff;
            padding: 12mm 10mm;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
        }

        .sheet-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid var(--primary-dark);
            padding-bottom: 3mm;
            margin-bottom: 6mm;
        }

        .sheet-header .brand {
            display: flex;
            align-items: center;
            gap: 3mm;
        }

        .sheet-header .brand img {
            width: 11mm;
            height: 11mm;
            object-fit: contain;
        }

        .sheet-header h1 {
            font-size: 11pt;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.01em;
            color: var(--primary-dark);
        }

        .sheet-header p {
            font-size: 7.5pt;
            margin: 0;
            color: #64748b;
        }

        .sheet-header .sheet-meta {
            text-align: right;
            font-size: 7.5pt;
            color: #64748b;
            line-height: 1.5;
        }

        /* Satu kelas per lembar A4 (full page) */
        .qr-card-full {
            flex: 1;
            border: 3px solid var(--primary-dark);
            border-radius: 4mm;
            padding: 10mm 8mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: #ffffff;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .qr-card-full .sekolah {
            font-size: 11pt;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--primary-dark);
            margin-bottom: 8mm;
        }

        .qr-card-full .qr-box {
            padding: 8mm;
            border: 3px solid var(--primary-dark);
            border-radius: 5mm;
            background: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .qr-card-full .qr-box canvas,
        .qr-card-full .qr-box img {
            display: block;
            width: 108mm !important;
            height: 108mm !important;
            image-rendering: pixelated;
        }

        .qr-card-full .kelas-nama {
            font-size: 48pt;
            font-weight: 800;
            line-height: 1;
            color: var(--primary-dark);
            margin: 8mm 0 2mm;
            letter-spacing: -0.02em;
        }

        .qr-card-full .kelas-label {
            font-size: 14pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            color: var(--primary-emerald);
            margin-bottom: 4mm;
        }

        .qr-card-full .kode {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 11pt;
            color: #475569;
            background: #f1f5f9;
            border-radius: 50rem;
            padding: 2mm 7mm;
        }

        .qr-card-full .petunjuk {
            font-size: 9.5pt;
            color: #64748b;
            margin-top: 6mm;
            line-height: 1.5;
            max-width: 150mm;
        }

        .empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            text-align: center;
            padding: 20mm 0;
        }

        /* Aturan cetak */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }

            body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .no-print-toolbar {
                display: none !important;
            }

            .a4-sheet {
                width: 210mm !important;
                height: 297mm !important;
                min-height: 297mm !important;
                margin: 0 !important;
                padding: 12mm 10mm !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                page-break-after: always;
                break-after: page;
                overflow: hidden;
            }

            .a4-sheet:last-child {
                page-break-after: auto;
                break-after: auto;
            }

            .qr-card-full {
                border: 3px solid #064e3b !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    @php
        $logo1 = file_exists(public_path('images/logo1.png')) ? asset('images/logo1.png') : asset('images/logo.png');
        $logo2 = file_exists(public_path('images/logo2.png')) ? asset('images/logo2.png') : asset('images/logo.svg');
        $totalLembar = $daftarKelas->count();
    @endphp

    <!-- Toolbar layar -->
    <header class="no-print-toolbar">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <a href="{{ route('kelas.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali ke Kelola Kelas
            </a>
            <div>
                <div style="font-weight: 800; font-size: 0.9rem; color: #0f172a;">
                    <i class="bi bi-qr-code text-success"></i> Layout Cetak QR Code Kelas
                </div>
                <div style="font-size: 0.72rem; color: #64748b;">
                    A4 Portrait &middot; 1 QR ukuran penuh per lembar &middot; {{ $totalLembar }} kelas &middot; {{ $totalLembar }} lembar
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('qrcode.kelas') }}" class="btn btn-light">
                <i class="bi bi-arrow-clockwise"></i> Muat Ulang
            </a>
            <button type="button" class="btn btn-success" onclick="window.print()">
                <i class="bi bi-printer-fill"></i> Cetak Sekarang
            </button>
        </div>
    </header>

    @forelse($daftarKelas as $k)
        <div class="a4-sheet">
            <div class="sheet-header">
                <div class="brand">
                    <img src="{{ $logo2 }}" alt="Kemenag" onerror="this.src='{{ asset('images/logo.svg') }}'">
                    <img src="{{ $logo1 }}" alt="MAN 2" onerror="this.src='{{ asset('images/logo.svg') }}'">
                    <div>
                        <h1>QR CODE RUANG KELAS</h1>
                        <p>MAN 2 Bandar Lampung &middot; Presensi Digital Guru</p>
                    </div>
                </div>
                <div class="sheet-meta">
                    <div>Tahun Pelajaran {{ $k->tahun_pelajaran ?? '-' }}</div>
                </div>
            </div>

            <div class="qr-card-full">
                <div class="sekolah">MAN 2 Bandar Lampung</div>
                <div class="qr-box">
                    <div class="qr-kelas-render" data-qr-value="KELAS:{{ $k->id }}"></div>
                </div>
                <div class="kelas-nama">Kelas {{ $k->kelas }}</div>
                <div class="kelas-label">Scan untuk Presensi</div>
                <div class="kode">KELAS:{{ $k->id }}</div>
                <div class="petunjuk">
                    Tempelkan QR Code ini di pintu ruang kelas.<br>
                    Guru memindai lewat menu <strong>Absensi Mengajar</strong>.
                </div>
            </div>
        </div>
    @empty
        <div class="a4-sheet">
            <div class="empty-state">
                <i class="bi bi-qr-code" style="font-size: 3rem;"></i>
                <h2 style="font-size: 1rem; font-weight: 700; margin: 0.75rem 0 0.25rem;">Tidak ada data kelas</h2>
                <p style="font-size: 0.8rem; margin: 0;">Tambahkan data kelas terlebih dahulu pada menu Kelas.</p>
            </div>
        </div>
    @endforelse

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof QRCode === 'undefined') {
                console.warn('Pustaka QRCode tidak ditemukan.');
                return;
            }

            document.querySelectorAll('.qr-kelas-render').forEach(function (elem) {
                const qrValue = elem.getAttribute('data-qr-value');
                if (!qrValue) return;

                elem.innerHTML = '';
                new QRCode(elem, {
                    text: qrValue,
                    width: 256,
                    height: 256,
                    colorDark: '#064e3b',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });
            });
        });
    </script>
</body>
</html>
