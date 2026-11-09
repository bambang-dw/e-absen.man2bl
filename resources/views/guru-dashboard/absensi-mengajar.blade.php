@extends('layouts.admin', [
    'title' => 'Absensi Mengajar Guru',
    'pageTitle' => 'Absensi Mengajar Guru',
    'pageSubtitle' => 'Scan QR kelas, pilih jam mengajar, lalu isi materi pembelajaran',
])

@section('content')
    <div class="container-fluid px-0">

        <!-- Data Mapel & Jadwal Tersembunyi (Formatter-Proof DOM Data) untuk Auto-Fill Mata Pelajaran Berdasarkan Kelas -->
        <div id="mapelDataList" class="d-none">
            @foreach ($jadwalsHariIni as $j)
                <div class="mapel-data-item" data-id="jadwal_{{ $j->id }}" data-kelas-id="{{ $j->kelas }}"
                    data-nama-kelas="{{ $j->dataKelas?->kelas }}" data-mapel="{{ $j->mata_pelajaran }}"
                    data-pengajar-id="{{ $j->nama_pengajar }}"
                    data-pengajar-nama="{{ $j->pengajar ? ($j->pengajar->name ?: $j->pengajar->username) : '' }}"
                    data-is-mine="{{ $user && (int) $j->nama_pengajar === (int) $user->id ? '1' : '0' }}">
                </div>
            @endforeach
            @foreach ($mapels as $m)
                <div class="mapel-data-item" data-id="mapel_{{ $m->id }}" data-kelas-id="{{ $m->kelas }}"
                    data-nama-kelas="{{ $m->dataKelas?->kelas }}" data-mapel="{{ $m->mata_pelajaran }}"
                    data-pengajar-id="{{ $m->nama_pengajar }}"
                    data-pengajar-nama="{{ $m->pengajar ? ($m->pengajar->name ?: $m->pengajar->username) : '' }}"
                    data-is-mine="{{ $user && (int) $m->nama_pengajar === (int) $user->id ? '1' : '0' }}">
                </div>
            @endforeach
        </div>

        <div class="row g-4 mb-4">
            <!-- Kolom Kiri: Form Absensi Mengajar (Kelas, Jam Ke Multi-Select, Mapel Otomatis, Materi) -->
            <div class="col-lg-7">
                <div class="card-custom p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">
                                <i class="bi bi-easel2-fill text-primary me-2"></i> Form Absensi Mengajar
                            </h5>
                            <small class="text-muted">Hari {{ $hariIni }},
                                {{ \Carbon\Carbon::parse($today)->translatedFormat('d F Y') }}</small>
                        </div>
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 fw-semibold">
                            <i class="bi bi-person-workspace me-1"></i>
                            {{ $user ? ($user->name ?: $user->username) : 'Guru Pengajar' }}
                        </span>
                    </div>

                    <!-- Notifikasi Hasil Scan / Submit Live -->

                    <form id="formAbsensiMengajar" action="{{ route('guru.absensi-mengajar.store') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="hari" value="{{ $hariIni }}">
                        <input type="hidden" name="tanggal" value="{{ $today }}">
                        <input type="hidden" name="qr_kelas" id="inputQrKelas" value="{{ old('qr_kelas') }}">

                        <div id="kelasQrDataList" class="d-none">
                            @foreach ($daftarKelas as $kelas)
                                <span class="kelas-qr-data" data-id="{{ $kelas->id }}"
                                    data-nama="{{ $kelas->kelas }}"></span>
                            @endforeach
                        </div>

                        <!-- 1. Scan QR Kelas -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold text-dark mb-0">
                                    1. Scan QR Code Kelas <span class="text-danger">*</span>
                                </label>
                                <span id="cameraBadgeStatus" class="badge bg-secondary rounded-pill px-3 py-1">Kamera
                                    Siap</span>
                            </div>
                            <div class="position-relative rounded-4 overflow-hidden border bg-dark mb-2"
                                style="min-height: 270px;">
                                <div id="readerKelas" style="width: 100%; min-height: 270px;"></div>
                                <div id="cameraPlaceholder"
                                    class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white p-3 text-center"
                                    style="background: rgba(15, 23, 42, 0.88);">
                                    <i class="bi bi-camera-video-fill fs-1 mb-2" style="color: #34d399;"></i>
                                    <p class="small text-white-50 mb-2">Arahkan kamera ke QR Code kelas untuk mendeteksi
                                        kelas.</p>
                                    <button type="button" id="btnStartCameraKelas"
                                        class="btn btn-success rounded-pill px-4 py-2 fw-bold shadow">
                                        <i class="bi bi-camera-fill me-1"></i> Aktifkan Kamera QR Kelas
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 justify-content-center mb-2">
                                <button type="button" id="btnSwitchCameraKelas"
                                    class="btn btn-sm btn-outline-primary rounded-pill px-3 d-none">
                                    <i class="bi bi-arrow-repeat me-1"></i> Ganti Kamera
                                </button>
                                <button type="button" id="btnStopCameraKelas"
                                    class="btn btn-sm btn-outline-danger rounded-pill px-3 d-none">
                                    <i class="bi bi-stop-circle me-1"></i> Matikan Kamera
                                </button>
                            </div>
                            <div id="kelasDetectedInfo" class="alert alert-info py-2 mb-0">
                                <span id="kelasDetectedMessage">Scan QR Code kelas terlebih dahulu.</span>
                                @if ($isAdmin)
                                    <div class="mt-2">
                                        <label for="selectKelasMengajar" class="form-label fw-semibold mb-1">Pilih kelas
                                            (admin)</label>
                                        <select name="kelas" id="selectKelasMengajar" class="form-select" required>
                                            <option value="">-- Pilih Kelas --</option>
                                            @foreach ($daftarKelas as $kelas)
                                                <option value="{{ $kelas->id }}" data-nama-kelas="{{ $kelas->kelas }}"
                                                    {{ (string) old('kelas') === (string) $kelas->id ? 'selected' : '' }}>
                                                    Kelas {{ $kelas->kelas }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @else
                                    <input type="hidden" name="kelas" id="inputKelasMengajar"
                                        value="{{ old('kelas') }}">
                                @endif
                            </div>
                        </div>

                        <!-- 2. Pilih Jam Ke -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold text-dark mb-0">
                                    2. Pilih Jam Ke <span class="text-danger">*</span>
                                    <span class="badge bg-info-subtle text-info rounded-pill ms-1"
                                        style="font-size: 0.72rem;">Bisa pilih lebih dari 1</span>
                                </label>
                                <button type="button" id="btnResetJamKe"
                                    class="btn btn-link btn-sm p-0 text-decoration-none text-muted">
                                    Reset Pilihan Jam
                                </button>
                            </div>

                            <div class="row g-2" id="jamKeCheckboxGroup" data-admin="{{ $isAdmin ? '1' : '0' }}"
                                data-server-time="{{ now()->format('H:i:s') }}">
                                @foreach ($jamKePilihan as $jb)
                                    <div class="col-6 col-sm-4 col-md-3 jam-ke-option {{ $isAdmin ? '' : 'd-none' }}"
                                        data-kelas-ids="{{ implode(',', $jb['kelas_ids']) }}"
                                        data-waktu-mulai="{{ $jb['waktu_mulai'] }}"
                                        data-waktu-selesai="{{ $jb['waktu_selesai'] }}">
                                        <label class="w-100 m-0">
                                            <input type="checkbox" name="jam_ke[]" value="{{ $jb['jam_ke'] }}"
                                                class="btn-check jam-ke-checkbox" {{ $isAdmin ? '' : 'disabled' }}>
                                            <div
                                                class="btn btn-outline-primary w-100 py-2 px-2 rounded-3 text-center d-flex flex-column align-items-center justify-content-center h-100">
                                                <span class="fw-bold" style="font-size: 0.9rem;">Jam
                                                    ke-{{ $jb['jam_ke'] }}</span>
                                                <small style="font-size: 0.7rem; opacity: 0.85;">
                                                    {{ $jb['waktu_mulai'] ? substr($jb['waktu_mulai'], 0, 5) : '' }}
                                                    @if ($jb['waktu_mulai'] && $jb['waktu_selesai'])
                                                        -
                                                    @endif
                                                    {{ $jb['waktu_selesai'] ? substr($jb['waktu_selesai'], 0, 5) : '' }}
                                                </small>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <small id="jamKeEmptyState" class="text-warning d-block mt-2 {{ $isAdmin ? 'd-none' : '' }}">
                                Pilih kelas terlebih dahulu.
                            </small>
                            <div id="jamKeWaktuWarning" class="alert alert-danger d-none mt-2 mb-0 py-2" role="alert">
                            </div>
                            <small class="text-muted d-block mt-1">
                                Terpilih: <strong id="selectedJamKePreview" class="text-primary">- (Belum
                                    dipilih)</strong>
                            </small>
                        </div>

                        <!-- 3. Mata Pelajaran (Otomatis muncul berdasarkan tabel kelas dan mapel) -->
                        <div class="mb-3">
                            <label for="inputMataPelajaran" class="form-label fw-bold text-dark">
                                3. Mata Pelajaran <span class="text-danger">*</span>
                                <span class="badge bg-success-subtle text-success rounded-pill ms-1"
                                    style="font-size: 0.72rem;">
                                    <i class="bi bi-shield-lock-fill me-1"></i>Sesuai Jadwal Anda
                                </span>
                            </label>
                            <input type="text" name="mata_pelajaran" id="inputMataPelajaran"
                                class="form-control fw-bold text-primary"
                                placeholder="Otomatis terisi dari jadwal Anda"
                                value="{{ old('mata_pelajaran') }}" readonly aria-readonly="true" required>
                            <small id="infoPengajarMapel" class="text-muted d-block mt-1">
                                Hanya menampilkan mata pelajaran yang Anda ampu sesuai jadwal mengajar.
                            </small>
                        </div>

                        <!-- 4. Materi Pembelajaran / Tugas (kehadiran dicatat otomatis dari scan QR Code Kelas) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">
                                4. Materi Pembelajaran / Tugas <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="materi_pembelajaran" id="inputMateriPembelajaran"
                                class="form-control" placeholder="Contoh: Persamaan Kuadrat / Tugas Bab 2..."
                                value="{{ old('materi_pembelajaran') }}" required>
                            <small class="text-muted">
                                Kehadiran mengajar dicatat setelah QR kelas dikenali dan form disimpan. Tambahkan kata
                                <strong>Tugas</strong> pada materi bila jam ini berisi penugasan.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">
                                5. Ambil Gambar Kelas
                                @unless ($isAdmin)
                                    <span class="text-danger">*</span>
                                @else
                                    <span class="text-muted fw-normal">(opsional)</span>
                                @endunless
                            </label>
                            <div class="position-relative rounded-4 overflow-hidden border bg-dark mb-2"
                                style="min-height: 240px;">
                                <video id="videoFotoKelas" class="w-100 d-none" autoplay playsinline
                                    style="min-height: 240px; object-fit: cover;"></video>
                                <img id="previewFotoKelas" class="w-100 d-none" alt="Pratinjau foto kelas"
                                    style="max-height: 360px; object-fit: contain;">
                                <div id="placeholderFotoKelas"
                                    class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white p-3 text-center"
                                    style="background: rgba(15, 23, 42, 0.88);">
                                    <i class="bi bi-camera-fill fs-1 mb-2" style="color: #34d399;"></i>
                                    <p class="small text-white-50 mb-0">
                                        @unless ($isAdmin)
                                            Ambil foto kelas setelah mengisi materi pembelajaran. Foto wajib untuk
                                            menyimpan absensi.
                                        @else
                                            Ambil foto kelas setelah mengisi materi pembelajaran.
                                        @endunless
                                    </p>
                                </div>
                            </div>
                            <input type="file" name="foto" id="inputFotoKelas" accept="image/jpeg,image/png,image/webp"
                                class="d-none">
                            <canvas id="canvasFotoKelas" class="d-none"></canvas>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" id="btnStartCameraFotoKelas"
                                    class="btn btn-outline-success rounded-pill px-3" disabled>
                                    <i class="bi bi-camera-video me-1"></i> Aktifkan Kamera Foto Kelas
                                </button>
                                <button type="button" id="btnCaptureFotoKelas"
                                    class="btn btn-success rounded-pill px-3 d-none">
                                    <i class="bi bi-camera-fill me-1"></i> Ambil Gambar
                                </button>
                                <button type="button" id="btnRetakeFotoKelas"
                                    class="btn btn-outline-secondary rounded-pill px-3 d-none">
                                    <i class="bi bi-arrow-repeat me-1"></i> Ambil Ulang
                                </button>
                            </div>
                            <small id="statusFotoKelas" class="text-muted d-block mt-2">
                                Isi materi pembelajaran untuk mengaktifkan kamera.
                            </small>
                        </div>

                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center pt-2 border-top">
                            <small class="text-muted">
                                <i class="bi bi-camera-video text-success me-1"></i> Pastikan kelas terdeteksi, materi
                                diisi, dan foto kelas diambil sebelum menyimpan.
                            </small>
                            <button type="submit" id="btnSubmitAbsensiMengajar"
                                class="btn btn-danger rounded-pill px-4 py-2 fw-bold shadow-sm" disabled>
                                <i class="bi bi-check2-circle me-1"></i> Simpan Absensi Mengajar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Kolom Kanan: Jadwal Mengajar Mingguan -->
            <div class="col-lg-5">
                <div class="card-custom p-4 h-100 d-flex flex-column justify-content-between">
                    <!-- Jadwal Mengajar Guru Selama Seminggu (Senin - Jumat) -->
                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold text-dark mb-0">
                                <i class="bi bi-calendar2-week-fill text-success me-1"></i> Jadwal Mengajar Minggu Ini
                            </h6>
                            <span class="badge bg-light text-muted border rounded-pill" style="font-size: 0.7rem;">Senin -
                                Jumat</span>
                        </div>

                        @php($totalJadwalMinggu = collect($jadwalMingguan ?? [])->sum(fn($h) => count($h['items'])))

                        <div class="d-flex flex-column gap-2" style="max-height: 340px; overflow-y: auto;">
                            @foreach ($jadwalMingguan ?? [] as $h)
                                <div class="rounded-3 border bg-white p-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold small {{ $h['is_today'] ? 'text-success' : 'text-dark' }}">
                                            {{ $h['hari'] }}
                                            <span class="text-muted fw-normal">({{ $h['tanggal_label'] }})</span>
                                            @if ($h['is_today'])
                                                <span class="badge bg-success rounded-pill ms-1"
                                                    style="font-size: 0.62rem;">Hari Ini</span>
                                            @endif
                                        </span>
                                        <span class="text-muted" style="font-size: 0.68rem;">{{ count($h['items']) }}
                                            jam</span>
                                    </div>

                                    @if (count($h['items']) > 0)
                                        <div class="d-flex flex-column gap-1">
                                            @foreach ($h['items'] as $item)
                                                <div class="d-flex align-items-center gap-2 rounded-2 px-2 py-1"
                                                    style="background-color: #f8fafc;">
                                                    <span
                                                        class="badge bg-success-subtle text-success rounded-pill flex-shrink-0"
                                                        style="font-size: 0.62rem;">Jam {{ $item['jam_ke'] }}</span>
                                                    <span class="fw-semibold text-dark text-truncate"
                                                        style="font-size: 0.72rem;"
                                                        title="{{ $item['mapel_lengkap'] }}">{{ $item['mapel'] }}</span>
                                                    <span class="text-muted text-truncate ms-auto"
                                                        style="font-size: 0.68rem;">{{ $item['kelas'] }}</span>
                                                    <span class="text-muted flex-shrink-0"
                                                        style="font-size: 0.64rem;">{{ $item['waktu'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-muted small fst-italic ps-1">Tidak ada jadwal mengajar</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if ($totalJadwalMinggu === 0)
                            <div class="alert alert-light border text-muted small mb-0 mt-2 py-2">
                                <i class="bi bi-info-circle me-1"></i> Jadwal mengajar belum tersedia untuk akun ini.
                                Pastikan Anda sudah ditetapkan sebagai pengajar pada menu <strong>Jadwal</strong>.
                            </div>
                        @endif
                    </div>

                    <div class="p-3 rounded-3 bg-light border small text-muted mt-3">
                        <div class="fw-bold text-dark mb-1">
                            <i class="bi bi-info-circle-fill text-primary me-1"></i> Cara Kerja Absensi Mengajar:
                        </div>
                        <ol class="mb-0 ps-3">
                            <li>Aktifkan kamera dan pindai <strong>QR Code Kelas</strong> untuk mendeteksi kelas.</li>
                            <li>Centang <strong>Jam Ke</strong>, isi materi pembelajaran, lalu ambil foto kelas.</li>
                            <li><strong>Mata Pelajaran</strong> terisi otomatis sesuai jadwal mengajar Anda.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        @if ($user && $user->hasRole('admin'))
            <!-- Tabel Riwayat Absensi Mengajar Hari Ini -->
            <div class="card-custom p-0 overflow-hidden">
                <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-clock-history text-primary me-2"></i> Riwayat Absensi Mengajar Hari Ini
                        ({{ \Carbon\Carbon::parse($today)->translatedFormat('d F Y') }})
                    </h6>
                    <span class="badge bg-success rounded-pill px-3 py-1" id="badgeCountRiwayat">
                        {{ $riwayatMengajar->count() }} Sesi Tercatat
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.86rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Waktu</th>
                                <th>Kelas</th>
                                <th>Jam Ke</th>
                                <th>Mata Pelajaran</th>
                                <th>Nama Guru</th>
                                <th>Materi Pembelajaran</th>
                                <th>Foto Kelas</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyRiwayatMengajar">
                            @forelse($riwayatMengajar as $rm)
                                <tr>
                                    <td class="ps-4 text-muted fw-semibold">{{ $rm->created_at?->format('H:i') ?? '-' }}
                                        WIB</td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info border rounded-pill px-3 py-1 fw-bold">
                                            Kelas {{ $rm->dataKelas?->kelas ?? $rm->kelas }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 fw-bold">
                                            Jam ke-{{ $rm->jam_ke }}
                                        </span>
                                    </td>
                                    <td class="fw-bold text-dark">{{ $rm->mata_pelajaran }}</td>
                                    <td>{{ $rm->nama_guru_tampil }}</td>
                                    <td class="text-muted">{{ $rm->materi_pembelajaran ?: '-' }}</td>
                                    <td class="text-nowrap">
                                        @if ($rm->foto)
                                            <a href="{{ $rm->foto_url }}" target="_blank" rel="noopener noreferrer">
                                                <i class="bi bi-image me-1"></i>{{ basename($rm->foto) }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($rm->hasTugas())
                                            <span class="badge rounded-pill px-3 py-1 fw-bold badge-tugas-cyan"
                                                style="background-color: #06b6d4; color: #ffffff;">
                                                <i class="bi bi-journal-check me-1"></i> {{ $rm->status }}
                                            </span>
                                        @elseif($rm->isHadir())
                                            <span class="badge bg-success rounded-pill px-3 py-1">
                                                <i class="bi bi-check-circle-fill me-1"></i> {{ $rm->status }}
                                            </span>
                                        @else
                                            <span class="badge bg-danger rounded-pill px-3 py-1">
                                                {{ $rm->status }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr id="emptyRowRiwayat">
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        Belum ada catatan absensi mengajar yang terekam hari ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectKelas = document.getElementById('selectKelasMengajar');
            const inputKelas = document.getElementById('inputKelasMengajar');
            const kelasDetectedMessage = document.getElementById('kelasDetectedMessage');
            const kelasDetectedInfo = document.getElementById('kelasDetectedInfo');
            const jamKeEmptyState = document.getElementById('jamKeEmptyState');
            const jamKeWaktuWarning = document.getElementById('jamKeWaktuWarning');
            const inputMataPelajaran = document.getElementById('inputMataPelajaran');
            const inputMateriPembelajaran = document.getElementById('inputMateriPembelajaran');
            const btnSubmitAbsensi = document.getElementById('btnSubmitAbsensiMengajar');
            const inputFotoKelas = document.getElementById('inputFotoKelas');
            const videoFotoKelas = document.getElementById('videoFotoKelas');
            const previewFotoKelas = document.getElementById('previewFotoKelas');
            const canvasFotoKelas = document.getElementById('canvasFotoKelas');
            const placeholderFotoKelas = document.getElementById('placeholderFotoKelas');
            const btnStartCameraFotoKelas = document.getElementById('btnStartCameraFotoKelas');
            const btnCaptureFotoKelas = document.getElementById('btnCaptureFotoKelas');
            const btnRetakeFotoKelas = document.getElementById('btnRetakeFotoKelas');
            const statusFotoKelas = document.getElementById('statusFotoKelas');
            const infoPengajarMapel = document.getElementById('infoPengajarMapel');
            const selectedJamKePreview = document.getElementById('selectedJamKePreview');
            const btnResetJamKe = document.getElementById('btnResetJamKe');
            const inputQrKelas = document.getElementById('inputQrKelas');
            const formAbsensi = document.getElementById('formAbsensiMengajar');
            const feedbackAlert = document.getElementById('scanFeedbackAlert');
            const jamKeCheckboxGroup = document.getElementById('jamKeCheckboxGroup');
            const isAdmin = jamKeCheckboxGroup.dataset.admin === '1';
            const kelasQrData = Array.from(document.querySelectorAll('#kelasQrDataList .kelas-qr-data'));
            const serverTimeAtLoad = jamKeCheckboxGroup.dataset.serverTime;
            const browserTimeAtLoad = Date.now();

            function timeToSeconds(time) {
                const parts = String(time || '').split(':').map(Number);
                return (parts[0] * 3600) + (parts[1] * 60) + (parts[2] || 0);
            }

            function getCurrentServerTimeSeconds() {
                const elapsedSeconds = Math.floor((Date.now() - browserTimeAtLoad) / 1000);

                return (timeToSeconds(serverTimeAtLoad) + elapsedSeconds) % 86400;
            }

            // 1. Kumpulkan seluruh data mapel dari DOM
            const allMapels = Array.from(document.querySelectorAll('#mapelDataList .mapel-data-item')).map(function(
                el) {
                return {
                    id: el.getAttribute('data-id'),
                    kelasId: String(el.getAttribute('data-kelas-id') || ''),
                    namaKelas: el.getAttribute('data-nama-kelas') || '',
                    mapel: el.getAttribute('data-mapel') || '',
                    pengajarId: el.getAttribute('data-pengajar-id') || '',
                    pengajarNama: el.getAttribute('data-pengajar-nama') || '',
                    isMine: el.getAttribute('data-is-mine') === '1'
                };
            });

            // 2. Fungsi untuk memperbarui pilihan Mata Pelajaran otomatis saat Kelas berubah
            function updateMapelBySelectedKelas(kelasId) {
                if (!kelasId) {
                    inputMataPelajaran.value = '';
                    infoPengajarMapel.textContent = 'Pilih kelas untuk melihat mata pelajaran sesuai jadwal.';
                    return;
                }

                // Hanya mapel pada kelas ini yang memang diampu oleh guru yang login.
                const matched = allMapels.filter(function(m) {
                    return m.kelasId === String(kelasId) && (isAdmin || m.isMine) && m.mapel !== '';
                });

                if (matched.length === 0) {
                    inputMataPelajaran.value = '';
                    infoPengajarMapel.textContent =
                        'Anda tidak memiliki jadwal mengajar di kelas ini. Absensi tidak dapat dicatat.';
                    return;
                }

                const preferred = matched[0];
                inputMataPelajaran.value = preferred.mapel;
                infoPengajarMapel.innerHTML = 'Mata pelajaran Anda di kelas ini: <strong>' + preferred.mapel +
                    '</strong>';
            }

            function updateJamKeBySelectedKelas(kelasId) {
                if (isAdmin) {
                    return;
                }

                const jamKeOptions = Array.from(document.querySelectorAll('.jam-ke-option'));
                let visibleOptions = 0;
                jamKeOptions.forEach(function(option) {
                    const checkbox = option.querySelector('.jam-ke-checkbox');
                    const kelasIds = String(option.dataset.kelasIds || '').split(',');
                    const isAvailable = Boolean(kelasId) && kelasIds.includes(String(kelasId));

                    option.classList.toggle('d-none', !isAvailable);
                    checkbox.disabled = !isAvailable;
                    if (!isAvailable) {
                        checkbox.checked = false;
                    } else {
                        visibleOptions++;
                    }
                });

                if (jamKeEmptyState) {
                    jamKeEmptyState.textContent = !kelasId ?
                        'Pilih kelas terlebih dahulu.' :
                        'Tidak ada jadwal mengajar di kelas ini.';
                    jamKeEmptyState.classList.toggle('d-none', Boolean(kelasId) && visibleOptions > 0);
                }
                updateSelectedJamPreview();
            }

            function setSelectedKelas(kelasId) {
                if (selectKelas) {
                    selectKelas.value = kelasId;
                } else if (inputKelas) {
                    inputKelas.value = kelasId;
                }

                updateMapelBySelectedKelas(kelasId);
                updateJamKeBySelectedKelas(kelasId);
            }

            if (selectKelas) {
                selectKelas.addEventListener('change', function() {
                    setSelectedKelas(this.value);
                });
            }

            // 3. Preview Jam Ke yang dipilih (multi-select)
            function updateSelectedJamPreview() {
                const checkedCheckboxes = Array.from(document.querySelectorAll('.jam-ke-checkbox:checked'));
                const checked = checkedCheckboxes.map(function(cb) {
                    return cb.value;
                });
                if (checked.length === 0) {
                    selectedJamKePreview.textContent = '- (Belum dipilih)';
                } else {
                    selectedJamKePreview.textContent = 'Jam ke-' + checked.join(', ');
                }
                updateJamKeWaktuWarning(checkedCheckboxes);
                updateSubmitButton(checkedCheckboxes);
                return checked;
            }

            function updateSubmitButton(checkedCheckboxes) {
                const kelasDipilih = isAdmin ? Boolean(selectKelas?.value) :
                    Boolean(inputKelas?.value && inputQrKelas.value);
                const waktuSesuai = isAdmin || jamKeWaktuWarning.classList.contains('d-none');
                const isReady = checkedCheckboxes.length > 0 &&
                    kelasDipilih &&
                    inputMataPelajaran.value.trim() !== '' &&
                    inputMateriPembelajaran.value.trim() !== '' &&
                    (isAdmin || inputFotoKelas.files.length > 0) &&
                    waktuSesuai;

                btnSubmitAbsensi.disabled = !isReady;
                btnSubmitAbsensi.classList.toggle('btn-success', isReady);
                btnSubmitAbsensi.classList.toggle('btn-danger', !isReady);
            }

            function updateJamKeWaktuWarning(checkedCheckboxes) {
                if (isAdmin || !jamKeWaktuWarning) {
                    return;
                }

                const selectedSchedules = checkedCheckboxes
                    .map(function(checkbox) {
                        return {
                            checkbox: checkbox,
                            option: checkbox.closest('.jam-ke-option')
                        };
                    })
                    .sort(function(first, second) {
                        return timeToSeconds(first.option.dataset.waktuMulai) -
                            timeToSeconds(second.option.dataset.waktuMulai);
                    });
                const firstSelected = selectedSchedules[0];
                const lastSelected = selectedSchedules[selectedSchedules.length - 1];
                const waktuMulai = firstSelected?.option.dataset.waktuMulai;
                const waktuSelesai = lastSelected?.option.dataset.waktuSelesai;
                const waktuSekarang = getCurrentServerTimeSeconds();

                if (
                    !waktuMulai ||
                    !waktuSelesai ||
                    (waktuSekarang >= timeToSeconds(waktuMulai) && waktuSekarang <= timeToSeconds(waktuSelesai))
                ) {
                    jamKeWaktuWarning.textContent = '';
                    jamKeWaktuWarning.classList.add('d-none');
                    return;
                }

                jamKeWaktuWarning.textContent =
                    'Waktu mengajar tidak sesuai jadwal. Anda tidak bisa melanjutkan Absensi Mengajar.';
                jamKeWaktuWarning.classList.remove('d-none');
            }

            formAbsensi.addEventListener('submit', function(event) {
                updateSelectedJamPreview();
                if (
                    document.querySelectorAll('.jam-ke-checkbox:checked').length === 0 ||
                    !inputMataPelajaran.value.trim() ||
                    !inputMateriPembelajaran.value.trim()
                ) {
                    event.preventDefault();
                    showFeedback('warning', 'Pilih jam mengajar dan isi materi pembelajaran terlebih dahulu.');
                    return;
                }

                if (!isAdmin && (!inputKelas.value || !inputQrKelas.value)) {
                    event.preventDefault();
                    showFeedback('warning', 'Scan QR Code kelas terlebih dahulu.');
                    return;
                }

                if (!isAdmin && !jamKeWaktuWarning.classList.contains('d-none')) {
                    event.preventDefault();
                    showFeedback('danger',
                        'Waktu mengajar tidak sesuai jadwal. Anda tidak bisa melanjutkan Absensi Mengajar.');
                    return;
                }

                if (!isAdmin && inputFotoKelas.files.length === 0) {
                    event.preventDefault();
                    showFeedback('warning', 'Ambil foto kelas terlebih dahulu sebelum menyimpan absensi.');
                }
            });

            document.querySelectorAll('.jam-ke-checkbox').forEach(function(cb) {
                cb.addEventListener('change', updateSelectedJamPreview);
            });

            if (btnResetJamKe) {
                btnResetJamKe.addEventListener('click', function() {
                    document.querySelectorAll('.jam-ke-checkbox').forEach(function(cb) {
                        cb.checked = false;
                    });
                    updateSelectedJamPreview();
                });
            }

            // 4. Kamera Scanner QR Code Kelas (Html5Qrcode)
            let html5QrCode = null;
            let isScanning = false;
            let currentFacingMode = 'environment';
            const btnStartCamera = document.getElementById('btnStartCameraKelas');
            const btnStopCamera = document.getElementById('btnStopCameraKelas');
            const btnSwitchCamera = document.getElementById('btnSwitchCameraKelas');
            const cameraPlaceholder = document.getElementById('cameraPlaceholder');
            const cameraBadge = document.getElementById('cameraBadgeStatus');

            function showFeedback(type, message) {
                if (!feedbackAlert) return;
                feedbackAlert.className = 'alert alert-' + type +
                    ' rounded-4 mb-3 shadow-sm d-flex align-items-center gap-2';
                feedbackAlert.innerHTML = '<i class="bi ' + (type === 'success' ? 'bi-check-circle-fill' :
                    'bi-exclamation-triangle-fill') + ' fs-5"></i><div>' + message + '</div>';
                feedbackAlert.classList.remove('d-none');
            }

            function matchKelasOptionFromQr(decodedText) {
                const raw = String(decodedText || '').trim();
                const normalized = raw.toUpperCase().replace(/\s+/g, '');
                for (let i = 0; i < kelasQrData.length; i++) {
                    const kelas = kelasQrData[i];
                    const idVal = String(kelas.dataset.id);
                    const namaKelas = String(kelas.dataset.nama || '').toUpperCase().replace(/\s+/g, '');
                    if (
                        raw === idVal ||
                        normalized === 'KELAS:' + idVal ||
                        normalized === 'KELAS:' + namaKelas ||
                        normalized === namaKelas
                    ) {
                        return idVal;
                    }
                }
                return null;
            }

            function onQrScanSuccess(decodedText) {
                if (!decodedText) return;
                inputQrKelas.value = decodedText.trim();

                const matchedKelasId = matchKelasOptionFromQr(decodedText);
                if (!matchedKelasId) {
                    showFeedback('warning',
                        'QR Code tidak dikenali. Pastikan Anda memindai QR Code kelas yang terdaftar.');
                    return;
                }

                setSelectedKelas(matchedKelasId);
                const kelasOption = kelasQrData.find(function(kelas) {
                    return String(kelas.dataset.id) === String(matchedKelasId);
                });
                kelasDetectedMessage.textContent = 'Kelas terdeteksi: ' + kelasOption.dataset.nama +
                    '. Silakan pilih jam mengajar.';
                kelasDetectedInfo.classList.remove('alert-info');
                kelasDetectedInfo.classList.add('alert-success');
                showFeedback('success', 'Kelas ' + kelasOption.dataset.nama +
                    ' berhasil dikenali. Silakan pilih jam mengajar dan isi materi pembelajaran.');
            }

            if (!isAdmin && inputKelas.value && inputQrKelas.value) {
                const matchedKelasId = matchKelasOptionFromQr(inputQrKelas.value);
                if (matchedKelasId === inputKelas.value) {
                    setSelectedKelas(matchedKelasId);
                    const kelasOption = kelasQrData.find(function(kelas) {
                        return String(kelas.dataset.id) === String(matchedKelasId);
                    });
                    kelasDetectedMessage.textContent = 'Kelas terdeteksi: ' + kelasOption.dataset.nama + '.';
                    kelasDetectedInfo.classList.remove('alert-info');
                    kelasDetectedInfo.classList.add('alert-success');
                } else {
                    inputKelas.value = '';
                    inputQrKelas.value = '';
                }
            }

            function startCamera() {
                if (typeof Html5Qrcode === 'undefined') {
                    showFeedback('danger', 'Pustaka kamera QR tidak ditemukan.');
                    return;
                }
                if (!html5QrCode) {
                    html5QrCode = new Html5Qrcode('readerKelas');
                }
                cameraPlaceholder.classList.add('d-none');
                cameraBadge.className = 'badge bg-success rounded-pill px-3 py-1';
                cameraBadge.textContent = 'Kamera Aktif';
                btnStopCamera.classList.remove('d-none');
                btnSwitchCamera.classList.remove('d-none');

                html5QrCode.start({
                        facingMode: currentFacingMode
                    }, {
                        fps: 10,
                        qrbox: {
                            width: 220,
                            height: 220
                        }
                    },
                    function(decodedText) {
                        // Hentikan kamera begitu QR terbaca agar QR yang sama tidak terpindai berulang kali
                        stopCamera();
                        onQrScanSuccess(decodedText);
                    },
                    function() {}
                ).then(function() {
                    isScanning = true;
                }).catch(function(err) {
                    cameraPlaceholder.classList.remove('d-none');
                    cameraBadge.className = 'badge bg-danger rounded-pill px-3 py-1';
                    cameraBadge.textContent = 'Gagal Akses Kamera';
                    showFeedback('warning', 'Tidak dapat mengakses kamera (' + err +
                        '). Pastikan izin kamera diaktifkan.');
                });
            }

            function stopCamera() {
                if (html5QrCode && isScanning) {
                    html5QrCode.stop().then(function() {
                        isScanning = false;
                        cameraPlaceholder.classList.remove('d-none');
                        btnStopCamera.classList.add('d-none');
                        btnSwitchCamera.classList.add('d-none');
                        cameraBadge.className = 'badge bg-secondary rounded-pill px-3 py-1';
                        cameraBadge.textContent = 'Kamera Nonaktif';
                    });
                }
            }

            if (btnStartCamera) {
                btnStartCamera.addEventListener('click', startCamera);
            }
            if (btnStopCamera) {
                btnStopCamera.addEventListener('click', stopCamera);
            }
            if (btnSwitchCamera) {
                btnSwitchCamera.addEventListener('click', function() {
                    currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
                    if (html5QrCode && isScanning) {
                        html5QrCode.stop().then(function() {
                            isScanning = false;
                            startCamera();
                        });
                    }
                });
            }

            let fotoKelasStream = null;
            let fotoKelasPreviewUrl = null;

            function stopCameraFotoKelas() {
                if (fotoKelasStream) {
                    fotoKelasStream.getTracks().forEach(function(track) {
                        track.stop();
                    });
                    fotoKelasStream = null;
                }
                videoFotoKelas.srcObject = null;
                videoFotoKelas.classList.add('d-none');
                btnCaptureFotoKelas.classList.add('d-none');
            }

            function startCameraFotoKelas() {
                if (!inputMateriPembelajaran.value.trim()) {
                    showFeedback('warning', 'Isi materi pembelajaran terlebih dahulu untuk mengambil foto kelas.');
                    return;
                }

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    showFeedback('danger', 'Perangkat ini tidak mendukung akses kamera.');
                    return;
                }

                navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: {
                            ideal: 'environment'
                        }
                    },
                    audio: false
                }).then(function(stream) {
                    fotoKelasStream = stream;
                    videoFotoKelas.srcObject = stream;
                    videoFotoKelas.classList.remove('d-none');
                    previewFotoKelas.classList.add('d-none');
                    placeholderFotoKelas.classList.add('d-none');
                    btnStartCameraFotoKelas.classList.add('d-none');
                    btnRetakeFotoKelas.classList.add('d-none');
                    btnCaptureFotoKelas.classList.remove('d-none');
                    statusFotoKelas.textContent = 'Arahkan kamera ke kelas, lalu ambil gambar.';
                    return videoFotoKelas.play();
                }).catch(function(error) {
                    stopCameraFotoKelas();
                    previewFotoKelas.classList.toggle('d-none', inputFotoKelas.files.length === 0);
                    placeholderFotoKelas.classList.toggle('d-none', inputFotoKelas.files.length > 0);
                    btnStartCameraFotoKelas.classList.toggle('d-none', inputFotoKelas.files.length > 0);
                    btnRetakeFotoKelas.classList.toggle('d-none', inputFotoKelas.files.length === 0);
                    statusFotoKelas.textContent = 'Kamera tidak dapat diaktifkan. Coba lagi atau periksa izin kamera.';
                    showFeedback('warning',
                        'Tidak dapat mengakses kamera foto (' + error.message +
                        '). Pastikan izin kamera diaktifkan.');
                });
            }

            function captureFotoKelas() {
                if (!videoFotoKelas.videoWidth || !fotoKelasStream) {
                    showFeedback('warning', 'Kamera belum siap. Coba tunggu sebentar lalu ambil gambar kembali.');
                    return;
                }

                const scale = Math.min(1, 1280 / Math.max(videoFotoKelas.videoWidth, videoFotoKelas.videoHeight));
                canvasFotoKelas.width = Math.round(videoFotoKelas.videoWidth * scale);
                canvasFotoKelas.height = Math.round(videoFotoKelas.videoHeight * scale);
                canvasFotoKelas.getContext('2d').drawImage(
                    videoFotoKelas,
                    0,
                    0,
                    canvasFotoKelas.width,
                    canvasFotoKelas.height
                );
                canvasFotoKelas.toBlob(function(blob) {
                    if (!blob) {
                        showFeedback('danger', 'Foto kelas gagal diproses. Silakan coba kembali.');
                        return;
                    }

                    const foto = new File([blob], 'foto-kelas-' + Date.now() + '.jpg', {
                        type: 'image/jpeg'
                    });
                    const transfer = new DataTransfer();
                    transfer.items.add(foto);
                    inputFotoKelas.files = transfer.files;

                    if (fotoKelasPreviewUrl) {
                        URL.revokeObjectURL(fotoKelasPreviewUrl);
                    }
                    fotoKelasPreviewUrl = URL.createObjectURL(blob);
                    previewFotoKelas.src = fotoKelasPreviewUrl;
                    previewFotoKelas.classList.remove('d-none');
                    placeholderFotoKelas.classList.add('d-none');
                    btnStartCameraFotoKelas.classList.add('d-none');
                    btnRetakeFotoKelas.classList.remove('d-none');
                    statusFotoKelas.textContent = 'Foto kelas berhasil diambil.';
                    stopCameraFotoKelas();
                    updateSelectedJamPreview();
                    showFeedback('success', 'Foto kelas berhasil diambil.');
                }, 'image/jpeg', 0.85);
            }

            btnStartCameraFotoKelas.addEventListener('click', startCameraFotoKelas);
            btnCaptureFotoKelas.addEventListener('click', captureFotoKelas);
            btnRetakeFotoKelas.addEventListener('click', startCameraFotoKelas);
            inputMateriPembelajaran.addEventListener('input', function() {
                btnStartCameraFotoKelas.disabled = !inputMateriPembelajaran.value.trim();
                if (!inputMateriPembelajaran.value.trim() && inputFotoKelas.files.length === 0) {
                    statusFotoKelas.textContent = 'Isi materi pembelajaran untuk mengaktifkan kamera.';
                }
                updateSelectedJamPreview();
            });

            window.addEventListener('beforeunload', stopCameraFotoKelas);

            btnStartCameraFotoKelas.disabled = !inputMateriPembelajaran.value.trim();
            updateSelectedJamPreview();
            setInterval(updateSelectedJamPreview, 1000);
        });
    </script>
@endpush
