<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <h4 class="fw-bold text-primary mb-1">
                        <i class="bi bi-qr-code-scan me-1"></i> Scanner Absensi Siswa
                    </h4>
                    <p class="text-muted small mb-0">MAN 2 Bandar Lampung • Pemindaian Otomatis Berkelanjutan</p>
                </div>

                <!-- Status Sesi Absensi (otomatis, tidak dapat dipilih manual) -->
                <div class="mb-3">
                    <label for="sesi" class="form-label fw-semibold small text-dark">Sesi Absensi Aktif:</label>
                    @if ($adaSesiAktif)
                        <div id="sesi" class="form-control bg-success-subtle border-success-subtle fw-semibold text-success d-flex align-items-center">
                            <i class="bi bi-broadcast me-2"></i> {{ $namaSesiAktif }}
                        </div>
                        <small class="text-muted" style="font-size: 0.72rem;">Sesi aktif otomatis mengikuti jadwal waktu.</small>
                    @else
                        <div id="sesi" class="form-control bg-warning-subtle border-warning-subtle fw-semibold text-warning-emphasis d-flex align-items-center">
                            <i class="bi bi-mic-mute-fill me-2"></i> Tidak Ada Sesi Aktif
                        </div>
                        <small class="text-muted" style="font-size: 0.72rem;">
                            {{ $sesis->isEmpty() ? 'Belum ada sesi absensi yang dibuat.' : 'Pemindaian dimatikan sampai sesi berikutnya dimulai. Sesi akan aktif otomatis.' }}
                        </small>
                    @endif
                </div>

                @if ($keteranganLibur)
                    <div class="alert alert-danger rounded-3 shadow-sm py-2 px-3 mb-3" role="alert">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-calendar-x text-danger fs-5"></i>
                            <div class="fw-semibold small">Hari Libur: {{ $keteranganLibur }}. Pemindaian presensi dinonaktifkan.</div>
                        </div>
                    </div>
                @elseif (! $adaSesiAktif)
                    <div class="alert alert-warning rounded-3 shadow-sm py-2 px-3 mb-3" role="alert">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history text-warning fs-5"></i>
                            <div class="fw-semibold small">Tidak Ada Sesi Aktif. Menunggu sesi berikutnya dimulai...</div>
                        </div>
                    </div>
                @endif

                <!-- Area Kamera Scanner (wire:ignore WAJIB agar stream video tidak terputus saat Livewire update data) -->
                <div wire:ignore id="cameraWrapper" class="text-center bg-dark rounded-4 p-2 mb-3 position-relative shadow" style="overflow: hidden; min-height: 280px;">
                    <div id="reader" style="width: 100%; border-radius: 12px; overflow: hidden;"></div>

                    <!-- Overlay Badge Status Pemindai -->
                    <div class="position-absolute top-0 start-50 translate-middle-x mt-3" style="z-index: 10; pointer-events: none;">
                        <span id="scanStatusBadge" class="badge rounded-pill px-3 py-2 fw-semibold shadow" style="background-color: rgba(16, 185, 129, 0.95); color: white; font-size: 0.82rem; backdrop-filter: blur(4px); transition: all 0.25s ease;">
                            <i class="bi bi-camera-fill me-1"></i> Kamera Siap • Arahkan QR Siswa
                        </span>
                    </div>

                    <!-- Sudut Pembidik Kamera (Viewfinder Reticle) -->
                    <div class="scanner-viewfinder position-absolute top-50 start-50 translate-middle" style="width: 220px; height: 220px; pointer-events: none; border: 2px dashed rgba(255, 255, 255, 0.4); border-radius: 16px;"></div>
                </div>

                <!-- Kontrol & Info Scanner -->
                <div class="d-flex align-items-center justify-content-between mb-3 px-1 small">
                    <div class="d-flex align-items-center gap-2">
                        <span class="spinner-grow spinner-grow-sm text-success" role="status" id="cameraActiveDot"></span>
                        <span class="text-muted fw-semibold" id="cameraStatusText">Auto-Scan Aktif (Jeda 0.5s)</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-sm" id="btnToggleCamera" onclick="switchCamera()">
                            <i class="bi bi-arrow-repeat me-1"></i> Ganti Kamera
                        </button>
                    </div>
                </div>

                <!-- Kotak Notifikasi / Alert -->
                @if ($alertMessage)
                    <div class="alert alert-{{ $alertType }} alert-dismissible fade show rounded-3 shadow-sm py-2 px-3 mb-3" role="alert">
                        <div class="d-flex align-items-center gap-2">
                            @if($alertType === 'success')
                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            @elseif($alertType === 'warning')
                                <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                            @else
                                <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                            @endif
                            <div class="fw-semibold small">{{ $alertMessage }}</div>
                        </div>
                        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Card Info Siswa Terakhir yang Dipindai -->
                @if ($lastSiswa)
                    <div class="card bg-light border-0 rounded-3 p-3 shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <img src="{{ $lastSiswa->foto_url }}" alt="{{ $lastSiswa->nama }}" class="rounded-circle border shadow-sm flex-shrink-0" style="width: 58px; height: 58px; object-fit: cover;">
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h6 class="mb-0 text-dark fw-bold text-truncate">{{ $lastSiswa->nama }}</h6>
                                    @php
                                        $lastStatus = 'Tercatat';
                                        if (str_contains($alertMessage, 'Tepat Waktu')) $lastStatus = 'Tepat Waktu';
                                        elseif (str_contains($alertMessage, 'Terlambat')) $lastStatus = 'Terlambat';
                                        elseif (str_contains($alertMessage, 'sudah')) $lastStatus = 'Sudah Absen';
                                    @endphp
                                    <span class="badge {{ $lastStatus === 'Tepat Waktu' ? 'bg-success' : ($lastStatus === 'Terlambat' ? 'bg-warning text-dark' : 'bg-info') }} rounded-pill px-2 py-1 small">
                                        {{ $lastStatus }}
                                    </span>
                                </div>
                                <p class="mb-0 text-muted small mt-1">
                                    Kelas: <strong class="text-dark">{{ $lastSiswa->kelas }}</strong> 
                                    | NIS: <strong class="text-dark">{{ $lastSiswa->nis }}</strong>
                                    @if($lastSiswa->nisn) | NISN: <strong class="text-dark">{{ $lastSiswa->nisn }}</strong>@endif
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Pustaka Html5-Qrcode CDN -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        // State Scanner
        let html5QrCode = null;
        let isScanningLocked = false;
        let lastScannedText = '';
        let lastScannedTimestamp = 0;
        let scanCooldownTimer = null;
        let availableCameras = [];
        let currentCameraIndex = 0;
        let audioContextInstance = null;

        /**
         * Inisialisasi Audio Context
         */
        function getAudioContext() {
            if (!audioContextInstance) {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (AudioCtx) {
                    audioContextInstance = new AudioCtx();
                }
            }
            if (audioContextInstance && audioContextInstance.state === 'suspended') {
                audioContextInstance.resume();
            }
            return audioContextInstance;
        }

        /**
         * Memutar suara beep feedback
         */
        function playBeep(success = true) {
            try {
                const ctx = getAudioContext();
                if (!ctx) return;

                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);

                if (success) {
                    // Nada tinggi 'beep' ceria (880 Hz)
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(880, ctx.currentTime);
                    gain.gain.setValueAtTime(0.25, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.16);
                    osc.start(ctx.currentTime);
                    osc.stop(ctx.currentTime + 0.16);
                } else {
                    // Nada rendah peringatan (260 Hz)
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(260, ctx.currentTime);
                    gain.gain.setValueAtTime(0.25, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
                    osc.start(ctx.currentTime);
                    osc.stop(ctx.currentTime + 0.25);
                }
            } catch (err) {
                console.warn("Gagal memutar audio:", err);
            }
        }

        /**
         * Update Tampilan Badge Status Kamera
         */
        function updateScannerBadge(state, message) {
            const badge = document.getElementById('scanStatusBadge');
            const dot = document.getElementById('cameraActiveDot');
            const statusText = document.getElementById('cameraStatusText');

            if (!badge) return;

            if (state === 'ready') {
                badge.style.backgroundColor = 'rgba(16, 185, 129, 0.95)';
                badge.innerHTML = `<i class="bi bi-camera-fill me-1"></i> ${message || 'Kamera Siap • Arahkan QR Siswa'}`;
                if (dot) { dot.className = 'spinner-grow spinner-grow-sm text-success'; }
                if (statusText) { statusText.textContent = 'Auto-Scan Aktif (Jeda 0.5s)'; }
            } else if (state === 'processing') {
                badge.style.backgroundColor = 'rgba(14, 165, 233, 0.95)';
                badge.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span> ${message || 'Menyimpan Presensi...'}`;
                if (dot) { dot.className = 'spinner-grow spinner-grow-sm text-info'; }
                if (statusText) { statusText.textContent = 'Menyimpan ke database...'; }
            } else if (state === 'success') {
                badge.style.backgroundColor = 'rgba(5, 150, 105, 0.95)';
                badge.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> ${message || 'Tersimpan!'}`;
            } else if (state === 'warning') {
                badge.style.backgroundColor = 'rgba(245, 158, 11, 0.95)';
                badge.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i> ${message || 'Perhatian'}`;
            } else if (state === 'error') {
                badge.style.backgroundColor = 'rgba(239, 68, 68, 0.95)';
                badge.innerHTML = `<i class="bi bi-x-circle-fill me-1"></i> ${message || 'Gagal'}`;
            }
        }

        /**
         * Callback saat QR Code berhasil terdeteksi oleh kamera
         */
        function handleQrCodeScanned(decodedText, decodedResult) {
            const now = Date.now();

            // 0. Jika tidak ada sesi aktif, kamera dimute (tidak ada pemindaian)
            if (window.sesiAktif === false) {
                updateScannerBadge('warning', 'Tidak Ada Sesi Aktif');
                return;
            }

            // 1. Jika scanner sedang mengunci / dalam jeda 0.5 detik, abaikan frame ini
            if (isScanningLocked) {
                return;
            }

            // 2. Mencegah pemindaian berulang untuk kartu siswa yang SAMA jika kartu belum ditarik (cooldown 2.5s)
            // Namun jika siswa BERBEDA (QR berbeda), langsung diproses begitu jeda 0.5s selesai!
            if (decodedText === lastScannedText && (now - lastScannedTimestamp) < 2500) {
                return;
            }

            // Kunci scanner seketika agar tidak memicu scan ganda dari frame video berikutnya
            isScanningLocked = true;
            lastScannedText = decodedText;
            lastScannedTimestamp = now;

            // Beri visual badge "Memproses"
            updateScannerBadge('processing', 'Menyimpan Data Presensi...');

            // Kirim data scan ke Livewire backend
            @this.call('processScan', decodedText)
                .then(response => {
                    if (response && response.success) {
                        updateScannerBadge('success', `Tersimpan: ${response.nama || 'Siswa'}`);
                        try {
                            if ('BroadcastChannel' in window) {
                                const bc = new BroadcastChannel('e_absen_live_channel');
                                bc.postMessage({ type: 'absensi_updated', timestamp: Date.now() });
                                bc.close();
                            }
                            localStorage.setItem('e_absen_last_transaction', String(Date.now()));
                        } catch (e) {}
                    } else if (response && response.message === 'Sudah absen') {
                        updateScannerBadge('warning', 'Siswa Sudah Absen Sesi Ini');
                    } else {
                        updateScannerBadge('warning', (response && response.message) || 'Presensi Diproses');
                    }
                })
                .catch(err => {
                    console.error("Gagal memproses absensi via Livewire:", err);
                    updateScannerBadge('error', 'Gagal memproses data');
                })
                .finally(() => {
                    // SESUAI PERMINTAAN USER:
                    // Setelah data tersimpan & notifikasi muncul, tepat 0,5 detik (500 ms) kemudian
                    // kamera kembali aktif dan siap untuk memindai QRCode siswa berikutnya!
                    if (scanCooldownTimer) {
                        clearTimeout(scanCooldownTimer);
                    }

                    scanCooldownTimer = setTimeout(() => {
                        isScanningLocked = false;
                        updateScannerBadge('ready', 'Kamera Siap • Arahkan QR Siswa');
                    }, 500); // Tepat 0.5 detik
                });
        }

        /**
         * Memulai Pemindai Kamera
         */
        function startCamera(cameraIdOrConfig) {
            const config = {
                fps: 15,
                qrbox: { width: 240, height: 240 },
                aspectRatio: 1.0
            };

            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("reader");
            }

            const targetConfig = cameraIdOrConfig || { facingMode: "environment" };

            html5QrCode.start(
                targetConfig,
                config,
                handleQrCodeScanned,
                (errorMessage) => {
                    // Abaikan frame tanpa barcode agar console tetap bersih
                }
            ).then(() => {
                updateScannerBadge('ready', 'Kamera Siap • Arahkan QR Siswa');
            }).catch(err => {
                console.warn("Mencoba kamera default karena mode environment gagal:", err);
                // Fallback ke kamera default
                html5QrCode.start(
                    { facingMode: "user" },
                    config,
                    handleQrCodeScanned,
                    () => {}
                ).then(() => {
                    updateScannerBadge('ready', 'Kamera Siap • Arahkan QR Siswa');
                }).catch(err2 => {
                    console.error("Tidak dapat membuka kamera:", err2);
                    updateScannerBadge('error', 'Kamera tidak dapat diakses');
                });
            });
        }

        /**
         * Ganti Kamera (Depan / Belakang / Kamera Lain)
         */
        function switchCamera() {
            if (!html5QrCode) return;

            updateScannerBadge('processing', 'Mengalihkan kamera...');

            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length > 0) {
                    availableCameras = devices;
                    currentCameraIndex = (currentCameraIndex + 1) % availableCameras.length;
                    const nextCamera = availableCameras[currentCameraIndex];

                    html5QrCode.stop().then(() => {
                        startCamera({ deviceId: { exact: nextCamera.id } });
                    }).catch(() => {
                        startCamera({ deviceId: { exact: nextCamera.id } });
                    });
                } else {
                    // Fallback toggle facingMode
                    html5QrCode.stop().then(() => {
                        startCamera({ facingMode: "user" });
                    }).catch(console.error);
                }
            }).catch(err => {
                console.warn("Gagal mendapatkan daftar kamera:", err);
            });
        }

        // State sesi aktif (diinisialisasi dari server, diperbarui tiap perubahan)
        window.sesiAktif = @json($adaSesiAktif);

        /**
         * Terapkan status sesi aktif ke badge kamera (mute / aktif)
         */
        function terapkanStatusSesi(aktif) {
            window.sesiAktif = !!aktif;
            if (!aktif) {
                updateScannerBadge('warning', 'Tidak Ada Sesi Aktif');
                const statusText = document.getElementById('cameraStatusText');
                if (statusText) { statusText.textContent = 'Menunggu sesi berikutnya dimulai...'; }
            } else {
                updateScannerBadge('ready', 'Kamera Siap • Arahkan QR Siswa');
            }
        }

        terapkanStatusSesi(window.sesiAktif);

        // Cek pergantian sesi setiap 15 detik agar sesi berikutnya otomatis aktif
        setInterval(() => {
            if (window.Livewire) {
                @this.call('periksaSesi');
            }
        }, 15000);

        // Listener Event Data Sesi Terbaru dari server
        window.addEventListener('sesi-diubah', event => {
            const detail = Array.isArray(event.detail) ? event.detail[0] : event.detail;
            terapkanStatusSesi(detail?.adaSesiAktif);
        });

        // Listener Event Audio dari Livewire Dispatch
        window.addEventListener('play-sound', event => {
            const type = event.detail?.type || (Array.isArray(event.detail) ? event.detail[0]?.type : null) || 'success';
            playBeep(type === 'success');
        });

        // Buka AudioContext saat ada interaksi pertama di halaman agar audio diizinkan browser
        document.addEventListener('click', () => getAudioContext(), { once: true });
        document.addEventListener('touchstart', () => getAudioContext(), { once: true });

        // Inisialisasi saat DOM siap
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => startCamera());
        } else {
            startCamera();
        }

        // Bersihkan stream kamera saat berpindah halaman
        window.addEventListener('beforeunload', () => {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().catch(console.error);
            }
        });
    </script>
</div>
