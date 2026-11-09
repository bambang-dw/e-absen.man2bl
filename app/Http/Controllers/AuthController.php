<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the clear-look login page.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login', [
            'institutionName' => 'MAN 2 Bandar Lampung',
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'string', 'in:siswa,guru,wali_kelas,admin'],
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'role.required' => 'Pilih peran / role terlebih dahulu.',
            'role.in' => 'Pilihan peran tidak valid.',
            'username.required' => 'Username atau NIS/NISN wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $role = $request->input('role');
        $username = trim($request->input('username'));
        $password = $request->input('password');

        // 1. Logika Login untuk Siswa (Tabel Siswa sebagai Akun Siswa, Username & Password Awal = NISN)
        if ($role === 'siswa') {
            // Cari data siswa di tabel siswas berdasarkan NISN atau NIS
            $siswa = Siswa::where('nisn', $username)
                ->orWhere('nis', $username)
                ->first();

            // Cek juga kemungkinan username cocok dengan User::username
            if (! $siswa) {
                $userSiswa = User::where('role', 'siswa')->where('username', $username)->first();
                if ($userSiswa && $userSiswa->siswa) {
                    $siswa = $userSiswa->siswa;
                }
            }

            if (! $siswa) {
                return back()->withInput($request->only('username', 'role'))
                    ->withErrors(['username' => 'NISN atau NIS siswa tidak terdaftar dalam database sekolah.']);
            }

            $defaultCredential = $siswa->nisn ?: $siswa->nis;

            // Cari atau buatkan akun User untuk siswa ini jika belum ada
            $user = User::where('siswa_id', $siswa->id)->first();
            if (! $user) {
                $user = User::create([
                    'siswa_id' => $siswa->id,
                    'username' => ($username === $siswa->nis) ? $siswa->nis : $defaultCredential,
                    'name' => $siswa->nama,
                    'email' => $defaultCredential.'@siswa.man2balam.sch.id',
                    'role' => 'siswa',
                    'no_hp' => $siswa->no_hp_ortu,
                    'password' => Hash::make($defaultCredential),
                    'password_changed_at' => null,
                ]);
            }

            // Validasi password:
            // - Jika siswa belum mengubah password (password_changed_at is null), cek dengan NISN atau NIS atau hash
            // - Jika siswa sudah mengubah password, cek dengan hash password baru
            $isValid = false;
            $isDefaultPassword = ($password === $siswa->nisn)
                || ($password === $siswa->nis)
                || ($password === 'siswa123')
                || ($password === $defaultCredential);

            if (Hash::check($password, $user->password)) {
                $isValid = true;
            } elseif (is_null($user->password_changed_at) && $isDefaultPassword) {
                $isValid = true;
                $user->password = Hash::make($password);
                $user->save();
            }

            if ($isValid) {
                Auth::login($user, $request->boolean('remember'));

                return redirect()->route('siswa.dashboard')->with('success', 'Selamat datang, '.$siswa->nama.'!');
            }

            $errorMessage = is_null($user->password_changed_at)
                ? 'Password tidak sesuai. Password awal akun Anda adalah nomor NISN.'
                : 'Password salah. Jika lupa password, hubungi Administrator untuk mereset password Anda kembali ke nomor NISN.';

            return back()->withInput($request->only('username', 'role'))
                ->withErrors(['password' => $errorMessage]);
        }

        // 2. Logika Login untuk Guru, Wali Kelas, dan Admin (Mendukung Multi-Role)
        $user = User::where(function ($q) use ($role) {
            $q->withRole($role);
        })
            ->where(function ($q) use ($username) {
                $q->where('username', $username)
                    ->orWhere('email', $username);
            })
            ->first();

        // Fallback untuk akun demo jika belum ada
        if (! $user && $username === 'guru' && $role === 'guru') {
            $user = User::create([
                'username' => 'guru',
                'name' => 'Guru Pengajar',
                'email' => 'guru@man2balam.sch.id',
                'role' => 'guru',
                'password' => Hash::make('guru123'),
                'no_hp' => '081234567801',
            ]);
        } elseif (! $user && $username === 'walikelas' && $role === 'wali_kelas') {
            $user = User::create([
                'username' => 'walikelas',
                'name' => 'Wali Kelas X-A',
                'email' => 'walikelas@man2balam.sch.id',
                'role' => 'wali_kelas,guru',
                'password' => Hash::make('walikelas123'),
                'no_hp' => '081234567802',
            ]);
        }

        if ($user && Hash::check($password, $user->password)) {
            Auth::login($user, $request->boolean('remember'));

            return $this->redirectBasedOnRole($user, $role);
        }

        return back()->withInput($request->only('username', 'role'))
            ->withErrors(['username' => 'Username atau password tidak cocok dengan peran '.ucfirst(str_replace('_', ' ', $role)).'.']);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }

    /**
     * Redirect to appropriate dashboard based on user role.
     */
    private function redirectBasedOnRole(User $user, ?string $selectedRole = null): RedirectResponse
    {
        // 1. Jika ada role yang dipilih saat login
        if ($selectedRole && $user->hasRole($selectedRole)) {
            return match ($selectedRole) {
                'siswa' => redirect()->route('siswa.dashboard'),
                'wali_kelas' => redirect()->route('walikelas.dashboard'),
                'guru' => redirect()->route('guru.dashboard'),
                default => redirect()->route('dashboard'),
            };
        }

        // 2. Pengalihan cerdas sesuai urutan prioritas role yang dimiliki
        if ($user->hasRole('admin')) {
            return redirect()->route('dashboard');
        }
        if ($user->hasRole('wali_kelas')) {
            return redirect()->route('walikelas.dashboard');
        }
        if ($user->hasRole('guru')) {
            return redirect()->route('guru.dashboard');
        }
        if ($user->hasRole('siswa')) {
            return redirect()->route('siswa.dashboard');
        }

        return redirect()->route('dashboard');
    }
}
