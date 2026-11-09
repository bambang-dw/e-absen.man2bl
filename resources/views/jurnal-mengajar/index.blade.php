@extends('layouts.admin', [
    'title' => $isAdmin ? 'Rekapitulasi Absen Guru' : 'Jurnal Mengajar Guru',
    'pageTitle' => $isAdmin ? 'Rekapitulasi Absen Guru' : 'Jurnal Mengajar Guru',
    'pageSubtitle' => $isAdmin ? 'Rekapitulasi kehadiran guru MAN 2 Bandar Lampung' : 'Tabel Absensi & Jurnal Mengajar Guru MAN 2 Bandar Lampung',
])

@section('content')

    <div class="container-fluid px-0">

        @unless ($isAdmin)
            <div class="alert alert-info border-0 rounded-4 d-flex align-items-start gap-2 mb-4 shadow-sm">
                <i class="bi bi-info-circle-fill fs-5 mt-1"></i>
                <div class="small">
                    Halaman ini menampilkan <strong>catatan absensi dan jurnal mengajar milik Anda sendiri</strong>.
                    Jurnal guru lain hanya dapat dilihat oleh Administrator.
                </div>
            </div>
        @endunless

        <!-- Ringkasan Statistik -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card-custom p-3 h-100 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-primary bg-primary-subtle"
                        style="width: 48px; height: 48px;">
                        <i class="bi bi-journal-check fs-5"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Total Catatan</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($totalCatatan) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-custom p-3 h-100 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-success bg-success-subtle"
                        style="width: 48px; height: 48px;">
                        <i class="bi bi-book-half fs-5"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Materi Terisi</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($totalMateri) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-custom p-3 h-100 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 48px; height: 48px; background-color: #cffafe; color: #0891b2;">
                        <i class="bi bi-pencil-square fs-5"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Sesi dengan Tugas</div>
                        <div class="fs-4 fw-bold text-dark">{{ number_format($totalTugas) }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if ($isAdmin)
            @include('jurnal-mengajar.rekap')
        @endif

        <!-- Filter & Pencarian -->
        <div class="card-custom p-4 mb-4">
            <form action="{{ route('jurnal-mengajar.index') }}" method="GET" class="row g-3 align-items-end">
                @if ($isAdmin)
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label small fw-bold text-secondary mb-1">Pencarian</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control bg-light"
                                placeholder="Guru / mapel / materi..." value="{{ $search }}">
                            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </div>
                @endif

                <div class="col-lg-2 col-md-3 col-6">
                    <label class="form-label small fw-bold text-secondary mb-1">Dari Tanggal</label>
                    <input type="date" name="tanggal_mulai" class="form-control bg-light" value="{{ $tanggalMulai }}">
                </div>

                <div class="col-lg-2 col-md-3 col-6">
                    <label class="form-label small fw-bold text-secondary mb-1">Sampai Tanggal</label>
                    <input type="date" name="tanggal_selesai" class="form-control bg-light"
                        value="{{ $tanggalSelesai }}">
                </div>

                <div class="col-lg-2 col-md-3 col-6">
                    <label class="form-label small fw-bold text-secondary mb-1">Kelas</label>
                    <select name="kelas" class="form-select bg-light">
                        <option value="">-- Semua Kelas --</option>
                        @foreach ($daftarKelas as $k)
                            <option value="{{ $k->id }}"
                                {{ (string) $kelasFilter === (string) $k->id ? 'selected' : '' }}>
                                Kelas {{ $k->kelas }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-3 col-md-3 col-6">
                    <label class="form-label small fw-bold text-secondary mb-1">Status</label>
                    <div class="d-flex gap-2">
                        <select name="status" class="form-select bg-light">
                            <option value="">-- Semua Status --</option>
                            @foreach ($daftarStatus as $st)
                                <option value="{{ $st }}" {{ $statusFilter === $st ? 'selected' : '' }}>
                                    {{ $st }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary-gradient rounded-pill px-3 text-nowrap">
                            <i class="bi bi-funnel-fill me-1"></i> Filter
                        </button>
                        @if ($search || $tanggalMulai || $tanggalSelesai || $kelasFilter || $statusFilter)
                            <a href="{{ route('jurnal-mengajar.index') }}" class="btn btn-light border rounded-pill px-3"
                                title="Reset Filter">
                                <i class="bi bi-x-circle"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
        <!-- Tabel Absensi & Jurnal Mengajar Guru -->
        <div class="card-custom p-0 overflow-hidden mb-4">
            <div class="p-3 bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-person-check-fill text-success me-2"></i> Tabel Absensi & Jurnal Mengajar Guru
                    </h6>
                    <small
                        class="text-muted">{{ \Carbon\Carbon::parse($tanggalHariIni)->locale('id')->translatedFormat('l, d F Y') }}</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary rounded-pill px-3 py-2">
                        Total: {{ $jurnal->total() }} Catatan
                    </span>
                    @if ($isAdmin)
                        <button type="button" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm"
                            data-bs-toggle="modal" data-bs-target="#modalTambahAbsensiGuru">
                            <i class="bi bi-plus-circle me-1"></i> Catat Absensi Guru
                        </button>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr style="font-size: 0.78rem; text-transform: uppercase;">
                            <th class="ps-3" style="width: 60px;">{{ $isAdmin ? 'ID' : 'No' }}</th>
                            <th>Hari</th>
                            <th>Tanggal</th>
                            <th>Nama Guru</th>
                            <th>Kelas</th>
                            <th>Jam Ke</th>
                            <th>Mata Pelajaran</th>
                            <th>Materi Pembelajaran / Jurnal</th>
                            <th>Foto Kelas</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                            @if ($isAdmin)
                                <th class="text-end pe-3" style="width: 70px;">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jurnal as $ag)
                            <tr>
                                <td class="ps-3 fw-bold text-muted">
                                    {{ $isAdmin ? '#' . $ag->id : $jurnal->firstItem() + $loop->index }}</td>
                                <td><span class="fw-semibold">{{ $ag->hari }}</span></td>
                                <td class="text-nowrap">{{ $ag->tanggal ? $ag->tanggal->format('d/m/Y') : '-' }}</td>
                                <td class="fw-bold text-dark">{{ $ag->nama_guru_tampil }}</td>
                                <td>
                                    @if ($ag->dataKelas)
                                        <span
                                            class="badge bg-light text-dark border rounded-pill">{{ $ag->dataKelas->kelas }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-light text-dark border">Jam ke-{{ $ag->jam_ke }}</span></td>
                                <td class="fw-semibold text-primary">{{ $ag->mata_pelajaran }}</td>
                                <td style="max-width: 320px;">
                                    @if ($ag->materi_pembelajaran)
                                        <span class="d-block text-dark"
                                            title="{{ $ag->materi_pembelajaran }}">{{ \Illuminate\Support\Str::limit($ag->materi_pembelajaran, 90) }}</span>
                                    @else
                                        <span class="text-muted fst-italic">Belum diisi</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @if ($ag->foto)
                                        <a href="{{ $ag->foto_url }}" target="_blank" rel="noopener noreferrer">
                                            <i class="bi bi-image me-1"></i>{{ basename($ag->foto) }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($ag->hasTugas())
                                        <span class="badge rounded-pill px-2 py-1 fw-bold"
                                            style="background-color: #06b6d4; color: #ffffff;">
                                            <i class="bi bi-journal-check me-1"></i> {{ $ag->status }}
                                        </span>
                                    @elseif($ag->isHadir())
                                        <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1 fw-bold">
                                            <i class="bi bi-check-circle-fill me-1"></i> {{ $ag->status }}
                                        </span>
                                    @else
                                        @php
                                            $statusColor = match (strtolower((string) $ag->status)) {
                                                'izin', 'ijin' => 'info',
                                                'sakit' => 'warning',
                                                default => 'danger',
                                            };
                                        @endphp
                                        <span
                                            class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} rounded-pill px-2 py-1 fw-bold">
                                            {{ $ag->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $ag->keterangan ?: '-' }}</td>
                                @if ($isAdmin)
                                    <td class="text-end pe-3">
                                        <form action="{{ route('jadwal.absensi-guru.destroy', $ag->id) }}" method="POST"
                                            class="d-inline" onsubmit="return confirm('Hapus data absensi guru ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="btn btn-sm btn-light border text-danger rounded-3" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isAdmin ? 12 : 11 }}" class="text-center py-5 text-muted">
                                    <i class="bi bi-clipboard2-x fs-1 d-block mb-2"></i>
                                    Belum ada catatan absensi dan jurnal mengajar yang cocok dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($jurnal->hasPages())
                <div class="p-3 border-top bg-light">
                    {{ $jurnal->links() }}
                </div>
            @endif
        </div>
    </div>

    @if ($isAdmin)
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const periodeSelect = document.getElementById('periode-rekap-guru');
                    const filterFields = document.querySelectorAll('[data-rekap-filter]');
                    const tahunLabel = document.querySelector('[data-tahun-label]');
                    const chartCanvas = document.getElementById('rekapAbsensiGuruChart');

                    const updateFilterFields = function() {
                        const periode = periodeSelect.value;

                        filterFields.forEach(function(field) {
                            const isVisible = field.dataset.rekapFilter === periode ||
                                field.dataset.rekapFilter === 'tahun';
                            field.hidden = !isVisible;
                            field.querySelectorAll('input, select').forEach(function(control) {
                                control.disabled = !isVisible;
                            });
                        });

                        if (tahunLabel) {
                            tahunLabel.textContent = periode === 'semester' ? 'Tahun Ajaran Mulai' : 'Tahun';
                        }
                    };

                    periodeSelect.addEventListener('change', updateFilterFields);
                    updateFilterFields();

                    if (chartCanvas && window.Chart) {
                        new Chart(chartCanvas, {
                            type: 'bar',
                            data: {
                                labels: ['Hadir', 'Izin dengan Tugas', 'Sakit dengan Tugas', 'Cuti', 'Tidak Hadir'],
                                datasets: [{
                                    label: 'Jumlah Sesi',
                                    data: {{ \Illuminate\Support\Js::from(array_values($totalKategoriRekap)) }},
                                    backgroundColor: ['#10b981', '#0ea5e9', '#f59e0b', '#8b5cf6', '#ef4444'],
                                    borderRadius: 6,
                                    maxBarThickness: 56,
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0
                                        },
                                    },
                                },
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                },
                            },
                        });
                    }
                });
            </script>
        @endpush
    @endif

    <!-- Modal Tambah Absensi Guru (absensi_gurus) -->
    <div class="modal fade" id="modalTambahAbsensiGuru" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi bi-person-check-fill text-success me-2"></i> Catat Absensi & Jurnal Mengajar
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('jadwal.absensi-guru.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Kelas</label>
                                <select name="kelas" class="form-select">
                                    <option value="">-- Pilih Kelas --</option>
                                    @foreach ($daftarKelas as $k)
                                        <option value="{{ $k->id }}"
                                            {{ (string) $kelasFilter === (string) $k->id ? 'selected' : '' }}>
                                            Kelas {{ $k->kelas }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Hari <span class="text-danger">*</span></label>
                                <select name="hari" class="form-select" required>
                                    @foreach ($daftarHari as $h)
                                        <option value="{{ $h }}" {{ $hariIni === $h ? 'selected' : '' }}>
                                            {{ $h }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal" class="form-control" value="{{ $tanggalHariIni }}"
                                    required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Guru <span class="text-danger">*</span></label>
                            <select name="nama_guru" class="form-select" required>
                                <option value="">-- Pilih Guru --</option>
                                @foreach ($daftarPengajar as $u)
                                    <option value="{{ $u->name ?: $u->username }}">
                                        {{ $u->name ?: $u->username }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-4">
                                <label class="form-label small fw-bold">Jam Ke <span class="text-danger">*</span></label>
                                <input type="text" name="jam_ke" class="form-control" placeholder="Contoh: 1,2,3"
                                    required>
                            </div>
                            <div class="col-8">
                                <label class="form-label small fw-bold">Mata Pelajaran <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="mata_pelajaran" class="form-control"
                                    placeholder="Contoh: Matematika" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Materi Pembelajaran / Jurnal</label>
                            <textarea name="materi_pembelajaran" class="form-control" rows="2"
                                placeholder="Pokok bahasan / instruksi tugas hari ini..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold d-block">
                                Status Kehadiran Guru <span class="text-danger">*</span>
                                <span class="badge rounded-pill ms-1"
                                    style="background-color: #06b6d4; color: #fff; font-size: 0.7rem;">Bisa pilih lebih
                                    dari 1 (Misal: Izin + Tugas / Sakit + Tugas)</span>
                            </label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($daftarStatus as $st)
                                    <label class="m-0">
                                        <input type="checkbox" name="status[]" value="{{ $st }}"
                                            class="btn-check" {{ $st === 'Hadir' ? 'checked' : '' }}>
                                        <span
                                            class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-semibold">{{ $st }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.73rem;">
                                Jika memilih <strong>Tugas</strong>, indikator pada <strong>Dashboard Mengajar</strong> akan
                                tampil berwarna <strong style="color: #0891b2;">Cyan</strong>.
                            </small>
                        </div>

                        <div class="mb-1">
                            <label class="form-label small fw-bold">Keterangan</label>
                            <input type="text" name="keterangan" class="form-control"
                                placeholder="Keterangan tambahan (opsional)">
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light py-3">
                        <button type="button" class="btn btn-light border rounded-pill px-4"
                            data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4">Simpan Absensi Guru</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
