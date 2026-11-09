@extends('layouts.admin', [
    'title' => 'Kelola Mata Pelajaran',
    'pageTitle' => 'Kelola Mata Pelajaran',
    'pageSubtitle' => 'MAN 2 Bandar Lampung - Manajemen Daftar Mata Pelajaran'
])

@section('content')
<div class="container-fluid px-0">

    <!-- Top Action Card -->
    <div class="card-custom p-4 mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-sm">
                    <i class="bi bi-journal-bookmark-fill text-primary me-1"></i> Total Mata Pelajaran: <strong class="text-primary">{{ $mapels->total() }}</strong>
                </span>
            </div>

            <div>
                <button type="button" class="btn btn-primary-gradient rounded-pill px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahMapel">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Mata Pelajaran
                </button>
            </div>
        </div>
    </div>

    <!-- Table Card (mapels: id | mata_pelajaran) -->
    <div class="card-custom p-0 overflow-hidden mb-4">
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-journal-bookmark-fill text-primary me-2"></i> Daftar Mata Pelajaran (`mapels`)
            </h6>
            <span class="badge bg-primary rounded-pill px-3 py-2">
                Total: {{ $mapels->total() }} Mapel
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr style="font-size: 0.8rem; text-transform: uppercase;">
                        <th class="ps-4" style="width: 80px;">ID</th>
                        <th style="width: 180px;">Kode Mapel</th>
                        <th>Mata Pelajaran</th>
                        <th class="text-center pe-4" style="width: 160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mapels as $m)
                        <tr>
                            <td class="ps-4 text-muted fw-bold">#{{ $m->id }}</td>
                            <td>
                                <span class="badge bg-indigo-subtle text-primary border px-3 py-1 rounded-pill fw-bold" style="background-color: #e0e7ff; color: #4338ca;">
                                    {{ $m->kode_mapel ?: '-' }}
                                </span>
                            </td>
                            <td>
                                <strong class="text-dark">{{ $m->mata_pelajaran }}</strong>
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-warning" title="Edit Mapel"
                                            data-bs-toggle="modal" data-bs-target="#modalEditMapel{{ $m->id }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" title="Hapus Mapel"
                                            data-bs-toggle="modal" data-bs-target="#modalHapusMapel{{ $m->id }}">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x fs-1 text-muted d-block mb-2"></i>
                                Belum ada data mata pelajaran yang terdaftar. Silakan tambahkan mata pelajaran baru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($mapels->hasPages())
            <div class="p-3 border-top bg-light">
                {{ $mapels->links() }}
            </div>
        @endif
    </div>

    <!-- Modals Edit & Hapus Mapel -->
    @foreach($mapels as $m)
        <!-- Modal Edit Mapel -->
        <div class="modal fade" id="modalEditMapel{{ $m->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4 text-start">
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark">
                            <i class="bi bi-pencil-square text-warning me-2"></i> Edit Mata Pelajaran
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('mapel.update', $m->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Kode Mapel <span class="text-danger">*</span></label>
                                <input type="text" name="kode_mapel" class="form-control" value="{{ old('kode_mapel', $m->kode_mapel) }}" placeholder="Contoh: MTK, BIN, FIS" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-bold">Nama Mata Pelajaran <span class="text-danger">*</span></label>
                                <input type="text" name="mata_pelajaran" class="form-control" value="{{ old('mata_pelajaran', $m->mata_pelajaran) }}" required>
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

        <!-- Modal Hapus Mapel -->
        <div class="modal fade" id="modalHapusMapel{{ $m->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow rounded-4 text-center p-3">
                    <div class="modal-body">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-1 mb-2 d-block"></i>
                        <h5 class="fw-bold text-dark mb-1">Hapus Mata Pelajaran?</h5>
                        <p class="text-muted small mb-3">
                            Hapus mata pelajaran <strong>{{ $m->mata_pelajaran }}</strong>?
                        </p>
                        <form action="{{ route('mapel.destroy', $m->id) }}" method="POST">
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

<!-- Modal Tambah Mapel -->
<div class="modal fade" id="modalTambahMapel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="bi bi-plus-circle text-primary me-2"></i> Tambah Mata Pelajaran Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('mapel.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kode Mapel <span class="text-danger">*</span></label>
                        <input type="text" name="kode_mapel" class="form-control" placeholder="Contoh: MTK, BIN, FIS, QH" value="{{ old('kode_mapel') }}" required>
                        <small class="text-muted" style="font-size: 0.72rem;">Kode mapel ini yang akan ditampilkan pada tabel jadwal pelajaran.</small>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Nama Mata Pelajaran <span class="text-danger">*</span></label>
                        <input type="text" name="mata_pelajaran" class="form-control" placeholder="Contoh: Matematika, Bahasa Indonesia, Al-Qur'an Hadis" value="{{ old('mata_pelajaran') }}" required>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-3">
                    <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-gradient rounded-pill px-4">Simpan Mata Pelajaran</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
