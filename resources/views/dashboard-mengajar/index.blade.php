@extends('layouts.admin', [
'title' => 'Dashboard Mengajar',
'pageTitle' => 'Dashboard Mengajar',
'pageSubtitle' => 'MAN 2 Bandar Lampung - Monitoring Kehadiran Mengajar Guru per Level Kelas Secara Real-Time'
])

@section('content')
<div class="container-fluid px-0">

    <!-- Statistik Ringkas Kehadiran Mengajar Guru -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card-custom p-3 d-flex align-items-center gap-3 border-start border-4 border-primary">
                <div class="rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-calendar2-week-fill fs-4"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Total Jadwal ({{ $hariFilter }})</small>
                    <h4 class="fw-bold text-dark mb-0" id="statTotalJadwalSlot">{{ $totalJadwalSlot }}</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card-custom p-3 d-flex align-items-center gap-3 border-start border-4 border-success">
                <div class="rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-check-circle-fill fs-4"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Hadir</small>
                    <h4 class="fw-bold text-success mb-0" id="statTotalHadirSlot">{{ $totalHadirSlot }}</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card-custom p-3 d-flex align-items-center gap-3 border-start border-4 border-info">
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: #cffafe; color: #0891b2;">
                    <i class="bi bi-journal-check fs-4"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Ada Tugas</small>
                    <h4 class="fw-bold mb-0" id="statTotalTugasSlot" style="color: #0891b2;">{{ $totalTugasSlot ?? 0 }}</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card-custom p-3 d-flex align-items-center gap-3 border-start border-4 border-danger">
                <div class="rounded-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-x-circle-fill fs-4"></i>
                </div>
                <div>
                    <small class="text-muted d-block">Belum Hadir</small>
                    <h4 class="fw-bold text-danger mb-0" id="statTotalBelumHadirSlot">{{ $totalBelumHadirSlot }}</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar & Legenda Indikator -->
    <div class="card-custom p-4 mb-4">
        <form action="{{ route('dashboard.mengajar') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-3 col-sm-6">
                <label class="form-label small fw-bold text-secondary mb-1">Tanggal Pemantauan</label>
                <input type="date" name="tanggal" class="form-control bg-light fw-semibold" value="{{ $tanggal }}" onchange="this.form.submit()">
            </div>

            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-bold text-secondary mb-1">Hari Jadwal</label>
                <select name="hari" class="form-select bg-light fw-semibold" onchange="this.form.submit()">
                    @foreach($daftarHari as $h)
                    <option value="{{ $h }}" {{ $hariFilter === $h ? 'selected' : '' }}>{{ $h }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-bold text-secondary mb-1">Level Kelas</label>
                <select name="level" class="form-select bg-light fw-semibold" onchange="this.form.submit()">
                    <option value="">-- Semua Level --</option>
                    <option value="X" {{ $levelFilter === 'X' ? 'selected' : '' }}>Level Kelas X</option>
                    <option value="XI" {{ $levelFilter === 'XI' ? 'selected' : '' }}>Level Kelas XI</option>
                    <option value="XII" {{ $levelFilter === 'XII' ? 'selected' : '' }}>Level Kelas XII</option>
                </select>
            </div>

            <div class="col-md-5 col-12 d-flex flex-wrap gap-2 justify-content-md-end align-items-center">
                <div class="d-flex flex-wrap align-items-center gap-3 me-2 small">
                    <span class="d-inline-flex align-items-center gap-1 fw-semibold text-success">
                        <span class="rounded-circle bg-success d-inline-block" style="width: 12px; height: 12px;"></span>
                        Hijau: Hadir
                    </span>
                    <span class="d-inline-flex align-items-center gap-1 fw-semibold" style="color: #0891b2;">
                        <span class="rounded-circle d-inline-block" style="width: 12px; height: 12px; background-color: #06b6d4;"></span>
                        Cyan: Ada Tugas
                    </span>
                    <span class="d-inline-flex align-items-center gap-1 fw-semibold text-danger">
                        <span class="rounded-circle bg-danger d-inline-block" style="width: 12px; height: 12px;"></span>
                        Merah: Belum Hadir
                    </span>
                    <!-- <span class="d-inline-flex align-items-center gap-1 fw-semibold" style="color: #b45309;">
                        <span class="rounded-circle d-inline-block" style="width: 12px; height: 12px; background-color: #f59e0b;"></span>
                        Oranye: Aktif (Tidak Dipantau)
                    </span> -->
                    <span class="d-inline-flex align-items-center gap-1 fw-semibold" style="color: #64748b;">
                        <span class="rounded-circle d-inline-block" style="width: 12px; height: 12px; background-color: #94a3b8;"></span>
                        Abu-abu: Kegiatan (Tanpa Guru)
                    </span>
                </div>

                <!-- Cetak QR Code Kelas dipindahkan ke halaman Kelola Kelas -->
                <!-- <a href="{{ route('jadwal.index') }}" class="btn btn-sm btn-primary-gradient rounded-pill px-3 py-2 fw-semibold">
                    <i class="bi bi-gear-fill me-1"></i> Atur Jadwal
                </a> -->
            </div>
        </form>
    </div>

    <!-- Jadwal Mengajar Per Level (Level Kelas X, Level Kelas XI, Level Kelas XII) -->
    @forelse($matrixPerLevel as $levelKey => $levelData)
    <div class="card-custom p-0 overflow-hidden mb-4">
        <div class="p-3 bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-pill px-3 py-2 fs-6">
                    <i class="bi bi-layers-fill me-1"></i> {{ $levelData['label'] }}
                </span>
                <span class="text-muted small">
                    Hari <strong>{{ $hariFilter }}</strong> • {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}
                </span>
            </div>
            <span class="badge bg-white text-dark border rounded-pill px-3 py-1">
                {{ $levelData['kelasList']->count() }} Kelas
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0" style="font-size: 0.84rem;">
                <thead class="table-light text-center">
                    <tr style="font-size: 0.78rem; text-transform: uppercase;">
                        <th style="width: 90px; min-width: 85px;">Jam Ke</th>
                        <th style="width: 105px; min-width: 100px;">Waktu Mulai</th>
                        <th style="width: 105px; min-width: 100px;">Waktu Selesai</th>
                        @foreach($levelData['kelasList'] as $k)
                        <th style="min-width: 190px;">
                            <div class="fw-bold text-dark">Kelas {{ $k->kelas }}</div>
                            <small class="text-muted fw-normal" style="font-size: 0.68rem;">TP {{ $k->tahun_pelajaran }}</small>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($levelData['rows'] as $row)
                    <tr>
                        <td class="text-center fw-bold bg-light">
                            <span class="badge bg-indigo-subtle text-primary border px-2.5 py-1 rounded-pill" style="background-color: #e0e7ff; color: #4338ca;">
                                Jam ke-{{ $row['jam_ke'] }}
                            </span>
                        </td>
                        <td class="text-center fw-semibold text-dark">
                            {{ $row['waktu_mulai'] }} WIB
                        </td>
                        <td class="text-center fw-semibold text-dark">
                            {{ $row['waktu_selesai'] }} WIB
                        </td>

                        @foreach($levelData['kelasList'] as $k)
                        @php
                        $cell = $row['cells'][$k->id] ?? null;
                        $cellDomId = 'cell_kelas_'.$k->id.'_jam_'.preg_replace('/[^a-zA-Z0-9]/', '_', (string) $row['jam_ke']);
                        @endphp
                        <td id="{{ $cellDomId }}" class="p-2 align-top">
                            @if($cell && $cell['scheduled'])
                            @php
                            $isTugas = !empty($cell['is_tugas']) || ($cell['indicator'] ?? '') === 'cyan';
                            $isHadir = !$isTugas && (bool) $cell['is_hadir'];
                            $isGuruKosong = !empty($cell['is_guru_kosong']) || ($cell['indicator'] ?? '') === 'orange';
                            $isMute = !empty($cell['is_mute']) || ($cell['indicator'] ?? '') === 'mute';
                            $statusLabel = $cell['status_label'] ?? ($isHadir ? 'Hadir' : 'Belum Hadir');

                            if ($isTugas) {
                            $cardClass = 'status-tugas-cyan';
                            $bgStyle = 'background-color: #ecfeff; border: 1.5px solid #06b6d4;';
                            $borderSubtle = 'border-info-subtle';
                            $dotColor = '#06b6d4';
                            } elseif ($isHadir) {
                            $cardClass = 'status-hadir-hijau';
                            $bgStyle = 'background-color: #ecfdf5; border: 1.5px solid #10b981;';
                            $borderSubtle = 'border-success-subtle';
                            $dotColor = '#10b981';
                            } elseif ($isMute) {
                            $cardClass = 'status-kegiatan-mute';
                            $bgStyle = 'background-color: #f8fafc; border: 1.5px solid #cbd5e1;';
                            $borderSubtle = 'border-secondary-subtle';
                            $dotColor = '#94a3b8';
                            } elseif ($isGuruKosong) {
                            $cardClass = 'status-guru-kosong-orange';
                            $bgStyle = 'background-color: #fffbeb; border: 1.5px dashed #f59e0b;';
                            $borderSubtle = 'border-warning-subtle';
                            $dotColor = '#f59e0b';
                            } else {
                            $cardClass = 'status-belum-merah';
                            $bgStyle = 'background-color: #fef2f2; border: 1.5px solid #ef4444;';
                            $borderSubtle = 'border-danger-subtle';
                            $dotColor = '#ef4444';
                            }
                            @endphp
                            <div class="rounded-3 p-2 h-100 shadow-xs jadwal-cell-card {{ $cardClass }}" style="{{ $bgStyle }}">
                                <!-- Baris Pertama: Kode Mapel & Tanda Indikator di Baris Atas (Tanpa Tulisan Hadir) -->
                                <div class="d-flex justify-content-between align-items-center gap-1 pb-1 mb-1 border-bottom {{ $borderSubtle }}">
                                    <div class="fw-bold text-dark text-truncate" style="font-size: 0.72rem; line-height: 1.2;" title="{{ $cell['mata_pelajaran'] }}">
                                        <i class="bi bi-journal-text me-1" style="color: {{ $dotColor }}; font-size: 0.72rem;"></i>
                                        <span class="cell-mapel-text">{{ $cell['mata_pelajaran'] }}</span>
                                    </div>
                                    @if($isTugas)
                                    <span class="badge rounded-pill px-1.5 py-0.5 flex-shrink-0 cell-indicator-badge" style="background-color: #06b6d4; color: #ffffff; font-size: 0.62rem;" title="Tugas: {{ $statusLabel }}">
                                        <i class="bi bi-journal-check me-0.5"></i> {{ $statusLabel }}
                                    </span>
                                    @elseif($isHadir)
                                    <span class="rounded-circle flex-shrink-0 cell-indicator-dot"
                                        style="width: 9px; height: 9px; display: inline-block; background-color: #10b981; box-shadow: 0 0 6px #10b981;"
                                        title="Hadir"></span>
                                    @elseif($isMute)
                                    <span class="rounded-circle flex-shrink-0 cell-indicator-dot"
                                        style="width: 9px; height: 9px; display: inline-block; background-color: #94a3b8;"
                                        title="Kegiatan, tanpa guru pengajar"></span>
                                    @elseif($isGuruKosong)
                                    <span class="rounded-circle flex-shrink-0 cell-indicator-dot"
                                        style="width: 9px; height: 9px; display: inline-block; background-color: #f59e0b; box-shadow: 0 0 6px #f59e0b;"
                                        title="Aktif, tidak dihitung dalam jadwal yang dipantau"></span>
                                    @else
                                    <span class="rounded-circle flex-shrink-0 cell-indicator-dot"
                                        style="width: 9px; height: 9px; display: inline-block; background-color: #ef4444; box-shadow: 0 0 6px #ef4444;"
                                        title="{{ $statusLabel }}"></span>
                                    @endif
                                </div>

                                <!-- Baris Kedua: Nama Guru Pengajar (Font Diperkecil) -->
                                <div class="d-flex align-items-center gap-1 pt-0.5">
                                    <div class="fw-semibold text-truncate" style="font-size: 0.68rem; line-height: 1.2; color: {{ $isTugas ? '#0e7490' : ($isHadir ? '#065f46' : ($isMute ? '#64748b' : ($isGuruKosong ? '#92400e' : '#991b1b'))) }};" title="{{ $cell['nama_pengajar'] }}">
                                        <i class="bi bi-person-badge me-1" style="font-size: 0.68rem;"></i>
                                        <span class="cell-guru-text">{{ $cell['nama_pengajar'] }}</span>
                                    </div>
                                </div>
                                @if (!empty($cell['foto_url']))
                                    <a class="d-block text-truncate mt-1" href="{{ $cell['foto_url'] }}" target="_blank"
                                        rel="noopener noreferrer" title="{{ $cell['foto_nama'] }}">
                                        <i class="bi bi-image me-1"></i>{{ $cell['foto_nama'] }}
                                    </a>
                                @endif
                            </div>
                            @else
                            <div class="text-center text-muted py-3 small" style="opacity: 0.55;">
                                -
                            </div>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ 3 + $levelData['kelasList']->count() }}" class="text-center py-4 text-muted">
                            Belum ada data Jam Belajar (`jam_belajars`) atau Jadwal Pelajaran (`jadwals`). Silakan tambahkan di menu <strong>Kelola Waktu Belajar</strong> dan <strong>Kelola Jadwal Pelajaran</strong>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @empty
    <div class="card-custom p-5 text-center text-muted">
        <i class="bi bi-easel2 fs-1 d-block mb-2"></i>
        <h6 class="fw-bold text-dark">Belum Ada Data Kelas Terdaftar</h6>
        <p class="small mb-3">Tambahkan data kelas (misal X A, X B, XI A, XII A) terlebih dahulu pada menu <strong>Kelola Kelas</strong>.</p>
        <a href="{{ route('kelas.index') }}" class="btn btn-primary rounded-pill px-4">Buka Kelola Kelas</a>
    </div>
    @endforelse

