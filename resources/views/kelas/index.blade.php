@extends('layouts.admin', [
    'title' => 'Kelola Data Kelas',
    'pageTitle' => 'Kelola Data Kelas',
    'pageSubtitle' => 'MAN 2 Bandar Lampung - Manajemen Kelas, Tahun Pelajaran, dan Penugasan Wali Kelas'
])

@section('content')
<div class="container-fluid px-0">

    <!-- Top Action Card: Filter, Search & Add Button -->
    <div class="card-custom p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <form action="{{ route('kelas.index') }}" method="GET" class="d-flex flex-wrap gap-2">
                    <div style="min-width: 200px;">
                        <select name="tahun_pelajaran" class="form-select bg-light" onchange="this.form.submit()">
                            <option value="">-- Semua Tahun Pelajaran --</option>
                            @foreach($daftarTahun as $tp)
                                <option value="{{ $tp }}" {{ $tahun === $tp ? 'selected' : '' }}>TP {{ $tp }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="input-group" style="max-width: 300px;">
                        <input type="text" name="search" class="form-control bg-light" placeholder="Cari nama kelas / wali kelas..." value="{{ $search }}">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>

                    @if($search || $tahun)
                        <a href="{{ route('kelas.index') }}" class="btn btn-light border" title="Reset Filter">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    @endif
                </form>
            </div>

            <div class="col-lg-4 text-lg-end">
                <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                    <a href="{{ route('qrcode.kelas', array_filter(['tahun_pelajaran' => $tahun ?: null])) }}"
                       target="_blank" rel="noopener"
                       class="btn btn-outline-primary rounded-pill px-3 py-2 fw-semibold"
                       title="Buka layout cetak QR Code Kelas (A4)">
                        <i class="bi bi-printer-fill me-1"></i> Cetak QR Code Kelas
                    </a>
                    <button type="button" class="btn btn-primary-gradient rounded-pill px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahKelas">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Kelas Baru
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card-custom p-0 overflow-hidden mb-4">
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-door-open-fill text-primary me-2"></i> Daftar Kelas & Wali Kelas MAN 2 Bandar Lampung
            </h6>
            <span class="badge bg-primary rounded-pill px-3 py-2">
                Total: {{ $kelases->total() }} Kelas
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr style="font-size: 0.8rem; text-transform: uppercase;">
                        <th class="ps-4" style="width: 60px;">ID</th>
                        <th>Tahun Pelajaran</th>
                        <th>Nama Kelas</th>
                        <th>Wali Kelas</th>
                        <th class="text-center">Siswa Terdaftar</th>
                        <th class="text-center pe-4" style="width: 160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kelases as $k)
                        <tr>
                            <td class="ps-4 text-muted fw-bold">#{{ $k->id }}</td>
                            <td>
                                <span class="badge bg-emerald-subtle text-success px-3 py-1 rounded-pill fw-bold" style="background-color: #d1fae5; color: #065f46;">
                                    <i class="bi bi-calendar3 me-1"></i> TP {{ $k->tahun_pelajaran }}
                                </span>
                            </td>
                            <td>
                                <strong class="fs-6 text-dark">{{ $k->kelas }}</strong>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-primary" style="width: 32px; height: 32px;">
                                        <i class="bi bi-person-workspace"></i>
                                    </div>
                                    <span class="fw-semibold text-dark">{{ $k->wali_kelas ?: '-' }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold">
                                    <i class="bi bi-people-fill me-1"></i> {{ $k->siswas_count }} Siswa
                                </span>
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('qrcode.kelas', ['tahun_pelajaran' => $k->tahun_pelajaran]) }}"
                                       target="_blank" rel="noopener"
                                       class="btn btn-outline-primary" title="Cetak QR Code Kelas {{ $k->kelas }}">
                                        <i class="bi bi-qr-code"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-warning" title="Edit Kelas"
                                            data-bs-toggle="modal" data-bs-target="#modalEditKelas{{ $k->id }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" title="Hapus Kelas"
                                            data-bs-toggle="modal" data-bs-target="#modalHapusKelas{{ $k->id }}">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-door-closed fs-1 text-muted d-block mb-2"></i>
                                Belum ada data kelas yang terdaftar. Silakan tambahkan kelas baru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($kelases->hasPages())
            <div class="p-3 border-top bg-light">
                {{ $kelases->links() }}
            </div>
        @endif
    </div>

    <!-- Modals Edit & Hapus Kelas (Ditempatkan di luar tabel agar HTML valid) -->
    @foreach($kelases as $k)
        <!-- Modal Edit Kelas (ID: {{ $k->id }}) -->
        <div class="modal fade" id="modalEditKelas{{ $k->id }}" tabindex="-1" aria-labelledby="modalEditKelasLabel{{ $k->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4 text-start">
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark" id="modalEditKelasLabel{{ $k->id }}">
                            <i class="bi bi-pencil-square text-warning me-2"></i> Edit Data Kelas
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('kelas.update', $k->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Tahun Pelajaran <span class="text-danger">*</span></label>
                                <input type="text" name="tahun_pelajaran" class="form-control" value="{{ old('tahun_pelajaran', $k->tahun_pelajaran) }}" required>
                                <small class="text-muted" style="font-size: 0.72rem;">Format: YYYY/YYYY (contoh: 2026/2027).</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Nama Kelas <span class="text-danger">*</span></label>
                                <input type="text" name="kelas" class="form-control" value="{{ old('kelas', $k->kelas) }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Wali Kelas <span class="text-danger">*</span></label>
                                <input type="text" name="wali_kelas" list="listGuru" class="form-control" value="{{ old('wali_kelas', $k->wali_kelas) }}" required>
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

        <!-- Modal Hapus Kelas (ID: {{ $k->id }}) -->
        <div class="modal fade" id="modalHapusKelas{{ $k->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow rounded-4 text-center p-3">
                    <div class="modal-body">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-1 mb-2 d-block"></i>
                        <h5 class="fw-bold text-dark mb-1">Hapus Kelas?</h5>
                        <p class="text-muted small mb-3">
                            Anda akan menghapus <strong>Kelas {{ $k->kelas }}</strong> (TP {{ $k->tahun_pelajaran }}). Tindakan ini tidak dapat dibatalkan.
                        </p>
                        <form action="{{ route('kelas.destroy', $k->id) }}" method="POST">
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

<!-- Modal Tambah Kelas -->
<div class="modal fade" id="modalTambahKelas" tabindex="-1" aria-labelledby="modalTambahKelasLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark" id="modalTambahKelasLabel">
                    <i class="bi bi-plus-circle text-primary me-2"></i> Tambah Kelas Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('kelas.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tahun Pelajaran <span class="text-danger">*</span></label>
                        <input type="text" name="tahun_pelajaran" class="form-control" placeholder="Contoh: 2026/2027" value="{{ old('tahun_pelajaran', '2026/2027') }}" required>
                        <small class="text-muted" style="font-size: 0.72rem;">Format: YYYY/YYYY (contoh: 2026/2027).</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Kelas <span class="text-danger">*</span></label>
                        <input type="text" name="kelas" class="form-control" placeholder="Contoh: X-A, XI-IPA-1, XII-IPS-2" value="{{ old('kelas') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Wali Kelas <span class="text-danger">*</span></label>
                        <input type="text" name="wali_kelas" list="listGuru" class="form-control" placeholder="Nama lengkap & gelar wali kelas" value="{{ old('wali_kelas') }}" required>
                        <datalist id="listGuru">
                            @foreach($daftarGuru as $g)
                                <option value="{{ $g->name }}">{{ $g->role === 'wali_kelas' ? '(Wali Kelas)' : '(Guru)' }}</option>
                            @endforeach
                        </datalist>
                        <small class="text-muted" style="font-size: 0.72rem;">Dapat memilih nama guru terdaftar atau mengetikkan nama baru.</small>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-3">
                    <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-gradient rounded-pill px-4">Simpan Kelas</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
