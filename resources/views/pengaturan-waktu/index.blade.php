@extends('layouts.admin', [
    'title' => 'Pengaturan Waktu & Sesi',
    'pageTitle' => 'Pengaturan Waktu Presensi',
    'pageSubtitle' => 'Atur jam mulai, toleransi keterlambatan, dan jam selesai tiap sesi absensi'
])

@section('content')
<div class="container-fluid px-0">

    <!-- Timeline & Rule Explanation Banner -->
    <div class="card-custom p-4 mb-4 border-0" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); color: white;">
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold">
                        <i class="bi bi-clock-history me-1"></i> Logika Aturan Waktu
                    </span>
                </div>
                <h4 class="fw-bold text-white mb-2">Bagaimana Sistem Menentukan Status Kehadiran?</h4>
                <div class="alert alert-warning py-2 px-3 rounded-3 mt-3 mb-0" role="alert" style="font-size: 0.82rem;">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>Jeda antar sesi:</strong> setelah suatu sesi berakhir dan waktu sesi berikutnya belum tercapai,
                    semua sesi akan <strong>dimute</strong> dan scanner menampilkan <strong>"Tidak Ada Sesi Aktif"</strong>.
                    Begitu jam mulai sesi berikutnya tercapai, sesi otomatis aktif kembali tanpa perlu tindakan manual.
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-md-4">
                        <div class="p-2 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-20">
                            <span class="badge bg-success mb-1">Tepat Waktu</span>
                            <p class="small mb-0 text-white-50">Siswa scan antara <strong>Jam Mulai</strong> s/d <strong>Batas Terlambat</strong>.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-20">
                            <span class="badge bg-warning text-dark mb-1">Terlambat</span>
                            <p class="small mb-0 text-white-50">Siswa scan melewati <strong>Batas Terlambat</strong> s/d <strong>Jam Selesai</strong>.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-20">
                            <span class="badge bg-danger mb-1">Alfa / Ditutup</span>
                            <p class="small mb-0 text-white-50">Siswa scan setelah <strong>Jam Selesai</strong> atau tidak scan.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <button type="button" class="btn btn-primary-gradient px-4 py-2 rounded-pill shadow" data-bs-toggle="modal" data-bs-target="#modalTambahSesi">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Sesi Baru
                </button>
            </div>
        </div>
    </div>

    <!-- Sesi List Table Card -->
    <div class="card-custom p-0 overflow-hidden mb-4">
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-alarm-fill me-2 text-primary"></i> Daftar Sesi Absensi
            </h6>
            <span class="badge bg-light text-dark border">{{ $sesis->count() }} Sesi Terdaftar</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em;">
                        <th class="ps-4">Status</th>
                        <th>Kode Sesi</th>
                        <th>Nama Sesi</th>
                        <th>Jam Mulai</th>
                        <th>Batas Tepat Waktu</th>
                        <th>Jam Selesai</th>
                        <th>Presensi Terkait</th>
                        <th class="text-end pe-4" style="width: 160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sesis as $s)
                        <tr>
                            <!-- Status Toggle -->
                            <td class="ps-4">
                                <form action="{{ route('pengaturan-waktu.toggle', $s->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $s->is_aktif ? 'btn-success' : 'btn-outline-secondary' }} rounded-pill px-3 py-1" title="Klik untuk mengubah status">
                                        @if($s->is_aktif)
                                            <i class="bi bi-check-circle-fill me-1"></i> Aktif
                                        @else
                                            <i class="bi bi-pause-circle me-1"></i> Nonaktif
                                        @endif
                                    </button>
                                </form>
                            </td>

                            <!-- Kode Sesi -->
                            <td>
                                <span class="badge bg-light text-dark border fw-mono">{{ $s->kode_sesi }}</span>
                            </td>

                            <!-- Nama Sesi -->
                            <td>
                                <strong class="text-dark">{{ $s->nama_sesi }}</strong>
                            </td>

                            <!-- Jam Mulai -->
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                    <i class="bi bi-play-circle me-1"></i> {{ substr($s->jam_mulai, 0, 5) }} WIB
                                </span>
                            </td>

                            <!-- Batas Terlambat -->
                            <td>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                    <i class="bi bi-exclamation-circle me-1"></i> {{ substr($s->jam_terlambat, 0, 5) }} WIB
                                </span>
                            </td>

                            <!-- Jam Selesai -->
                            <td>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                    <i class="bi bi-stop-circle me-1"></i> {{ substr($s->jam_selesai, 0, 5) }} WIB
                                </span>
                            </td>

                            <!-- Presensi Terkait -->
                            <td>
                                <span class="text-muted small">{{ $s->absensis_count }} scan</span>
                            </td>

                            <!-- Aksi -->
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light border text-primary" data-bs-toggle="modal" data-bs-target="#modalEditSesi{{ $s->id }}" title="Edit Sesi">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-light border text-danger" data-bs-toggle="modal" data-bs-target="#modalHapusSesi{{ $s->id }}" title="Hapus Sesi">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal Edit Sesi -->
                        <div class="modal fade" id="modalEditSesi{{ $s->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow rounded-4 text-start">
                                    <div class="modal-header border-bottom py-3">
                                        <h5 class="modal-title fw-bold text-dark">
                                            <i class="bi bi-pencil-square text-primary me-2"></i> Edit Sesi Absensi
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form action="{{ route('pengaturan-waktu.update', $s->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-body p-4">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold">Kode Sesi <span class="text-danger">*</span></label>
                                                    <input type="text" name="kode_sesi" class="form-control" value="{{ old('kode_sesi', $s->kode_sesi) }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold">Nama Sesi <span class="text-danger">*</span></label>
                                                    <input type="text" name="nama_sesi" class="form-control" value="{{ old('nama_sesi', $s->nama_sesi) }}" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small fw-bold">Jam Mulai <span class="text-danger">*</span></label>
                                                    <input type="time" name="jam_mulai" class="form-control" value="{{ old('jam_mulai', substr($s->jam_mulai, 0, 5)) }}" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small fw-bold">Batas Tepat Waktu <span class="text-danger">*</span></label>
                                                    <input type="time" name="jam_terlambat" class="form-control" value="{{ old('jam_terlambat', substr($s->jam_terlambat, 0, 5)) }}" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small fw-bold">Jam Selesai <span class="text-danger">*</span></label>
                                                    <input type="time" name="jam_selesai" class="form-control" value="{{ old('jam_selesai', substr($s->jam_selesai, 0, 5)) }}" required>
                                                </div>
                                                <div class="col-12">
                                                    <div class="form-check form-switch mt-2">
                                                        <input class="form-check-input" type="checkbox" name="is_aktif" value="1" id="aktifSwitch{{ $s->id }}" {{ $s->is_aktif ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-semibold" for="aktifSwitch{{ $s->id }}">Sesi ini aktif digunakan pada scanner</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top bg-light py-3">
                                            <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Hapus Sesi -->
                        <div class="modal fade" id="modalHapusSesi{{ $s->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-sm">
                                <div class="modal-content border-0 shadow rounded-4 text-center p-3">
                                    <div class="modal-body">
                                        <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                                            <i class="bi bi-trash3-fill fs-3"></i>
                                        </div>
                                        <h5 class="fw-bold mb-1">Hapus Sesi?</h5>
                                        <p class="text-muted small mb-3">Yakin ingin menghapus sesi <strong>{{ $s->nama_sesi }}</strong>? Data presensi yang telah tercatat pada sesi ini akan terpengaruh.</p>
                                        <div class="d-flex justify-content-center gap-2">
                                            <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                            <form action="{{ route('pengaturan-waktu.destroy', $s->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger rounded-pill px-3 fw-semibold">Ya, Hapus</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                Belum ada sesi absensi. Silakan klik tombol "Tambah Sesi Baru" di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Tambah Sesi Baru -->
<div class="modal fade" id="modalTambahSesi" tabindex="-1" aria-labelledby="modalTambahSesiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 text-start">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark" id="modalTambahSesiLabel">
                    <i class="bi bi-plus-circle text-primary me-2"></i> Tambah Sesi Absensi Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('pengaturan-waktu.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Kode Sesi <span class="text-danger">*</span></label>
                            <input type="text" name="kode_sesi" class="form-control" placeholder="Contoh: pagi-01" value="{{ old('kode_sesi') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nama Sesi <span class="text-danger">*</span></label>
                            <input type="text" name="nama_sesi" class="form-control" placeholder="Contoh: Kedatangan Pagi" value="{{ old('nama_sesi') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Jam Mulai <span class="text-danger">*</span></label>
                            <input type="time" name="jam_mulai" class="form-control" value="{{ old('jam_mulai', '06:30') }}" required>
                            <small class="text-muted" style="font-size: 0.7rem;">Mulai scan dibuka</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Batas Tepat Waktu <span class="text-danger">*</span></label>
                            <input type="time" name="jam_terlambat" class="form-control" value="{{ old('jam_terlambat', '07:00') }}" required>
                            <small class="text-muted" style="font-size: 0.7rem;">Lewat ini = Terlambat</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Jam Selesai <span class="text-danger">*</span></label>
                            <input type="time" name="jam_selesai" class="form-control" value="{{ old('jam_selesai', '07:15') }}" required>
                            <small class="text-muted" style="font-size: 0.7rem;">Sesi ditutup</small>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_aktif" value="1" id="aktifBaru" checked>
                                <label class="form-check-label fw-semibold" for="aktifBaru">Aktifkan sesi ini sekarang</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-3">
                    <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Sesi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