</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let isPolling = false;

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function renderCellHtml(cell) {
            if (!cell || !cell.scheduled) {
                return '<div class="text-center text-muted py-3 small" style="opacity: 0.55;">-</div>';
            }

            const isTugas = Boolean(cell.is_tugas) || cell.indicator === 'cyan';
            const isHadir = !isTugas && Boolean(cell.is_hadir);
            const isGuruKosong = Boolean(cell.is_guru_kosong) || cell.indicator === 'orange';
            const isMute = Boolean(cell.is_mute) || cell.indicator === 'mute';
            const statusLabel = escapeHtml(cell.status_label || (isHadir ? 'Hadir' : 'Belum Hadir'));
            const photoLink = cell.foto_url ?
                '<a class="d-block text-truncate mt-1" href="' + escapeHtml(cell.foto_url) +
                '" target="_blank" rel="noopener noreferrer" title="' + escapeHtml(cell.foto_nama) +
                '"><i class="bi bi-image me-1"></i>' + escapeHtml(cell.foto_nama) + '</a>' :
                '';

            let cardClass = 'status-belum-merah';
            let bgStyle = 'background-color: #fef2f2; border: 1.5px solid #ef4444;';
            let borderSubtle = 'border-danger-subtle';
            let dotColor = '#ef4444';
            let textColor = '#991b1b';
            let indicatorTopHtml = '<span class="rounded-circle flex-shrink-0 cell-indicator-dot" style="width: 9px; height: 9px; display: inline-block; background-color: #ef4444; box-shadow: 0 0 6px #ef4444;" title="' + statusLabel + '"></span>';

            if (isTugas) {
                cardClass = 'status-tugas-cyan';
                bgStyle = 'background-color: #ecfeff; border: 1.5px solid #06b6d4;';
                borderSubtle = 'border-info-subtle';
                dotColor = '#06b6d4';
                textColor = '#0e7490';
                indicatorTopHtml = '<span class="badge rounded-pill px-1.5 py-0.5 flex-shrink-0 cell-indicator-badge" style="background-color: #06b6d4; color: #ffffff; font-size: 0.62rem;" title="Tugas: ' + statusLabel + '"><i class="bi bi-journal-check me-0.5"></i> ' + statusLabel + '</span>';
            } else if (isHadir) {
                cardClass = 'status-hadir-hijau';
                bgStyle = 'background-color: #ecfdf5; border: 1.5px solid #10b981;';
                borderSubtle = 'border-success-subtle';
                dotColor = '#10b981';
                textColor = '#065f46';
                indicatorTopHtml = '<span class="rounded-circle flex-shrink-0 cell-indicator-dot" style="width: 9px; height: 9px; display: inline-block; background-color: #10b981; box-shadow: 0 0 6px #10b981;" title="Hadir"></span>';
            } else if (isMute) {
                cardClass = 'status-kegiatan-mute';
                bgStyle = 'background-color: #f8fafc; border: 1.5px solid #cbd5e1;';
                borderSubtle = 'border-secondary-subtle';
                dotColor = '#94a3b8';
                textColor = '#64748b';
                indicatorTopHtml = '<span class="rounded-circle flex-shrink-0 cell-indicator-dot" style="width: 9px; height: 9px; display: inline-block; background-color: #94a3b8;" title="Kegiatan, tanpa guru pengajar"></span>';
            } else if (isGuruKosong) {
                cardClass = 'status-guru-kosong-orange';
                bgStyle = 'background-color: #fffbeb; border: 1.5px dashed #f59e0b;';
                borderSubtle = 'border-warning-subtle';
                dotColor = '#f59e0b';
                textColor = '#92400e';
                indicatorTopHtml = '<span class="rounded-circle flex-shrink-0 cell-indicator-dot" style="width: 9px; height: 9px; display: inline-block; background-color: #f59e0b; box-shadow: 0 0 6px #f59e0b;" title="Aktif, tidak dihitung dalam jadwal yang dipantau"></span>';
            }

            return (
                '<div class="rounded-3 p-2 h-100 shadow-xs jadwal-cell-card ' + cardClass + '" style="' + bgStyle + '">' +
                '<div class="d-flex justify-content-between align-items-center gap-1 pb-1 mb-1 border-bottom ' + borderSubtle + '">' +
                '<div class="fw-bold text-dark text-truncate" style="font-size: 0.72rem; line-height: 1.2;" title="' + escapeHtml(cell.mata_pelajaran) + '">' +
                '<i class="bi bi-journal-text me-1" style="color: ' + dotColor + '; font-size: 0.72rem;"></i> ' +
                '<span class="cell-mapel-text">' + escapeHtml(cell.mata_pelajaran) + '</span>' +
                '</div>' +
                indicatorTopHtml +
                '</div>' +
                '<div class="d-flex align-items-center gap-1 pt-0.5">' +
                '<div class="fw-semibold text-truncate" style="font-size: 0.68rem; line-height: 1.2; color: ' + textColor + ';" title="' + escapeHtml(cell.nama_pengajar) + '">' +
                '<i class="bi bi-person-badge me-1" style="font-size: 0.68rem;"></i> ' +
                '<span class="cell-guru-text">' + escapeHtml(cell.nama_pengajar) + '</span>' +
                '</div>' +
                '</div>' +
                photoLink +
                '</div>'
            );
        }

        function fetchLiveDashboardMengajar() {
            if (isPolling || document.hidden) return;
            isPolling = true;

            const url = new URL(window.location.href);
            url.searchParams.set('live', '1');
            url.searchParams.set('_t', String(Date.now()));

            fetch(url.toString(), {
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
                    const elTotal = document.getElementById('statTotalJadwalSlot');
                    const elHadir = document.getElementById('statTotalHadirSlot');
                    const elTugas = document.getElementById('statTotalTugasSlot');
                    const elBelum = document.getElementById('statTotalBelumHadirSlot');

                    if (elTotal) elTotal.textContent = String(data.totalJadwalSlot ?? 0);
                    if (elHadir) elHadir.textContent = String(data.totalHadirSlot ?? 0);
                    if (elTugas) elTugas.textContent = String(data.totalTugasSlot ?? 0);
                    if (elBelum) elBelum.textContent = String(data.totalBelumHadirSlot ?? 0);

                    if (data.matrixPerLevel) {
                        Object.keys(data.matrixPerLevel).forEach(function(lvlKey) {
                            const lvl = data.matrixPerLevel[lvlKey];
                            if (!lvl || !Array.isArray(lvl.rows)) return;

                            lvl.rows.forEach(function(row) {
                                const safeJam = String(row.jam_ke).replace(/[^a-zA-Z0-9]/g, '_');
                                if (!row.cells) return;

                                Object.keys(row.cells).forEach(function(kelasId) {
                                    const cellData = row.cells[kelasId];
                                    const domId = 'cell_kelas_' + kelasId + '_jam_' + safeJam;
                                    const td = document.getElementById(domId);
                                    if (td) {
                                        td.innerHTML = renderCellHtml(cellData);
                                    }
                                });
                            });
                        });
                    }
                })
                .catch(function() {})
                .finally(function() {
                    isPolling = false;
                });
        }

        setInterval(fetchLiveDashboardMengajar, 3000);

        if ('BroadcastChannel' in window) {
            const ch = new BroadcastChannel('e_absen_live_channel');
            ch.onmessage = function() {
                fetchLiveDashboardMengajar();
            };
        }
        window.addEventListener('storage', function(e) {
            if (e.key === 'e_absen_last_transaction') {
                fetchLiveDashboardMengajar();
            }
        });
    });
</script>
@endpush