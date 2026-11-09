@extends('layouts.admin', [
    'title' => 'Kelola Siswa',
    'pageTitle' => 'Kelola Data Siswa',
    'pageSubtitle' => 'Input manual, import file Excel (.xlsx), dan atur foto serta NISN siswa'
])

@section('content')
<div class="container-fluid px-0">

    <!-- Top Action & Statistics Bar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-sm">
                <i class="bi bi-people-fill text-primary me-1"></i> Total Siswa: <strong class="text-primary">{{ $totalSiswa }}</strong>
            </span>
            <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-sm">
                <i class="bi bi-camera-fill text-success me-1"></i> Dengan Foto: <strong class="text-success">{{ $totalDenganFoto }}</strong>
            </span>
            <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-sm">
                <i class="bi bi-building text-info me-1"></i> Kelas: <strong class="text-info">{{ $daftarKelas->count() }}</strong>
            </span>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- Download Template Button -->
            <a href="{{ route('siswa.template') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2 shadow-sm">
                <i class="bi bi-file-earmark-arrow-down me-1"></i> Download Template XLSX
            </a>

            <!-- Export XLSX Button -->
            <a href="{{ route('siswa.export', ['kelas' => request('kelas')]) }}" class="btn btn-outline-success btn-sm rounded-pill px-3 py-2 shadow-sm">
                <i class="bi bi-file-earmark-excel me-1"></i> Export Data (.xlsx)
            </a>

            <!-- Import XLSX Modal Trigger -->
            <button type="button" class="btn btn-success btn-sm rounded-pill px-3 py-2 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalImportXlsx">
                <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Import dari Excel
            </button>

            <!-- Manual Add Siswa Modal Trigger -->
            <button type="button" class="btn btn-primary-gradient btn-sm rounded-pill px-3 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahSiswa">
                <i class="bi bi-person-plus-fill me-1"></i> Input Siswa Manual
            </button>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card-custom p-3 mb-4">
        <form action="{{ route('siswa.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Cari nama siswa, NIS, atau NISN..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="kelas" class="form-select bg-light">
                    <option value="">-- Semua Kelas --</option>
                    @foreach($daftarKelas as $k)
                        <option value="{{ $k }}" {{ request('kelas') == $k ? 'selected' : '' }}>Kelas {{ $k }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
                @if(request('search') || request('kelas'))
                    <a href="{{ route('siswa.index') }}" class="btn btn-light border rounded-3 px-3">
                        <i class="bi bi-x-circle me-1"></i> Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Siswa Table Card -->
    <div class="card-custom p-0 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">
                        <th class="ps-4" style="width: 70px;">Foto</th>
                        <th>NIS</th>
                        <th>NISN (QR Code)</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>No HP Siswa</th>
                        <th>No HP Orang Tua</th>
                        <th class="text-end pe-4" style="width: 160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($siswas as $s)
                        <tr>
                            <!-- Foto Siswa -->
                            <td class="ps-4">
                                <div class="position-relative d-inline-block">
                                    <img src="{{ $s->foto_url }}" alt="{{ $s->nama }}" class="rounded-circle border shadow-sm" style="width: 44px; height: 44px; object-fit: cover;">
                                    @if($s->foto)
                                        <span class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle" style="width: 12px; height: 12px;" title="Foto tersimpan"></span>
                                    @endif
                                </div>
                            </td>

                            <!-- NIS -->
                            <td>
                                <span class="badge bg-light text-dark border fw-mono">{{ $s->nis }}</span>
                            </td>

                            <!-- NISN -->
                            <td>
                                <span class="badge bg-indigo-subtle text-primary border border-indigo-200 fw-mono" style="background-color: #e0e7ff; color: #4338ca;">
                                    {{ $s->nisn ?: '-' }}
                                </span>
                            </td>

                            <!-- Nama -->
                            <td>
                                <div class="fw-bold text-dark">{{ $s->nama }}</div>
                            </td>

                            <!-- Kelas -->
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1">{{ $s->kelas }}</span>
                            </td>

                            <!-- No HP Siswa -->
                            <td>
                                @if($s->no_hp_siswa)
                                    @php
                                        $cleanPhoneSiswa = preg_replace('/[^0-9]/', '', $s->no_hp_siswa);
                                        if (str_starts_with($cleanPhoneSiswa, '0')) {
                                            $cleanPhoneSiswa = '62' . substr($cleanPhoneSiswa, 1);
                                        }
                                    @endphp
                                    <a href="https://wa.me/{{ $cleanPhoneSiswa }}" target="_blank" class="text-decoration-none text-primary small fw-semibold" title="Chat WhatsApp Siswa">
                                        <i class="bi bi-phone me-1"></i> {{ $s->no_hp_siswa }}
                                    </a>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>

                            <!-- No HP Ortu -->
                            <td>
                                @if($s->no_hp_ortu)
                                    @php
                                        $cleanPhone = preg_replace('/[^0-9]/', '', $s->no_hp_ortu);
                                        if (str_starts_with($cleanPhone, '0')) {
                                            $cleanPhone = '62' . substr($cleanPhone, 1);
                                        }
                                    @endphp
                                    <a href="https://wa.me/{{ $cleanPhone }}" target="_blank" class="text-decoration-none text-success small fw-semibold" title="Chat WhatsApp Orang Tua">
                                        <i class="bi bi-whatsapp me-1"></i> {{ $s->no_hp_ortu }}
                                    </a>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border text-primary" data-bs-toggle="modal" data-bs-target="#modalEditSiswa{{ $s->id }}" title="Edit Data">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-light border text-danger" data-bs-toggle="modal" data-bs-target="#modalHapusSiswa{{ $s->id }}" title="Hapus Siswa">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-1 d-block mb-2 text-muted"></i>
                                Tidak ada data siswa yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($siswas->hasPages())
            <div class="p-3 border-top d-flex justify-content-between align-items-center bg-white">
                <small class="text-muted">
                    Menampilkan {{ $siswas->firstItem() }} - {{ $siswas->lastItem() }} dari total {{ $siswas->total() }} siswa
                </small>
                <div>
                    {{ $siswas->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Modals Edit & Hapus Siswa (Ditempatkan di luar tabel agar HTML valid) -->
    @foreach($siswas as $s)
        <!-- Modal Edit Siswa -->
        <div class="modal fade" id="modalEditSiswa{{ $s->id }}" tabindex="-1" aria-labelledby="modalEditLabel{{ $s->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow rounded-4">
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark" id="modalEditLabel{{ $s->id }}">
                            <i class="bi bi-pencil-square text-primary me-2"></i> Edit Data Siswa
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('siswa.update', $s->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="modal-body p-4 text-start">
                            <!-- Current Photo Preview -->
                            <div class="text-center mb-3">
                                <div class="d-inline-block position-relative">
                                    <img id="previewFotoEdit{{ $s->id }}" src="{{ $s->foto_url }}" alt="{{ $s->nama }}" class="rounded-circle border shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                                </div>
                                @if($s->foto)
                                    <div class="form-check d-flex justify-content-center gap-2 mt-2">
                                        <input class="form-check-input" type="checkbox" name="hapus_foto" value="1" id="hapusFoto{{ $s->id }}">
                                        <label class="form-check-label text-danger small fw-semibold" for="hapusFoto{{ $s->id }}">
                                            Hapus foto saat ini
                                        </label>
                                    </div>
                                @endif
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">NIS <span class="text-danger">*</span></label>
                                    <input type="text" name="nis" class="form-control" value="{{ old('nis', $s->nis) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">NISN (Digunakan untuk QR Code)</label>
                                    <input type="text" name="nisn" class="form-control" value="{{ old('nisn', $s->nisn) }}" placeholder="Contoh: 0020260001">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Nama Lengkap Siswa <span class="text-danger">*</span></label>
                                    <input type="text" name="nama" class="form-control" value="{{ old('nama', $s->nama) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Kelas <span class="text-danger">*</span></label>
                                    <input type="text" name="kelas" class="form-control" value="{{ old('kelas', $s->kelas) }}" placeholder="Contoh: X-A" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">No HP Siswa</label>
                                    <input type="text" name="no_hp_siswa" class="form-control" value="{{ old('no_hp_siswa', $s->no_hp_siswa) }}" placeholder="Contoh: 081234567890">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">No HP Orang Tua</label>
                                    <input type="text" name="no_hp_ortu" class="form-control" value="{{ old('no_hp_ortu', $s->no_hp_ortu) }}" placeholder="Contoh: 081234567891">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Ganti Foto Siswa (Opsional)</label>
                                    <input type="file" name="foto" class="form-control" accept="image/*" onchange="previewImage(this, 'previewFotoEdit{{ $s->id }}')">
                                    <small class="text-muted" style="font-size: 0.75rem;">Format: JPG, PNG, WebP. Maksimal 2MB.</small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-light py-3">
                            <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                                <i class="bi bi-save me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Hapus Siswa -->
        <div class="modal fade" id="modalHapusSiswa{{ $s->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow rounded-4 text-center p-3">
                    <div class="modal-body">
                        <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                            <i class="bi bi-trash3-fill fs-3"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Hapus Siswa?</h5>
                        <p class="text-muted small mb-3">Yakin ingin menghapus <strong>{{ $s->nama }}</strong> (NIS: {{ $s->nis }})? Data presensi terkait juga akan terhapus.</p>
                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                            <form action="{{ route('siswa.destroy', $s->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger rounded-pill px-3 fw-semibold">Ya, Hapus</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

</div>

<!-- Modal Tambah Siswa Manual -->
<div class="modal fade" id="modalTambahSiswa" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark" id="modalTambahLabel">
                    <i class="bi bi-person-plus-fill text-primary me-2"></i> Input Siswa Manual
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('siswa.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4 text-start">
                    <!-- Photo Preview -->
                    <div class="text-center mb-3">
                        <img id="previewFotoTambah" src="https://ui-avatars.com/api/?name=Siswa+Baru&background=e0e7ff&color=4f46e5&size=128" alt="Preview" class="rounded-circle border shadow-sm mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                        <div class="text-muted small">Pratinjau Foto Siswa</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">NIS <span class="text-danger">*</span></label>
                            <input type="text" name="nis" class="form-control" placeholder="Contoh: 20261001" value="{{ old('nis') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">NISN (Untuk QR Code)</label>
                            <input type="text" name="nisn" class="form-control" placeholder="Contoh: 0020261001" value="{{ old('nisn') }}">
                            <small class="text-muted" style="font-size: 0.72rem;">Jika kosong otomatis diisi 00+NIS</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Nama Lengkap Siswa <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control" placeholder="Contoh: Muhammad Farhan" value="{{ old('nama') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Kelas <span class="text-danger">*</span></label>
                            <input type="text" name="kelas" class="form-control" placeholder="Contoh: X-A" value="{{ old('kelas') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">No HP Siswa</label>
                            <input type="text" name="no_hp_siswa" class="form-control" placeholder="Contoh: 081234567890" value="{{ old('no_hp_siswa') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">No HP Orang Tua</label>
                            <input type="text" name="no_hp_ortu" class="form-control" placeholder="Contoh: 081234567891" value="{{ old('no_hp_ortu') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Upload Foto Siswa</label>
                            <input type="file" name="foto" class="form-control" accept="image/*" onchange="previewImage(this, 'previewFotoTambah')">
                            <small class="text-muted" style="font-size: 0.75rem;">Disimpan di storage public agar dapat diakses kapan saja. Maksimal 2MB.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-3">
                    <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Data Siswa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Import XLSX -->
<div class="modal fade" id="modalImportXlsx" tabindex="-1" aria-labelledby="modalImportLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark" id="modalImportLabel">
                    <i class="bi bi-file-earmark-spreadsheet-fill text-success me-2"></i> Import Siswa dari File Excel (.xlsx)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('siswa.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4 text-start">
                    <div class="alert alert-info border-0 rounded-3 mb-3 small">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-info-circle-fill text-info fs-5"></i>
                            <strong>Petunjuk Import Excel:</strong>
                        </div>
                        <p class="mb-1">Pastikan baris pertama file Excel adalah judul kolom: <strong>NIS, NISN, NAMA, KELAS, NO HP ORTU</strong>.</p>
                        <p class="mb-0">Jika NIS sudah ada dalam database, data siswa akan diperbarui secara otomatis.</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Pilih File Excel (.xlsx / .xls) <span class="text-danger">*</span></label>
                        <input type="file" name="file_xlsx" class="form-control" accept=".xlsx, .xls" required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 border">
                        <div class="small text-muted">
                            <i class="bi bi-filetype-xlsx text-success fs-5 me-1"></i> Belum punya format file?
                        </div>
                        <a href="{{ route('siswa.template') }}" class="btn btn-sm btn-outline-primary rounded-pill">
                            <i class="bi bi-download me-1"></i> Unduh Template
                        </a>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-3">
                    <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold">
                        <i class="bi bi-upload me-1"></i> Mulai Import Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function previewImage(input, targetId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(targetId).src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endpush
