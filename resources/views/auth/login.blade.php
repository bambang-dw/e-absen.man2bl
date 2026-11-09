<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Sistem Presensi - e-absen MAN 2 Balam</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo1.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo1.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo1.png') }}">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --font-sans: 'Plus Jakarta Sans', system-ui, sans-serif;
            --primary-emerald: #059669;
            --primary-emerald-hover: #047857;
        }

        body {
            font-family: var(--font-sans);
            background: radial-gradient(circle at 10% 20%, rgba(5, 150, 105, 0.08) 0%, rgba(241, 245, 249, 1) 90%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 1.5rem 1rem;
            color: #1e293b;
        }

        /* Clear Look Glass Container */
        .login-card {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 24px;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08), 0 0 1px 1px rgba(255, 255, 255, 0.6) inset;
            max-width: 460px;
            width: 100%;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .school-badge-wrapper {
            width: 74px;
            height: 74px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
            padding: 8px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            transition: all 0.25s ease;
        }

        .school-badge-wrapper:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(5, 150, 105, 0.16);
        }

        .school-badge-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .badge-separator {
            width: 2px;
            height: 38px;
            background: #cbd5e1;
            border-radius: 2px;
        }

        /* Clear Look Role Tabs */
        .role-selector-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            background: #f1f5f9;
            padding: 5px;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            border: 1px solid #e2e8f0;
        }

        .role-tab-btn {
            border: none;
            background: transparent;
            padding: 8px 4px;
            border-radius: 10px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #64748b;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .role-tab-btn i {
            font-size: 1.15rem;
        }

        .role-tab-btn:hover {
            color: var(--primary-emerald);
        }

        .role-tab-btn.active {
            background: #ffffff;
            color: var(--primary-emerald);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.06);
        }

        /* Form Controls */
        .form-control-clear {
            background-color: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.92rem;
            color: #0f172a;
            transition: all 0.2s ease;
        }

        .form-control-clear:focus {
            background-color: #ffffff;
            border-color: var(--primary-emerald);
            box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.12);
            outline: none;
        }

        .input-icon-group {
            position: relative;
        }

        .input-icon-group .icon-left {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
        }

        .input-icon-group .form-control-clear {
            padding-left: 2.6rem;
        }

        .btn-toggle-pwd {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0;
        }

        .btn-login-clear {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #ffffff;
            border: none;
            padding: 0.85rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.02em;
            box-shadow: 0 6px 18px rgba(5, 150, 105, 0.25);
            transition: all 0.2s ease;
            width: 100%;
        }

        .btn-login-clear:hover {
            background: linear-gradient(135deg, #047857 0%, #065f46 100%);
            box-shadow: 0 8px 22px rgba(5, 150, 105, 0.35);
            transform: translateY(-1px);
            color: white;
        }

        /* Demo badge footer */
        .demo-hints {
            background: #f8fafc;
            border-radius: 12px;
            border: 1px dashed #cbd5e1;
            padding: 10px 14px;
            font-size: 0.75rem;
            color: #64748b;
        }
    </style>
</head>

<body>

    <div class="login-card p-4 p-md-5">
        <!-- School Logos (logo2 & logo1) & Header -->
        <div class="text-center mb-4">
            <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                <!-- Logo 2 (Kementerian Agama RI) -->
                <div class="school-badge-wrapper" title="Kementerian Agama RI">
                    <img src="{{ file_exists(public_path('images/logo2.png')) ? asset('images/logo2.png') : asset('images/logo.svg') }}"
                        alt="Logo Kementerian Agama" onerror="this.src='{{ asset('images/logo.svg') }}'">
                </div>

                <div class="badge-separator"></div>

                <!-- Logo 1 (MAN 2 Bandar Lampung) -->
                <div class="school-badge-wrapper" title="MAN 2 Bandar Lampung">
                    <img src="{{ file_exists(public_path('images/logo1.png')) ? asset('images/logo1.png') : asset('images/logo.svg') }}"
                        alt="Logo MAN 2 Bandar Lampung" onerror="this.src='{{ asset('images/logo.svg') }}'">
                </div>
            </div>
            <h4 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.02em;">MAN 2 BANDAR LAMPUNG</h4>
            <p class="text-muted small mb-0">Sistem Presensi & Kartu Siswa Digital</p>
        </div>

        <!-- Flash Alert -->
        @if (session('success'))
            <div class="alert alert-success border-0 rounded-3 py-2 px-3 small d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 rounded-3 py-2 px-3 small d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-exclamation-circle-fill text-danger fs-5"></i>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST" id="formLogin">
            @csrf
            @php($selectedRole = in_array(old('role', 'siswa'), ['siswa', 'guru', 'admin'], true) ? old('role', 'siswa') : 'siswa')
            <input type="hidden" name="role" id="selectedRole" value="{{ $selectedRole }}">

            <!-- Role Selector Tabs -->
            <label class="form-label small fw-bold text-muted mb-2">Pilih Peran Anda:</label>
            <div class="role-selector-grid">
                <button type="button" class="role-tab-btn {{ $selectedRole === 'siswa' ? 'active' : '' }}"
                    onclick="selectRole('siswa', this)">
                    <i class="bi bi-mortarboard"></i>
                    <span>Siswa</span>
                </button>
                <button type="button" class="role-tab-btn {{ $selectedRole === 'guru' ? 'active' : '' }}"
                    onclick="selectRole('guru', this)">
                    <i class="bi bi-person-workspace"></i>
                    <span>Guru</span>
                </button>
                <button type="button" class="role-tab-btn {{ $selectedRole === 'admin' ? 'active' : '' }}"
                    onclick="selectRole('admin', this)">
                    <i class="bi bi-shield-lock"></i>
                    <span>Admin</span>
                </button>
            </div>

            <!-- Username / NISN Input Field -->
            <div class="mb-3">
                <label id="usernameLabel" class="form-label small fw-bold text-dark">
                    NISN Siswa <span class="text-danger">*</span>
                </label>
                <div class="input-icon-group">
                    <i id="usernameIcon" class="bi bi-person-vcard icon-left"></i>
                    <input type="text" name="username" id="usernameInput" class="form-control form-control-clear"
                        placeholder="Masukkan nomor NISN Anda" value="{{ old('username') }}" required autofocus>
                </div>
                <div id="usernameHelp" class="form-text text-muted" style="font-size: 0.72rem;">
                    Siswa dapat login menggunakan nomor NISN (atau NIS) yang terdaftar di sekolah.
                </div>
            </div>

            <!-- Password Field -->
            <div class="mb-3">
                <label class="form-label small fw-bold text-dark">
                    Kata Sandi <span class="text-danger">*</span>
                </label>
                <div class="input-icon-group">
                    <i class="bi bi-key icon-left"></i>
                    <input type="password" name="password" id="passwordInput" class="form-control form-control-clear"
                        placeholder="Masukkan kata sandi" required>
                    <button type="button" class="btn-toggle-pwd" onclick="togglePasswordVisibility()"
                        aria-label="Lihat Password">
                        <i id="eyeIcon" class="bi bi-eye"></i>
                    </button>
                </div>
                <div id="passwordHelp" class="form-text text-muted" style="font-size: 0.72rem;">
                    Password awal siswa adalah nomor NISN Anda.
                </div>
            </div>

            <!-- Remember Me Checkbox -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" checked>
                    <label class="form-check-label small text-muted" for="remember">
                        Ingat saya di perangkat ini
                    </label>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-login-clear d-flex align-items-center justify-content-center gap-2">
                <span>Masuk Sekarang</span>
                <i class="bi bi-arrow-right"></i>
            </button>
        </form>

        <!-- Demo Accounts Hint for Evaluator / Testing
        <div class="demo-hints mt-4">
            <div class="fw-bold mb-1 text-dark d-flex align-items-center gap-1">
                <i class="bi bi-info-circle text-primary"></i> Contoh Akun Demo (MAN 2 BALAM):
            </div>
            <div class="d-flex flex-column gap-1">
                <div>• <strong>Siswa:</strong> Username: <code>NISN Siswa</code> / Sandi Awal: <code>NISN Siswa</code></div>
                <div>• <strong>Guru:</strong> User: <code>guru</code> / Sandi: <code>guru123</code></div>
                <div>• <strong>Wali Kelas:</strong> User: <code>walikelas</code> / Sandi: <code>walikelas123</code></div>
                <div>• <strong>Admin:</strong> User: <code>admin</code> / Sandi: <code>admin123</code></div>
            </div>
        </div> -->

        <div class="text-center mt-3 text-muted" style="font-size: 0.72rem;">
            &copy; {{ date('Y') }} MAN 2 Bandar Lampung • E-Absensi Siswa
        </div>
    </div>

    <script>
        function selectRole(role, btnElement = null) {
            document.getElementById('selectedRole').value = role;

            // Update tab styles
            document.querySelectorAll('.role-tab-btn').forEach(btn => btn.classList.remove('active'));
            if (btnElement) {
                btnElement.classList.add('active');
            } else if (window.event && window.event.currentTarget) {
                window.event.currentTarget.classList.add('active');
            }

            // Dynamic Labels & Placeholders based on Role
            const label = document.getElementById('usernameLabel');
            const input = document.getElementById('usernameInput');
            const icon = document.getElementById('usernameIcon');
            const help = document.getElementById('usernameHelp');
            const pwdHelp = document.getElementById('passwordHelp');

            if (role === 'siswa') {
                label.innerHTML = 'NISN Siswa <span class="text-danger">*</span>';
                input.placeholder = 'Masukkan 10 digit NISN Anda';
                icon.className = 'bi bi-person-vcard icon-left';
                help.textContent = 'Gunakan nomor NISN yang terdaftar di sekolah.';
                pwdHelp.textContent = 'Password awal adalah NISN Anda.';
            } else if (role === 'guru') {
                label.innerHTML = 'NIP/PegID Guru <span class="text-danger">*</span>';
                input.placeholder = 'Masukkan NIP/PegID Guru';
                icon.className = 'bi bi-person-workspace icon-left';
                help.textContent = 'Gunakan NIP/PegID guru Anda.';
                pwdHelp.textContent = 'Password akun guru Anda.';
            } else {
                label.innerHTML = 'Username Administrator <span class="text-danger">*</span>';
                input.placeholder = 'Masukkan Akun Admin';
                icon.className = 'bi bi-shield-lock icon-left';
                help.textContent = 'Akses penuh administrator sistem MAN 2 Bandar Lampung.';
                pwdHelp.textContent = 'Password akun administrator.';
            }
        }

        function togglePasswordVisibility() {
            const pwd = document.getElementById('passwordInput');
            const eye = document.getElementById('eyeIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                eye.className = 'bi bi-eye-slash';
            } else {
                pwd.type = 'password';
                eye.className = 'bi bi-eye';
            }
        }
    </script>
</body>

</html>
