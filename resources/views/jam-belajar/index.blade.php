@extends('layouts.admin', [
    'title' => 'Kelola Waktu Belajar',
    'pageTitle' => 'Kelola Waktu Belajar',
    'pageSubtitle' => 'MAN 2 Bandar Lampung - Pengaturan Jam Ke, Waktu Mulai, dan Waktu Selesai Pembelajaran'
])

@section('content')
<div class="container-fluid px-0">

    <!-- Top Action Card -->
    <div class="card-custom p-4 mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-sm">
                    <i class="bi bi-hourglass-split text-primary me-1"></i> Total Jam Belajar: <strong class="text-primary">{{ $jamBelajars->count() }}</strong>
                </span>
            </div>

            <div>
                <button type="button" class="btn btn-primary-gradient rounded-pill px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahJamBelajar">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Jam Belajar
                </button>
            </div>
        </div>
    </div>

    <!-- Table Card (jam_belajars: id | jam_ke | waktu_mulai | waktu_selesai) -->
    <div class="card-custom p-0 overflow-hidden mb-4">
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-clock-fill text-primary me-2"></i> Tabel Jam Belajar (`jam_belajars`)
            </h6>
            <span class="badge bg-primary rounded-pill px-3 py-2">
                Total: {{ $jamBelajars->count() }} Jam
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr style="font-size: 0.8rem; text-transform: uppercase;">
                        <th class="ps-4" style="width: 80px;">ID</th>
                        <th>Jam Ke</th>
                        <th>Waktu Mulai</th>
                        <th>Waktu Selesai</th>
                        <th class="text-center pe-4" style="width: 160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jamBelajars as $jb)
                        <tr>
                            <td class="ps-4 text-muted fw-bold">#{{ $jb->id }}</td>
                            <td>
                                <span class="badge bg-indigo-subtle text-primary border px-3 py-1 rounded-pill fw-bold" style="background-color: #e0e7ff; color: #4338ca;">
                                    Jam ke-{{ $jb->jam_ke }}
                                </span>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">
                                    <i class="bi bi-clock text-success me-1"></i> {{ substr($jb->waktu_mulai, 0, 5) }} WIB
                                </span>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">
                                    <i class="bi bi-clock-history text-danger me-1"></i> {{ substr($jb->waktu_selesai, 0, 5) }} WIB
                                </span>
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-warning" title="Edit Jam Belajar"
                                            data-bs-toggle="modal" data-bs-target="#modalEditJamBelajar{{ $jb->id }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" title="Hapus Jam Belajar"
                                            data-bs-toggle="modal" data-bs-target="#modalHapusJamBelajar{{ $jb->id }}">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-hourglass fs-1 text-muted d-block mb-2"></i>
                                Belum ada data jam belajar yang terdaftar. Silakan tambahkan jam belajar baru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modals Edit & Hapus Jam Belajar -->
    @foreach($jamBelajars as $jb)
        <!-- Modal Edit Jam Belajar -->
        <div class="modal fade" id="modalEditJamBelajar{{ $jb->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4 text-start">
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark">
                            <i class="bi bi-pencil-square text-warning me-2"></i> Edit Jam Belajar
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('jam-belajar.update', $jb->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Jam Ke <span class="text-danger">*</span></label>
                                <input type="text" name="jam_ke" class="form-control" value="{{ old('jam_ke', $jb->jam_ke) }}" placeholder="Contoh: 1, 2, 3" required>
                            </div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Waktu Mulai <span class="text-danger">*</span></label>
                                    <input type="time" name="waktu_mulai" class="form-control" value="{{ substr($jb->waktu_mulai, 0, 5) }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Waktu Selesai <span class="text-danger">*</span></label>
                                    <input type="time" name="waktu_selesai" class="form-control" value="{{ substr($jb->waktu_selesai, 0, 5) }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-light py-3">
                            <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-warning rounded-pill px-4 fw-semibold">Perbarui Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Hapus Jam Belajar -->
        <div class="modal fade" id="modalHapusJamBelajar{{ $jb->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow rounded-4 text-center p-3">
                    <div class="modal-body">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-1 mb-2 d-block"></i>
                        <h5 class="fw-bold text-dark mb-1">Hapus Jam Belajar?</h5>
                        <p class="text-muted small mb-3">
                            Hapus <strong>Jam ke-{{ $jb->jam_ke }}</strong> ({{ substr($jb->waktu_mulai, 0, 5) }} - {{ substr($jb->waktu_selesai, 0, 5) }})?
                        </p>
                        <form action="{{ route('jam-belajar.destroy', $jb->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold">Ya, Hapus</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

</div>

<!-- Modal Tambah Jam Belajar -->
<div class="modal fade" id="modalTambahJamBelajar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="bi bi-plus-circle text-primary me-2"></i> Tambah Jam Belajar Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('jam-belajar.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Jam Ke <span class="text-danger">*</span></label>
                        <input type="text" name="jam_ke" class="form-control" placeholder="Contoh: 1, 2, 3, atau Istirahat" value="{{ old('jam_ke') }}" required>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Waktu Mulai <span class="text-danger">*</span></label>
                            <input type="time" name="waktu_mulai" class="form-control" value="{{ old('waktu_mulai', '07:15') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Waktu Selesai <span class="text-danger">*</span></label>
                            <input type="time" name="waktu_selesai" class="form-control" value="{{ old('waktu_selesai', '08:00') }}" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-3">
                    <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-gradient rounded-pill px-4">Simpan Jam Belajar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
