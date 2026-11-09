@extends('layouts.admin', [
    'title' => 'Pengaturan Hari Libur',
    'pageTitle' => 'Pengaturan Hari Libur & Kalender Belajar',
    'pageSubtitle' => 'MAN 2 Bandar Lampung - Kebijakan 5 Hari Belajar (Sabtu Libur) & Manajemen Hari Libur Khusus'
])

@section('content')
<div class="container-fluid px-0">

    <!-- Flash Notification Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                <div><strong>Berhasil!</strong> {{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-octagon-fill fs-5 me-2"></i>
                <div><strong>Perhatian!</strong> {{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                <div>
                    <strong>Terdapat Kesalahan:</strong>
                    <ul class="mb-0 ps-3 mt-1 small">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Information Banner: 5-Day School Policy -->
    <div class="card-custom p-4 mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white;">
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge bg-primary px-3 py-1.5 rounded-pill fw-bold" style="font-size: 0.8rem;">
                        <i class="bi bi-calendar-week me-1"></i> Kebijakan 5 Hari Sekolah
                    </span>
                    @if($statusHariIni['is_libur'])
                        <span class="badge bg-danger text-white px-3 py-1.5 rounded-pill fw-bold" style="font-size: 0.8rem;">
                            <i class="bi bi-calendar-x me-1"></i> Hari Ini Libur: {{ $statusHariIni['keterangan'] }}
                        </span>
                    @else
                        <span class="badge bg-success text-white px-3 py-1.5 rounded-pill fw-bold" style="font-size: 0.8rem;">
                            <i class="bi bi-check-circle me-1"></i> Hari Ini: Hari Belajar Efektif
                        </span>
                    @endif
                </div>
                <h4 class="fw-bold text-white mb-2">Sistem 5 Hari Belajar (Senin s.d. Jumat)</h4>
                <p class="text-white-50 mb-0" style="font-size: 0.92rem; line-height: 1.6;">
                    Sesuai ketentuan, kegiatan belajar mengajar berlangsung 5 hari kerja. <strong>Hari Sabtu dan Minggu secara otomatis ditetapkan sebagai Hari Libur</strong> (tidak belajar). Sesi absensi dan scanner akan dinonaktifkan pada akhir pekan. Gunakan menu di bawah untuk menambahkan hari libur nasional atau libur khusus sekolah.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <button type="button" class="btn btn-primary-gradient px-4 py-2.5 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahLibur">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Hari Libur
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content: Holiday List Table -->
    <div class="card-custom p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h5 class="fw-bold text-dark mb-1">
                    <i class="bi bi-calendar-event text-primary me-2"></i>Daftar Hari Libur Khusus & Nasional
                </h5>
                <p class="text-muted small mb-0">Kelola tanggal merah dan hari libur sekolah di luar jadwal akhir pekan</p>
            </div>
            <div class="d-flex gap-2">
                <form action="{{ route('hari-libur.index') }}" method="GET" class="d-flex gap-2">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Cari tanggal/keterangan..." value="{{ $search ?? '' }}">
                        @if(!empty($search))
                            <a href="{{ route('hari-libur.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
                        @endif
                    </div>
                    <button type="submit" class="btn btn-light border px-3">Filter</button>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.04em;">
                        <th style="width: 60px;" class="text-center">No</th>
                        <th>Hari & Tanggal</th>
                        <th>Keterangan Libur</th>
                        <th>Status Waktu</th>
                        <th>Ditambahkan Oleh</th>
                        <th style="width: 100px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($hariLiburs as $index => $item)
                        @php
                            $carbonTgl = \Carbon\Carbon::parse($item->tanggal);
                            $isPast = $carbonTgl->isPast() && !$carbonTgl->isToday();
                            $isToday = $carbonTgl->isToday();
                        @endphp
                        <tr>
                            <td class="text-center fw-semibold text-muted">
                                {{ $hariLiburs->firstItem() + $index }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-3 p-2 text-center {{ $isToday ? 'bg-danger text-white' : 'bg-light border text-dark' }}" style="min-width: 50px;">
                                        <div class="fw-bold" style="font-size: 1.1rem; line-height: 1;">{{ $carbonTgl->format('d') }}</div>
                                        <div class="small text-uppercase" style="font-size: 0.68rem;">{{ $carbonTgl->format('M') }}</div>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $carbonTgl->translatedFormat('l') }}</div>
                                        <small class="text-muted">{{ $carbonTgl->translatedFormat('d F Y') }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $item->keterangan }}</span>
                            </td>
                            <td>
                                @if($isToday)
                                    <span class="badge bg-danger rounded-pill px-3 py-1.5"><i class="bi bi-broadcast me-1"></i> Hari Ini</span>
                                @elseif($isPast)
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">Telah Lewat</span>
                                @else
                                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1">Mendatang</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $item->pembuat?->name ?? 'Admin' }}</small>
                            </td>
                            <td class="text-center">
                                <form action="{{ route('hari-libur.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus hari libur tanggal {{ $carbonTgl->translatedFormat('d F Y') }} ({{ $item->keterangan }})?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Hapus Hari Libur">
                                        <i class="bi bi-trash3 me-1"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-check fs-2 d-block mb-2 text-muted"></i>
                                Belum ada hari libur khusus yang didaftarkan.
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTambahLibur">
                                        + Tambah Hari Libur
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($hariLiburs->hasPages())
            <div class="mt-4 pt-2 border-top">
                {{ $hariLiburs->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal Tambah Hari Libur -->
<div class="modal fade" id="modalTambahLibur" tabindex="-1" aria-labelledby="modalTambahLiburLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="{{ route('hari-libur.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="modalTambahLiburLabel">
                        <i class="bi bi-calendar-plus text-primary me-2"></i>Tambah Hari Libur Khusus
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Daftarkan tanggal libur nasional atau kegiatan non-efektif sekolah. Siswa dan scanner akan otomatis libur pada tanggal ini.
                    </p>

                    <div class="mb-3">
                        <label for="tanggal_libur" class="form-label small fw-bold">Tanggal Libur <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" id="tanggal_libur" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="keterangan_libur" class="form-label small fw-bold">Keterangan / Nama Hari Libur <span class="text-danger">*</span></label>
                        <input type="text" name="keterangan" id="keterangan_libur" class="form-control" placeholder="Contoh: Libur Hari Raya Idul Fitri, Hari Guru Nasional" required>
                    </div>

                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <span class="small text-muted me-2">Template Cepat:</span>
                        <button type="button" class="badge bg-light text-dark border rounded-pill px-2 py-1" onclick="document.getElementById('keterangan_libur').value = 'Hari Libur Nasional'">Hari Libur Nasional</button>
                        <button type="button" class="badge bg-light text-dark border rounded-pill px-2 py-1" onclick="document.getElementById('keterangan_libur').value = 'Libur Awal Ramadhan'">Awal Ramadhan</button>
                        <button type="button" class="badge bg-light text-dark border rounded-pill px-2 py-1" onclick="document.getElementById('keterangan_libur').value = 'Libur Semester'">Libur Semester</button>
                        <button type="button" class="badge bg-light text-dark border rounded-pill px-2 py-1" onclick="document.getElementById('keterangan_libur').value = 'Kegiatan Classmeeting'">Classmeeting</button>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-save me-1"></i> Simpan Hari Libur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

