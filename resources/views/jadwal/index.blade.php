@extends('layouts.admin', [
'title' => 'Kelola Jadwal Pelajaran',
'pageTitle' => 'Kelola Jadwal Pelajaran',
'pageSubtitle' => 'MAN 2 Bandar Lampung - Pengaturan Jadwal Pelajaran per Tahun Pelajaran, Semester, Kelas, dan Hari'
])

@section('content')
<div class="container-fluid px-0">

    <!-- Filter Bar: Dropdown Tahun Pelajaran, Semester, Kelas, dan Hari -->
    <div class="card-custom p-4 mb-4">
        <form action="{{ route('jadwal.index') }}" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-bold text-secondary mb-1">
                        <i class="bi bi-calendar-range text-primary me-1"></i> Tahun Pelajaran
                    </label>
                    <select name="tahun_pelajaran" class="form-select bg-light fw-semibold" onchange="this.form.submit()">
                        <option value="">-- Semua Tahun Pelajaran --</option>
                        @foreach($daftarTahun as $tp)
                        <option value="{{ $tp }}" {{ (string) $tahunFilter === (string) $tp ? 'selected' : '' }}>
                            {{ $tp }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label small fw-bold text-secondary mb-1">
                        <i class="bi bi-layers text-primary me-1"></i> Semester
                    </label>
                    <select name="semester" class="form-select bg-light fw-semibold" onchange="this.form.submit()">
                        <option value="">-- Semua Semester --</option>
                        @foreach($daftarSemester as $sem)
                        <option value="{{ $sem }}" {{ (string) $semesterFilter === (string) $sem ? 'selected' : '' }}>
                            Semester {{ $sem }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-bold text-secondary mb-1">
                        <i class="bi bi-door-open text-primary me-1"></i> Kelas
                    </label>
                    <select name="kelas" class="form-select bg-light fw-semibold" onchange="this.form.submit()">
                        <option value="">-- Semua Kelas --</option>
                        @foreach($daftarKelas as $k)
                        <option value="{{ $k->id }}" {{ (string) $kelasFilter === (string) $k->id ? 'selected' : '' }}>
                            Kelas {{ $k->kelas }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label small fw-bold text-secondary mb-1">
                        <i class="bi bi-calendar-day text-primary me-1"></i> Hari
                    </label>
                    <select name="hari" class="form-select bg-light fw-semibold" onchange="this.form.submit()">
                        <option value="">-- Semua Hari --</option>
                        @foreach($daftarHari as $h)
                        <option value="{{ $h }}" {{ (string) $hariFilter === (string) $h ? 'selected' : '' }}>
                            {{ $h }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-12 d-flex gap-2 justify-content-md-end">
                    <button type="button" class="btn btn-primary-gradient rounded-pill px-3 py-2 shadow-sm w-100" data-bs-toggle="modal" data-bs-target="#modalTambahJadwal">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Jadwal
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabel Jadwal Pelajaran (jam_ke | waktu_mulai | waktu_selesai | mata_pelajaran | nama_pengajar) -->
    @php
        $slotRows = collect();
        $matchedJadwalIds = [];

        foreach ($jamBelajars as $jb) {
            $matched = $jadwals->first(function ($j) use ($jb) {
                return (int) $j->jam_belajar_id === (int) $jb->id
                    || trim((string) $j->jam_ke) === trim((string) $jb->jam_ke);
            });
            if ($matched) {
                $matchedJadwalIds[] = $matched->id;
            }
            $slotRows->push([
                'jam_belajar_id' => $jb->id,
                'jam_ke' => $jb->jam_ke,
                'waktu_mulai' => $jb->waktu_mulai,
                'waktu_selesai' => $jb->waktu_selesai,
                'jadwal' => $matched,
            ]);
        }

        foreach ($jadwals as $j) {
            if (!in_array($j->id, $matchedJadwalIds, true)) {
                $slotRows->push([
                    'jam_belajar_id' => $j->jam_belajar_id,
                    'jam_ke' => $j->jam_ke,
                    'waktu_mulai' => $j->waktu_mulai,
                    'waktu_selesai' => $j->waktu_selesai,
                    'jadwal' => $j,
                ]);
            }
        }
    @endphp

    <div class="card-custom p-0 overflow-hidden mb-4">
        <form action="{{ route('jadwal.store') }}" method="POST" id="formBulkJadwal">
            @csrf
            <input type="hidden" name="tahun_pelajaran" value="{{ $tahunFilter ?: ($daftarTahun->first() ?? date('Y').'/'.(date('Y')+1)) }}">
            <input type="hidden" name="semester" value="{{ $semesterFilter ?: 'Ganjil' }}">
            <input type="hidden" name="kelas" value="{{ $kelasFilter ?: $daftarKelas->first()?->id }}">
            <input type="hidden" name="hari" value="{{ $hariFilter ?: 'Senin' }}">

            <div class="p-3 bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-calendar2-week-fill text-primary me-2"></i> Tabel Jadwal Pelajaran
                    </h6>
                    <small class="text-muted" style="font-size: 0.75rem;">
                        Pilih <strong>Mata Pelajaran</strong> (dari tabel <code>mapels</code>) dan <strong>Nama Pengajar</strong> (dari tabel <code>users</code>) pada setiap jam belajar. Pada <strong>Dashboard Mengajar</strong> akan ditampilkan <strong>Kode Mapel</strong>.
                    </small>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if($tahunFilter)
                    <span class="badge bg-white text-dark border rounded-pill px-3 py-1">TP: {{ $tahunFilter }}</span>
                    @endif
                    @if($semesterFilter)
                    <span class="badge bg-white text-dark border rounded-pill px-3 py-1">Semester: {{ $semesterFilter }}</span>
                    @endif
                    @if($hariFilter)
                    <span class="badge bg-info-subtle text-info border rounded-pill px-3 py-1">Hari: {{ $hariFilter }}</span>
                    @endif
                    @if($slotRows->isNotEmpty() && $daftarKelas->isNotEmpty())
                    <button type="submit" class="btn btn-sm btn-primary-gradient rounded-pill px-3 py-1.5 shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Jadwal
                    </button>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr style="font-size: 0.8rem; text-transform: uppercase;">
                            <th class="ps-4" style="width: 130px;">Jam Ke</th>
                            <th style="width: 140px;">Waktu Mulai</th>
                            <th style="width: 140px;">Waktu Selesai</th>
                            <th>Mata Pelajaran</th>
                            <th>Nama Pengajar</th>
                            <th class="text-center pe-4" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($slotRows as $idx => $slot)
                        @php
                            $j = $slot['jadwal'];
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <input type="hidden" name="items[{{ $idx }}][jam_belajar_id]" value="{{ $slot['jam_belajar_id'] }}">
                                <input type="hidden" name="items[{{ $idx }}][jam_ke]" value="{{ $slot['jam_ke'] }}">
                                <input type="hidden" name="items[{{ $idx }}][waktu_mulai]" value="{{ substr($slot['waktu_mulai'], 0, 5) }}">
                                <input type="hidden" name="items[{{ $idx }}][waktu_selesai]" value="{{ substr($slot['waktu_selesai'], 0, 5) }}">

                                <span class="badge bg-indigo-subtle text-primary border px-3 py-1 rounded-pill fw-bold" style="background-color: #e0e7ff; color: #4338ca;">
                                    Jam ke-{{ $slot['jam_ke'] }}
                                </span>
                                @if($j)
                                <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                                    {{ $j->hari }} • {{ $j->dataKelas ? 'Kelas '.$j->dataKelas->kelas : 'Kelas #'.$j->kelas }}
                                </div>
                                @endif
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">
                                    <i class="bi bi-clock text-success me-1"></i> {{ substr($slot['waktu_mulai'], 0, 5) }} WIB
                                </span>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">
                                    <i class="bi bi-clock-history text-danger me-1"></i> {{ substr($slot['waktu_selesai'], 0, 5) }} WIB
                                </span>
                            </td>
                            <td>
                                <select name="items[{{ $idx }}][mapel_id]" class="form-select form-select-sm select-row-mapel">
                                    <option value=""></option>
                                    @foreach($mapels as $m)
                                    <option value="{{ $m->id }}"
                                        data-pengajar="{{ $m->nama_pengajar }}"
                                        {{ ($j && ((int) $j->mapel_id === (int) $m->id || $j->mata_pelajaran === $m->mata_pelajaran)) ? 'selected' : '' }}>
                                        {{ $m->mata_pelajaran }}
                                    </option>
                                    @endforeach
                                </select>
                                @if($j)
                                <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                                    Tersimpan: <strong class="text-dark">{{ $j->mata_pelajaran }}</strong>
                                    @if($j->tampil_mapel && $j->tampil_mapel !== $j->mata_pelajaran)
                                        <span class="badge bg-primary-subtle text-primary border ms-1">{{ $j->tampil_mapel }}</span>
                                    @endif
                                </div>
                                @endif
                            </td>
                            <td>
                                <select name="items[{{ $idx }}][nama_pengajar]" class="form-select form-select-sm select-row-pengajar">
                                    <option value=""></option>
                                    @foreach($daftarPengajar as $u)
                                    <option value="{{ $u->id }}" {{ ($j && (int) $j->nama_pengajar === (int) $u->id) ? 'selected' : '' }}>
                                        {{ $u->name ?: $u->username }}
                                    </option>
                                    @endforeach
                                </select>
                                @if($j && $j->pengajar)
                                <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                                    Pengajar: <strong class="text-dark">{{ $j->pengajar->name ?: $j->pengajar->username }}</strong>
                                </div>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                @if($j)
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-warning" title="Edit Jadwal"
                                        data-bs-toggle="modal" data-bs-target="#modalEditJadwal{{ $j->id }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" title="Hapus Jadwal"
                                        data-bs-toggle="modal" data-bs-target="#modalHapusJadwal{{ $j->id }}">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                                @else
                                <span class="badge bg-light text-secondary border">Belum Diisi</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar2-x fs-1 text-muted d-block mb-2"></i>
                                Belum ada data Waktu Belajar (<code>jam_belajars</code>). Silakan tambahkan waktu belajar terlebih dahulu atau klik <strong>Tambah Jadwal</strong>.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($slotRows->isNotEmpty() && $daftarKelas->isNotEmpty())
            <div class="p-3 bg-light border-top d-flex justify-content-end">
                <button type="submit" class="btn btn-primary-gradient rounded-pill px-4 py-2 shadow-sm">
                    <i class="bi bi-save me-1"></i> Simpan Jadwal Pelajaran
                </button>
            </div>
            @endif
        </form>
    </div>

    <!-- Modals Edit & Hapus Jadwal -->
    @foreach($jadwals as $j)
    <div class="modal fade" id="modalEditJadwal{{ $j->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow rounded-4 text-start">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi bi-pencil-square text-warning me-2"></i> Edit Jadwal Pelajaran
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('jadwal.update', $j->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Tahun Pelajaran <span class="text-danger">*</span></label>
                                <select name="tahun_pelajaran" class="form-select" required>
                                    @foreach($daftarTahun as $tp)
                                    <option value="{{ $tp }}" {{ $j->tahun_pelajaran === $tp ? 'selected' : '' }}>{{ $tp }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Semester <span class="text-danger">*</span></label>
                                <select name="semester" class="form-select" required>
                                    @foreach($daftarSemester as $sem)
                                    <option value="{{ $sem }}" {{ $j->semester === $sem ? 'selected' : '' }}>{{ $sem }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Kelas <span class="text-danger">*</span></label>
                                <select name="kelas" class="form-select" required>
                                    @foreach($daftarKelas as $k)
                                    <option value="{{ $k->id }}" {{ (int) $j->kelas === $k->id ? 'selected' : '' }}>
                                        Kelas {{ $k->kelas }} (TP {{ $k->tahun_pelajaran }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Hari <span class="text-danger">*</span></label>
                                <select name="hari" class="form-select" required>
                                    @foreach($daftarHari as $h)
                                    <option value="{{ $h }}" {{ $j->hari === $h ? 'selected' : '' }}>{{ $h }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Pilih dari Jam Belajar</label>
                                <select name="jam_belajar_id" class="form-select select-jam-belajar">
                                    <option value="">-- Sesuaikan Manual / Pilih --</option>
                                    @foreach($jamBelajars as $jb)
                                    <option value="{{ $jb->id }}"
                                        data-jam-ke="{{ $jb->jam_ke }}"
                                        data-mulai="{{ substr($jb->waktu_mulai, 0, 5) }}"
                                        data-selesai="{{ substr($jb->waktu_selesai, 0, 5) }}"
                                        {{ (int) $j->jam_belajar_id === $jb->id ? 'selected' : '' }}>
                                        Jam ke-{{ $jb->jam_ke }} ({{ substr($jb->waktu_mulai, 0, 5) }} - {{ substr($jb->waktu_selesai, 0, 5) }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Jam Ke <span class="text-danger">*</span></label>
                                <input type="text" name="jam_ke" class="form-control input-jam-ke" value="{{ $j->jam_ke }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Waktu Mulai <span class="text-danger">*</span></label>
                                <input type="time" name="waktu_mulai" class="form-control input-waktu-mulai" value="{{ substr($j->waktu_mulai, 0, 5) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Waktu Selesai <span class="text-danger">*</span></label>
                                <input type="time" name="waktu_selesai" class="form-control input-waktu-selesai" value="{{ substr($j->waktu_selesai, 0, 5) }}" required>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Mata Pelajaran <span class="text-danger">*</span></label>
                                <select name="mapel_id" class="form-select select-mapel" required>
                                    <option value=""></option>
                                    @foreach($mapels as $m)
                                    <option value="{{ $m->id }}"
                                        data-mapel="{{ $m->mata_pelajaran }}"
                                        data-pengajar="{{ $m->nama_pengajar }}"
                                        {{ ((int) $j->mapel_id === (int) $m->id || $j->mata_pelajaran === $m->mata_pelajaran) ? 'selected' : '' }}>
                                        {{ $m->mata_pelajaran }}
                                    </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="mata_pelajaran" class="input-mata-pelajaran" value="{{ $j->mata_pelajaran }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nama Pengajar</label>
                                <select name="nama_pengajar" class="form-select select-nama-pengajar">
                                    <option value=""></option>
                                    @foreach($daftarPengajar as $u)
                                    <option value="{{ $u->id }}" {{ (int) $j->nama_pengajar === $u->id ? 'selected' : '' }}>
                                        {{ $u->name ?: $u->username }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light py-3">
                        <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning rounded-pill px-4 fw-semibold">Perbarui Jadwal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalHapusJadwal{{ $j->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow rounded-4 text-center p-3">
                <div class="modal-body">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-1 mb-2 d-block"></i>
                    <h5 class="fw-bold text-dark mb-1">Hapus Jadwal?</h5>
                    <p class="text-muted small mb-3">
                        Hapus jadwal <strong>{{ $j->mata_pelajaran }}</strong> (Hari {{ $j->hari }}, Jam ke-{{ $j->jam_ke }})?
                    </p>
                    <form action="{{ route('jadwal.destroy', $j->id) }}" method="POST">
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

<!-- Modal Tambah Jadwal Pelajaran -->
<div class="modal fade" id="modalTambahJadwal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="bi bi-plus-circle text-primary me-2"></i> Tambah Jadwal Pelajaran
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('jadwal.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Tahun Pelajaran <span class="text-danger">*</span></label>
                            <select name="tahun_pelajaran" class="form-select" required>
                                @foreach($daftarTahun as $tp)
                                <option value="{{ $tp }}" {{ (string) $tahunFilter === (string) $tp ? 'selected' : '' }}>{{ $tp }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Semester <span class="text-danger">*</span></label>
                            <select name="semester" class="form-select" required>
                                @foreach($daftarSemester as $sem)
                                <option value="{{ $sem }}" {{ (string) $semesterFilter === (string) $sem ? 'selected' : '' }}>{{ $sem }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Kelas <span class="text-danger">*</span></label>
                            <select name="kelas" class="form-select" required>
                                <option value="">-- Pilih Kelas --</option>
                                @foreach($daftarKelas as $k)
                                <option value="{{ $k->id }}" {{ (string) $kelasFilter === (string) $k->id ? 'selected' : '' }}>
                                    Kelas {{ $k->kelas }} (TP {{ $k->tahun_pelajaran }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Hari <span class="text-danger">*</span></label>
                            <select name="hari" class="form-select" required>
                                @foreach($daftarHari as $h)
                                <option value="{{ $h }}" {{ (string) $hariFilter === (string) $h ? 'selected' : '' }}>{{ $h }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Pilih dari Jam Belajar (`jam_belajars`)</label>
                            <select name="jam_belajar_id" class="form-select select-jam-belajar">
                                <option value="">-- Pilih Jam Belajar --</option>
                                @foreach($jamBelajars as $jb)
                                <option value="{{ $jb->id }}"
                                    data-jam-ke="{{ $jb->jam_ke }}"
                                    data-mulai="{{ substr($jb->waktu_mulai, 0, 5) }}"
                                    data-selesai="{{ substr($jb->waktu_selesai, 0, 5) }}">
                                    Jam ke-{{ $jb->jam_ke }} ({{ substr($jb->waktu_mulai, 0, 5) }} - {{ substr($jb->waktu_selesai, 0, 5) }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Jam Ke <span class="text-danger">*</span></label>
                            <input type="text" name="jam_ke" class="form-control input-jam-ke" placeholder="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Waktu Mulai <span class="text-danger">*</span></label>
                            <input type="time" name="waktu_mulai" class="form-control input-waktu-mulai" value="07:15" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Waktu Selesai <span class="text-danger">*</span></label>
                            <input type="time" name="waktu_selesai" class="form-control input-waktu-selesai" value="08:00" required>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Mata Pelajaran <span class="text-danger">*</span></label>
                            <select name="mapel_id" class="form-select select-mapel" required>
                                <option value=""></option>
                                @foreach($mapels as $m)
                                <option value="{{ $m->id }}"
                                    data-mapel="{{ $m->mata_pelajaran }}"
                                    data-pengajar="{{ $m->nama_pengajar }}">
                                    {{ $m->mata_pelajaran }}
                                </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="mata_pelajaran" class="input-mata-pelajaran" value="">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nama Pengajar</label>
                            <select name="nama_pengajar" class="form-select select-nama-pengajar">
                                <option value=""></option>
                                @foreach($daftarPengajar as $u)
                                <option value="{{ $u->id }}">
                                    {{ $u->name ?: $u->username }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-3">
                    <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-gradient rounded-pill px-4">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.select-jam-belajar').forEach(function(selectEl) {
            selectEl.addEventListener('change', function() {
                const option = this.options[this.selectedIndex];
                const modal = this.closest('.modal-body');
                if (!modal || !option.value) return;

                const jamKeInput = modal.querySelector('.input-jam-ke');
                const mulaiInput = modal.querySelector('.input-waktu-mulai');
                const selesaiInput = modal.querySelector('.input-waktu-selesai');

                if (jamKeInput) jamKeInput.value = option.getAttribute('data-jam-ke') || '';
                if (mulaiInput) mulaiInput.value = option.getAttribute('data-mulai') || '';
                if (selesaiInput) selesaiInput.value = option.getAttribute('data-selesai') || '';
            });
        });

        document.querySelectorAll('.select-mapel').forEach(function(selectEl) {
            selectEl.addEventListener('change', function() {
                const option = this.options[this.selectedIndex];
                const modal = this.closest('.modal-body');
                if (!modal || !option.value) return;

                const mapelInput = modal.querySelector('.input-mata-pelajaran');
                const pengajarSelect = modal.querySelector('.select-nama-pengajar');

                if (mapelInput) mapelInput.value = option.getAttribute('data-mapel') || '';
                const pengajarId = option.getAttribute('data-pengajar');
                if (pengajarSelect && pengajarId) {
                    pengajarSelect.value = pengajarId;
                }
            });
        });

        document.querySelectorAll('.select-row-mapel').forEach(function(selectEl) {
            selectEl.addEventListener('change', function() {
                const option = this.options[this.selectedIndex];
                const row = this.closest('tr');
                if (!row || !option.value) return;

                const pengajarSelect = row.querySelector('.select-row-pengajar');
                const pengajarId = option.getAttribute('data-pengajar');
                if (pengajarSelect && pengajarId && !pengajarSelect.value) {
                    pengajarSelect.value = pengajarId;
                }
            });
        });
    });
</script>
@endpush
