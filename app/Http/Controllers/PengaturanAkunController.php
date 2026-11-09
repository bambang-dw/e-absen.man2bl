<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengaturanAkunController extends Controller
{
    /**
     * Display a listing of user accounts.
     */
    public function index(Request $request): View
    {
        $activeTab = $request->input('tab', 'pegawai');

        // 1. Data Akun Pegawai / Staff (Admin, Petugas, Guru, Kepsek)
        $query = User::where('role', '!=', 'siswa');

        // Search by username, role, or no_hp
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%")
                    ->orWhere('no_hp', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role (mendukung multi-role via withRole)
        if ($role = $request->input('role')) {
            $query->withRole($role);
        }

        $users = $query->orderByRaw("CASE WHEN role LIKE '%admin%' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->orderBy('username')
            ->paginate(15, ['*'], 'page')
            ->withQueryString();

        // Staff counts (mendukung multi-role)
        $totalUsers = User::where('role', '!=', 'siswa')->count();
        $totalAdmin = User::withRole('admin')->count();
        $totalGuru = User::where(function ($q) {
            $q->withRole('guru')
                ->orWhere(fn ($sq) => $sq->withRole('wali_kelas'));
        })->count();
        $totalWaliKelas = User::withRole('wali_kelas')->count();

        // 2. Data Akun Siswa (Berdasarkan Tabel Siswa)
        $siswaQuery = Siswa::with('user');

        if ($searchSiswa = $request->input('search_siswa')) {
            $siswaQuery->where(function ($q) use ($searchSiswa) {
                $q->where('nama', 'like', "%{$searchSiswa}%")
                    ->orWhere('nisn', 'like', "%{$searchSiswa}%")
                    ->orWhere('nis', 'like', "%{$searchSiswa}%")
                    ->orWhere('kelas', 'like', "%{$searchSiswa}%");
            });
        }

        if ($kelasSiswa = $request->input('kelas_siswa')) {
            $siswaQuery->where('kelas', $kelasSiswa);
        }

        if ($statusSiswa = $request->input('status_siswa')) {
            if ($statusSiswa === 'kustom') {
                $siswaQuery->whereHas('user', function ($q) {
                    $q->whereNotNull('password_changed_at');
                });
            } elseif ($statusSiswa === 'default') {
                $siswaQuery->where(function ($q) {
                    $q->whereDoesntHave('user')
                        ->orWhereHas('user', function ($uq) {
                            $uq->whereNull('password_changed_at');
                        });
                });
            }
        }

        $siswas = $siswaQuery->orderBy('kelas')->orderBy('nama')->paginate(15, ['*'], 'siswa_page')->withQueryString();
        $daftarKelas = Siswa::select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');

        $totalSiswa = Siswa::count();
        $totalSiswaKustom = User::where('role', 'siswa')->whereNotNull('password_changed_at')->count();
        $totalSiswaDefault = max(0, $totalSiswa - $totalSiswaKustom);

        // Ensure at least one admin exists if table is empty
        if ($totalAdmin === 0) {
            User::create([
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'no_hp' => '081234567890',
                'name' => 'Administrator Utama',
                'email' => 'admin@e-absen.test',
            ]);
            $users = User::where('role', '!=', 'siswa')->paginate(10, ['*'], 'page');
            $totalUsers = 1;
            $totalAdmin = 1;
        }

        return view('pengaturan-akun.index', compact(
            'activeTab',
            'users',
            'totalUsers',
            'totalAdmin',
            'totalGuru',
            'totalWaliKelas',
            'siswas',
            'daftarKelas',
            'totalSiswa',
            'totalSiswaKustom',
            'totalSiswaDefault'
        ));
    }

    /**
     * Parse and normalize a role string or mixed raw string from Excel/Forms.
     */
    public static function parseRolesString(string $rawRole): string
    {
        if (trim($rawRole) === '') {
            return 'guru';
        }

        // Split by comma, semicolon, slash, backslash, plus, ampersand, pipe, newline, ' dan ', ' and '
        $tokens = preg_split('/[,;\/\\\\+&|\n]|(?:\s+dan\s+)|\s+and\s+/i', $rawRole);
        $cleanRoles = [];

        foreach ($tokens as $token) {
            $t = strtolower(trim($token));
            if ($t === '') {
                continue;
            }

            $cleaned = str_replace([' ', '-', '_', '.', '/'], '', $t);

            if (str_contains($cleaned, 'walikelas') || str_contains($cleaned, 'wali') || str_contains($cleaned, 'walas')) {
                $cleanRoles[] = 'wali_kelas';
            }
            if (str_contains($cleaned, 'guru') || str_contains($cleaned, 'pengajar') || str_contains($cleaned, 'pendidik') || str_contains($cleaned, 'petugas')) {
                $cleanRoles[] = 'guru';
            }
            if (str_contains($cleaned, 'admin') || str_contains($cleaned, 'operator')) {
                $cleanRoles[] = 'admin';
            }
            if (str_contains($cleaned, 'kepsek') || str_contains($cleaned, 'kepala')) {
                $cleanRoles[] = 'kepsek';
            }
        }

        if (empty($cleanRoles)) {
            $cleanRoles = ['guru'];
        }

        $sortOrder = ['admin' => 1, 'guru' => 2, 'wali_kelas' => 3, 'kepsek' => 4];
        $uniqueRoles = array_values(array_unique($cleanRoles));
        usort($uniqueRoles, fn ($a, $b) => ($sortOrder[$a] ?? 99) <=> ($sortOrder[$b] ?? 99));

        return implode(',', $uniqueRoles);
    }

    /**
     * Normalize an array of roles.
     *
     * @param  array<int, string>  $roles
     * @return array<int, string>
     */
    public static function parseRolesArray(array $roles): array
    {
        $joined = implode(',', $roles);

        return explode(',', self::parseRolesString($joined));
    }

    /**
     * Download standard Excel import template for staff and teachers.
     */
    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Pegawai & Guru');

        $headers = ['USERNAME / NIP', 'NAMA LENGKAP', 'ROLE', 'NO HP / WHATSAPP', 'EMAIL', 'PASSWORD'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];

        foreach ($headers as $index => $header) {
            $col = $cols[$index];
            $sheet->setCellValue("{$col}1", $header);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("{$col}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E293B');
            $sheet->getStyle("{$col}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Contoh data pegawai/guru termasuk multi-role
        $sampleData = [
            ['197505122005011002', 'Drs. H. Ahmad Fauzi, M.Pd.', 'guru,wali_kelas', '081234567890', 'fauzi@man2balam.sch.id', 'guru123'],
            ['198208152009122003', 'Hj. Siti Rahmawati, S.Pd.', 'guru', '081234567891', 'siti@man2balam.sch.id', 'guru123'],
            ['198511102010011003', 'Rahmat Hidayat, M.Kom.', 'guru', '081234567892', 'rahmat@man2balam.sch.id', 'guru123'],
            ['197003051998031001', 'Dra. Hj. Nauval, M.Pd.I.', 'kepsek', '081234567894', 'kepsek@man2balam.sch.id', 'kepsek123'],
        ];

        $rowNum = 2;
        foreach ($sampleData as $data) {
            foreach ($data as $index => $val) {
                $col = $cols[$index];
                $sheet->setCellValueExplicit("{$col}{$rowNum}", $val, DataType::TYPE_STRING);
            }
            $rowNum++;
        }

        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'Template_Import_Akun_Pegawai_Guru.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import user accounts from an Excel file (.xlsx / .xls).
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.mimes' => 'File harus berformat Excel (.xlsx atau .xls).',
            'file.max' => 'Ukuran file maksimal 5MB.',
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            if (count($rows) < 2) {
                return redirect()->route('pengaturan-akun.index', ['tab' => 'pegawai'])
                    ->with('error', 'File Excel kosong atau tidak memiliki data.');
            }

            // Cari baris header secara dinamis (mengantisipasi jika ada baris judul di baris 1 atau 2)
            $headerRowKey = null;
            $columnMap = [];

            $rowKeys = array_keys($rows);
            $inspectKeys = array_slice($rowKeys, 0, 10);

            foreach ($inspectKeys as $rKey) {
                $tempMap = [];
                foreach ($rows[$rKey] as $col => $headerName) {
                    $clean = strtolower(trim((string) $headerName));
                    $clean = str_replace([' ', '_', '-', '/', '.'], '', $clean);

                    if (str_contains($clean, 'username') || str_contains($clean, 'nip') || str_contains($clean, 'user')) {
                        $tempMap['username'] = $col;
                    } elseif (str_contains($clean, 'nama')) {
                        $tempMap['name'] = $col;
                    } elseif (
                        str_contains($clean, 'role') ||
                        str_contains($clean, 'peran') ||
                        str_contains($clean, 'jabatan') ||
                        str_contains($clean, 'tugas') ||
                        str_contains($clean, 'hakakses') ||
                        str_contains($clean, 'akses') ||
                        str_contains($clean, 'status') ||
                        str_contains($clean, 'posisi') ||
                        str_contains($clean, 'wewenang')
                    ) {
                        $tempMap['role'] = $col;
                    } elseif (str_contains($clean, 'hp') || str_contains($clean, 'telepon') || str_contains($clean, 'wa') || str_contains($clean, 'kontak') || str_contains($clean, 'ponsel')) {
                        $tempMap['no_hp'] = $col;
                    } elseif (str_contains($clean, 'email') || str_contains($clean, 'surel')) {
                        $tempMap['email'] = $col;
                    } elseif (str_contains($clean, 'password') || str_contains($clean, 'sandi') || str_contains($clean, 'pass')) {
                        $tempMap['password'] = $col;
                    }
                }

                if (isset($tempMap['username']) || isset($tempMap['name'])) {
                    $headerRowKey = $rKey;
                    $columnMap = $tempMap;
                    break;
                }
            }

            if (! isset($columnMap['username']) && ! isset($columnMap['name'])) {
                return redirect()->route('pengaturan-akun.index', ['tab' => 'pegawai'])
                    ->with('error', 'Format kolom Excel tidak sesuai. Minimal harus memiliki kolom USERNAME / NIP atau NAMA.');
            }

            $inserted = 0;
            $updated = 0;

            foreach ($rows as $rKey => $row) {
                if ($headerRowKey !== null && $rKey <= $headerRowKey) {
                    continue;
                }

                $username = isset($columnMap['username']) ? trim((string) ($row[$columnMap['username']] ?? '')) : '';
                $name = isset($columnMap['name']) ? trim((string) ($row[$columnMap['name']] ?? '')) : '';
                $rawRole = isset($columnMap['role']) ? trim((string) ($row[$columnMap['role']] ?? '')) : '';
                $noHp = isset($columnMap['no_hp']) ? trim((string) ($row[$columnMap['no_hp']] ?? '')) : null;
                $email = isset($columnMap['email']) ? trim((string) ($row[$columnMap['email']] ?? '')) : null;
                $rawPassword = isset($columnMap['password']) ? trim((string) ($row[$columnMap['password']] ?? '')) : '';

                if (empty($username) && empty($name)) {
                    continue;
                }

                if (empty($username)) {
                    $username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', Str::slug($name, '_')));
                }
                $username = strtolower($username);

                if (empty($name)) {
                    $name = ucfirst($username);
                }

                // Parse dan normalisasi multi-role (misal: "guru,wali_kelas", "Guru / Wali Kelas", "Wali Kelas")
                $roleString = self::parseRolesString($rawRole);

                if (empty($email)) {
                    $email = $username.'@man2balam.sch.id';
                }

                $password = ! empty($rawPassword) ? $rawPassword : 'guru123';

                $existing = User::where('username', $username)
                    ->orWhere('email', $email)
                    ->first();

                if ($existing) {
                    $updateData = [
                        'name' => $name,
                        'role' => $roleString,
                        'no_hp' => $noHp ?: $existing->no_hp,
                    ];
                    if (! empty($rawPassword)) {
                        $updateData['password'] = Hash::make($rawPassword);
                    }
                    $existing->update($updateData);
                    $updated++;
                } else {
                    User::create([
                        'username' => $username,
                        'name' => $name,
                        'role' => $roleString,
                        'email' => $email,
                        'no_hp' => $noHp,
                        'password' => Hash::make($password),
                    ]);
                    $inserted++;
                }
            }

            return redirect()->route('pengaturan-akun.index', ['tab' => 'pegawai'])
                ->with('success', "Import akun berhasil! {$inserted} akun pegawai/guru baru ditambahkan, {$updated} akun diperbarui.");
        } catch (\Exception $e) {
            return redirect()->route('pengaturan-akun.index', ['tab' => 'pegawai'])
                ->with('error', 'Terjadi kesalahan saat memproses file Excel: '.$e->getMessage());
        }
    }

    /**
     * Store a newly created user account.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['nullable', 'string'],
            'roles' => ['nullable', 'array'],
            'no_hp' => ['nullable', 'string', 'max:25'],
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'email', 'max:150', 'unique:users,email'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan oleh akun lain.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, strip, dan garis bawah.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        // Resolve roles (mendukung roles[] maupun string role)
        $rolesInput = $request->input('roles', []);
        if (empty($rolesInput) && ! empty($validated['role'])) {
            $rolesInput = explode(',', (string) $validated['role']);
        }
        $cleanRoles = self::parseRolesArray((array) $rolesInput);
        if (empty($cleanRoles)) {
            $cleanRoles = ['guru'];
        }
        $roleString = implode(',', $cleanRoles);

        $userData = [
            'username' => strtolower($validated['username']),
            'password' => Hash::make($validated['password']),
            'role' => $roleString,
            'no_hp' => $validated['no_hp'] ?? null,
            'name' => $validated['name'] ?? ucfirst($validated['username']),
            'email' => $validated['email'] ?? (strtolower($validated['username']).'@e-absen.local'),
        ];

        User::create($userData);

        return redirect()->route('pengaturan-akun.index')
            ->with('success', "Akun pengguna '{$userData['username']}' dengan role '{$userData['role']}' berhasil dibuat.");
    }

    /**
     * Update the specified user account.
     */
    public function update(Request $request, ?User $user = null): RedirectResponse
    {
        // If user parameter is not passed, fallback to logged in user or first user
        if (! $user || ! $user->exists) {
            $user = auth()->user() ?? User::first();
        }

        if (! $user) {
            return redirect()->route('pengaturan-akun.index')->with('error', 'Akun tidak ditemukan.');
        }

        $validated = $request->validate([
            'username' => ['nullable', 'string', 'max:50', 'alpha_dash', 'unique:users,username,'.$user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['nullable', 'string'],
            'roles' => ['nullable', 'array'],
            'no_hp' => ['nullable', 'string', 'max:25'],
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'email', 'max:150', 'unique:users,email,'.$user->id],
        ], [
            'username.unique' => 'Username sudah digunakan oleh akun lain.',
            'password.min' => 'Password baru minimal 6 karakter.',
        ]);

        if (! empty($validated['username'])) {
            $user->username = strtolower($validated['username']);
        }

        // Update roles jika ada input roles atau role
        $hasRolesInput = $request->has('roles') || ! empty($validated['role']);
        if ($hasRolesInput) {
            $rolesInput = $request->input('roles', []);
            if (empty($rolesInput) && ! empty($validated['role'])) {
                $rolesInput = explode(',', (string) $validated['role']);
            }
            $cleanRoles = self::parseRolesArray((array) $rolesInput);
            if (empty($cleanRoles)) {
                $cleanRoles = ['guru'];
            }

            // Prevent changing the role of the last admin
            if ($user->hasRole('admin') && ! in_array('admin', $cleanRoles, true) && User::withRole('admin')->count() <= 1) {
                return back()->with('error', 'Tidak dapat mengubah role satu-satunya Administrator di sistem.');
            }
            $user->role = implode(',', $cleanRoles);
        }

        if (array_key_exists('no_hp', $validated)) {
            $user->no_hp = $validated['no_hp'];
        }

        if (array_key_exists('name', $validated) && ! empty($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (! empty($validated['email'])) {
            $user->email = $validated['email'];
        }

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('pengaturan-akun.index')
            ->with('success', "Data akun '{$user->username}' berhasil diperbarui.");
    }

    /**
     * Remove the specified user account.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Prevent deleting last admin
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return redirect()->route('pengaturan-akun.index')
                ->with('error', 'Gagal! Tidak dapat menghapus satu-satunya akun Administrator dalam sistem.');
        }

        $username = $user->username ?: $user->name;
        $user->delete();

        return redirect()->route('pengaturan-akun.index')
            ->with('success', "Akun pengguna '{$username}' berhasil dihapus.");
    }

    /**
     * Reset student's password back to their NISN.
     */
    public function resetPasswordSiswa(Siswa $siswa): RedirectResponse
    {
        $defaultPassword = $siswa->nisn ?: $siswa->nis;

        if (! $defaultPassword) {
            return back()->with('error', 'Gagal mereset: Siswa tidak memiliki data NISN atau NIS.');
        }

        $user = User::where('siswa_id', $siswa->id)->first();
        if (! $user) {
            $user = User::create([
                'siswa_id' => $siswa->id,
                'username' => $defaultPassword,
                'name' => $siswa->nama,
                'email' => $defaultPassword.'@siswa.man2balam.sch.id',
                'role' => 'siswa',
                'no_hp' => $siswa->no_hp_ortu,
                'password' => Hash::make($defaultPassword),
                'password_changed_at' => null,
            ]);
        } else {
            $user->username = $defaultPassword;
            $user->password = Hash::make($defaultPassword);
            $user->password_changed_at = null;
            $user->save();
        }

        return redirect()->route('pengaturan-akun.index', ['tab' => 'siswa'])
            ->with('success', "Password akun siswa '{$siswa->nama}' (NISN: {$defaultPassword}) berhasil direset kembali menjadi nomor NISN.");
    }

    /**
     * Reset all students in a class back to their NISN.
     */
    public function resetPasswordKelas(Request $request): RedirectResponse
    {
        $request->validate([
            'kelas' => ['required', 'string'],
        ], [
            'kelas.required' => 'Pilih kelas yang ingin direset.',
        ]);

        $kelas = $request->input('kelas');
        $siswas = Siswa::where('kelas', $kelas)->get();

        if ($siswas->isEmpty()) {
            return back()->with('error', "Tidak ada data siswa pada kelas {$kelas}.");
        }

        $count = 0;
        foreach ($siswas as $siswa) {
            $defaultPassword = $siswa->nisn ?: $siswa->nis;
            if (! $defaultPassword) {
                continue;
            }

            $user = User::where('siswa_id', $siswa->id)->first();
            if (! $user) {
                User::create([
                    'siswa_id' => $siswa->id,
                    'username' => $defaultPassword,
                    'name' => $siswa->nama,
                    'email' => $defaultPassword.'@siswa.man2balam.sch.id',
                    'role' => 'siswa',
                    'no_hp' => $siswa->no_hp_ortu,
                    'password' => Hash::make($defaultPassword),
                    'password_changed_at' => null,
                ]);
            } else {
                $user->username = $defaultPassword;
                $user->password = Hash::make($defaultPassword);
                $user->password_changed_at = null;
                $user->save();
            }
            $count++;
        }

        return redirect()->route('pengaturan-akun.index', ['tab' => 'siswa', 'kelas_siswa' => $kelas])
            ->with('success', "Sebanyak {$count} akun siswa di kelas {$kelas} berhasil direset passwordnya menjadi nomor NISN masing-masing.");
    }

    /**
     * Export users or students list to Excel (.xlsx).
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $tab = $request->input('tab', 'pegawai');

        if ($tab === 'siswa') {
            $siswaQuery = Siswa::with('user');
            if ($kelas = $request->input('kelas_siswa')) {
                $siswaQuery->where('kelas', $kelas);
            }
            $siswas = $siswaQuery->orderBy('kelas')->orderBy('nama')->get();

            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Data Akun Siswa');

            $headers = ['NO', 'NISN (USERNAME)', 'NIS', 'NAMA SISWA', 'KELAS', 'STATUS PASSWORD', 'NO HP ORTU'];
            $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

            foreach ($headers as $index => $header) {
                $col = $cols[$index];
                $sheet->setCellValue("{$col}1", $header);
                $sheet->getStyle("{$col}1")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle("{$col}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('065F46');
                $sheet->getStyle("{$col}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $rowNum = 2;
            foreach ($siswas as $idx => $s) {
                $statusPwd = ($s->user && $s->user->password_changed_at) ? 'Kustom (Sudah Diubah)' : 'Default (NISN)';

                $sheet->setCellValue("A{$rowNum}", $idx + 1);
                $sheet->setCellValueExplicit("B{$rowNum}", $s->nisn ?: '-', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("C{$rowNum}", $s->nis ?: '-', DataType::TYPE_STRING);
                $sheet->setCellValue("D{$rowNum}", $s->nama);
                $sheet->setCellValue("E{$rowNum}", $s->kelas);
                $sheet->setCellValue("F{$rowNum}", $statusPwd);
                $sheet->setCellValueExplicit("G{$rowNum}", $s->no_hp_ortu ?: '-', DataType::TYPE_STRING);

                $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("F{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $rowNum++;
            }

            $sheet->getStyle('A1:G'.($rowNum - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            foreach ($cols as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $fileName = 'Data_Akun_Siswa_'.date('Y-m-d').'.xlsx';

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        $query = User::where('role', '!=', 'siswa');

        // Filter role mendukung multi-role (misal: guru,wali_kelas)
        if ($role = $request->input('role')) {
            $query->withRole($role);
        }

        $users = $query->orderBy('role')->orderBy('username')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Akun Pengguna');

        // Headers
        $headers = ['NO', 'USERNAME', 'NAMA LENGKAP', 'ROLE', 'NO HP', 'EMAIL', 'TANGGAL DIBUAT'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

        foreach ($headers as $index => $header) {
            $col = $cols[$index];
            $sheet->setCellValue("{$col}1", $header);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("{$col}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E293B');
            $sheet->getStyle("{$col}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $rowNum = 2;
        foreach ($users as $idx => $u) {
            $sheet->setCellValue("A{$rowNum}", $idx + 1);
            $sheet->setCellValueExplicit("B{$rowNum}", $u->username ?: '-', DataType::TYPE_STRING);
            $sheet->setCellValue("C{$rowNum}", $u->name ?: '-');
            $sheet->setCellValue("D{$rowNum}", strtoupper($u->role ?: 'ADMIN'));
            $sheet->setCellValueExplicit("E{$rowNum}", $u->no_hp ?: '-', DataType::TYPE_STRING);
            $sheet->setCellValue("F{$rowNum}", $u->email ?: '-');
            $sheet->setCellValue("G{$rowNum}", $u->created_at ? $u->created_at->format('d/m/Y H:i') : '-');

            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $rowNum++;
        }

        // Table borders
        $sheet->getStyle('A1:G'.($rowNum - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'Data_Akun_Pengguna_'.date('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export users list to PDF.
     */
    public function exportPdf(Request $request): Response
    {
        $tab = $request->input('tab', 'pegawai');

        if ($tab === 'siswa') {
            $siswaQuery = Siswa::with('user');
            if ($kelas = $request->input('kelas_siswa')) {
                $siswaQuery->where('kelas', $kelas);
            }
            $siswas = $siswaQuery->orderBy('kelas')->orderBy('nama')->get();
            $date = date('d F Y, H:i').' WIB';

            $pdf = Pdf::loadView('pengaturan-akun.pdf-siswa', compact('siswas', 'date'))
                ->setPaper('a4', 'portrait');

            return $pdf->download('Data_Akun_Siswa_'.date('Y-m-d').'.pdf');
        }

        $query = User::where('role', '!=', 'siswa');

        // Filter role mendukung multi-role (misal: guru,wali_kelas)
        if ($role = $request->input('role')) {
            $query->withRole($role);
        }

        $users = $query->orderBy('role')->orderBy('username')->get();
        $date = date('d F Y, H:i').' WIB';

        $pdf = Pdf::loadView('pengaturan-akun.pdf', compact('users', 'date'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('Data_Akun_Pengguna_'.date('Y-m-d').'.pdf');
    }
}
