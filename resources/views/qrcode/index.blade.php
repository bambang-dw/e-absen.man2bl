@extends('layouts.admin', [
    'title' => 'Generate Kartu Absen',
    'pageTitle' => 'Generate Kartu Absen Siswa',
    'pageSubtitle' => 'Cetak kartu presensi digital siswa portrait ukuran A4 (QR Code = NISN)'
])

@section('content')
<div class="container-fluid px-0">

    <!-- Top Action & Filter Card -->
    <div class="card-custom p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-lg-6">
                <form action="{{ route('qrcode.index') }}" method="GET" class="d-flex flex-wrap gap-2">
                    <div style="min-width: 200px;">
                        <select name="kelas" class="form-select bg-light" onchange="this.form.submit()">
                            <option value="">-- Semua Kelas ({{ $totalSiswa }} Siswa) --</option>
                            @foreach($daftarKelas as $k)
                                <option value="{{ $k }}" {{ request('kelas') == $k ? 'selected' : '' }}>Kelas {{ $k }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="input-group" style="max-width: 280px;">
                        <input type="text" name="search" class="form-control bg-light" placeholder="Cari nama / NISN..." value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                    @if(request('kelas') || request('search'))
                        <a href="{{ route('qrcode.index') }}" class="btn btn-light border"><i class="bi bi-x-circle"></i></a>
                    @endif
                </form>
            </div>

            <div class="col-lg-6 text-lg-end">
                <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                    <a href="{{ route('qrcode.print-all', ['kelas' => request('kelas')]) }}" target="_blank" class="btn btn-success rounded-pill px-4 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-printer-fill me-2"></i> Cetak Kartu Massal (A4 Sheet)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Cards Grid -->
    <div class="row g-4 mb-4">
        @forelse($siswas as $s)
            @php
                $qrValue = $s->nisn ?: $s->nis;
            @endphp
            <div class="col-sm-6 col-lg-4 col-xl-3">
                <div class="card-custom h-100 overflow-hidden border shadow-sm text-center position-relative id-card-box" id="card-siswa-{{ $s->id }}">
                    <!-- Card Header / Lanyard slot banner -->
                    <div class="p-3 text-white" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="badge bg-indigo-500 rounded-pill px-2 py-1 text-white" style="background-color: #6366f1; font-size: 0.65rem;">MAN 2 BANDAR LAMPUNG</span>
                            <small class="text-white-50" style="font-size: 0.68rem;">KARTU PELAJAR</small>
                        </div>
                        <h6 class="fw-bold mb-0 text-white" style="font-size: 0.88rem; letter-spacing: -0.01em;">PRESENSI DIGITAL</h6>
                    </div>

                    <!-- Card Body -->
                    <div class="p-3 bg-white">
                        <!-- Student Photo -->
                        <div class="mb-3 position-relative d-inline-block">
                            <img src="{{ $s->foto_url }}" alt="{{ $s->nama }}" class="rounded-circle border border-3 border-light shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                            <span class="position-absolute bottom-0 end-0 badge bg-primary rounded-pill border border-white" style="font-size: 0.68rem;">
                                {{ $s->kelas }}
                            </span>
                        </div>

                        <!-- Name & Student Info -->
                        <h6 class="fw-bold text-dark mb-1 text-truncate" title="{{ $s->nama }}">{{ $s->nama }}</h6>
                        <div class="small text-muted mb-3">
                            <span>NIS: <strong>{{ $s->nis }}</strong></span> | 
                            <span>NISN: <strong class="text-primary">{{ $s->nisn ?: '-' }}</strong></span>
                        </div>

                        <!-- QR Code Container -->
                        <div class="p-2 rounded-3 border bg-light d-inline-flex justify-content-center align-items-center mb-2 shadow-sm position-relative">
                            <div id="qrcode-{{ $s->id }}" class="qrcode-render" data-qr-value="{{ $qrValue }}"></div>
                        </div>

                        <div class="small text-muted mb-3">
                            <span class="badge bg-light text-primary border" style="font-size: 0.7rem;">
                                <i class="bi bi-qr-code me-1"></i> QR Code = NISN ({{ $qrValue }})
                            </span>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="d-flex justify-content-center gap-2 pt-2 border-top">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="downloadQRCode('{{ $s->id }}', '{{ $s->nisn ?: $s->nis }}', '{{ $s->nama }}')">
                                <i class="bi bi-download me-1"></i> QR PNG
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="printSingleCard('{{ $s->id }}')">
                                <i class="bi bi-printer me-1"></i> Cetak
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card-custom p-5 text-center text-muted">
                    <i class="bi bi-qr-code fs-1 d-block mb-3 text-secondary"></i>
                    <h5>Tidak ada siswa ditemukan</h5>
                    <p class="small mb-0">Coba ubah kata kunci pencarian atau filter kelas.</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($siswas->hasPages())
        <div class="p-3 bg-white card-custom d-flex justify-content-between align-items-center mb-4">
            <small class="text-muted">Menampilkan {{ $siswas->firstItem() }} - {{ $siswas->lastItem() }} dari {{ $siswas->total() }} kartu</small>
            <div>{{ $siswas->links() }}</div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Render QR Codes for each student card
        document.querySelectorAll('.qrcode-render').forEach(function (elem) {
            const qrValue = elem.getAttribute('data-qr-value');
            if (qrValue) {
                new QRCode(elem, {
                    text: qrValue,
                    width: 120,
                    height: 120,
                    colorDark: "#0f172a",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
        });
    });

    // Function to download rendered QR Code canvas as PNG image
    function downloadQRCode(studentId, nisn, studentName) {
        const container = document.getElementById('qrcode-' + studentId);
        const canvas = container.querySelector('canvas');
        const img = container.querySelector('img');

        let dataUrl = '';
        if (canvas) {
            dataUrl = canvas.toDataURL('image/png');
        } else if (img) {
            dataUrl = img.src;
        }

        if (dataUrl) {
            const link = document.createElement('a');
            link.download = `QRCode_NISN_${nisn}_${studentName.replace(/[^a-zA-Z0-9]/g, '_')}.png`;
            link.href = dataUrl;
            link.click();
        } else {
            alert('QR Code belum selesai digenerate.');
        }
    }

    // Function to print single student card
    function printSingleCard(studentId) {
        const cardElem = document.getElementById('card-siswa-' + studentId);
        if (!cardElem) return;

        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            alert('Pop-up pencetakan diblokir oleh browser. Izinkan pop-up untuk mencetak kartu.');
            return;
        }

        const cardContent = cardElem.innerHTML;
        const htmlParts = [
            '<!DOCTYPE html>',
            '<html>',
            '<head>',
            '<title>Cetak Kartu Siswa - e-absen MAN 2 Balam</title>',
            '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">',
            '<style>',
            'body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #fff; margin: 0; padding: 20px; }',
            '.card-print { width: 53.98mm; height: 85.60mm; border: 0.8px dashed #94a3b8; border-radius: 3.18mm; overflow: hidden; text-align: center; box-sizing: border-box; }',
            '.card-header-print { background: #1e1b4b; color: white; padding: 8px; }',
            '.card-body-print { padding: 8px; }',
            '@media print { body { padding: 0; } @page { size: 54mm 86mm; margin: 0; } }',
            '</style>',
            '</head>',
            '<body>',
            '<div class="card-print">' + cardContent + '</div>',
            '</body>',
            '</html>'
        ];

        printWindow.document.open();
        printWindow.document.write(htmlParts.join('\n'));
        printWindow.document.close();
        printWindow.focus();
        setTimeout(function() {
            printWindow.print();
            printWindow.close();
        }, 350);
    }
</script>
@endpush
