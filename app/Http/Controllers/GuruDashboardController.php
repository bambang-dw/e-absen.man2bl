<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\AbsensiGuru;
use App\Models\Jadwal;
use App\Models\SesiAbsensi;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GuruDashboardController extends Controller
{
    /**
     * Display teacher dashboard (monitoring kehadiran, akses scanner, dan kelola izin/sakit siswa).
     */
    public function indexGuru(Request $request): View|JsonResponse
    {
        $now = Carbon::now();
        $today = $now->toDateString();
        $tanggal = $request->input('tanggal', $today);
        $kelasFilter = $request->input('kelas', '');
        $statusFilter = $request->input('status', '');
        $search = $request->input('search', '');

        $daftarKelas = Siswa::select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
        $sesis = SesiAbsensi::where('is_aktif', true)->orderBy('jam_mulai')->orderBy('id')->get();
        $sesiAktif = SesiAbsensi::getSesiAktif($now->toTimeString());

        // 1. Statistik Presensi pada tanggal terpilih
        $absensiTanggal = Absensi::with('siswa', 'sesi')->where('tanggal', $tanggal)->get();
        $totalSiswa = Siswa::count();
        $jumlahTepatWaktu = $absensiTanggal->where('status', 'Tepat Waktu')->pluck('nis')->unique()->count();
        $jumlahTerlambat = $absensiTanggal->where('status', 'Terlambat')->pluck('nis')->unique()->count();
        $jumlahIzin = $absensiTanggal->filter(fn ($a) => in_array($a->status, ['Izin', 'Ijin']))->pluck('nis')->unique()->count();
        $jumlahSakit = $absensiTanggal->where('status', 'Sakit')->pluck('nis')->unique()->count();
        $totalHadir = $jumlahTepatWaktu + $jumlahTerlambat;
        $jumlahAlfa = max(0, $totalSiswa - ($totalHadir + $jumlahIzin + $jumlahSakit));

        // 2. Query Siswa yang berstatus Izin & Sakit
        $izinSakitQuery = Absensi::with('siswa', 'sesi')
            ->whereIn('status', ['Izin', 'Ijin', 'Sakit'])
            ->where('tanggal', $tanggal);

        if (! empty($kelasFilter)) {
            $izinSakitQuery->whereHas('siswa', fn ($q) => $q->where('kelas', $kelasFilter));
        }

        if (! empty($statusFilter)) {
            if ($statusFilter === 'Izin') {
                $izinSakitQuery->whereIn('status', ['Izin', 'Ijin']);
            } else {
                $izinSakitQuery->where('status', $statusFilter);
            }
        }

        if (! empty($search)) {
            $izinSakitQuery->whereHas('siswa', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        $daftarIzinSakit = $izinSakitQuery->orderBy('jam_scan', 'desc')->paginate(10, ['*'], 'page')->withQueryString();

        // 3. Daftar Semua Siswa untuk Pilihan Modal Tambah Izin/Sakit
        $semuaSiswa = Siswa::select('id', 'nis', 'nisn', 'nama', 'kelas')->orderBy('kelas')->orderBy('nama')->get();

        // 4. Riwayat Presensi Terkini Hari Ini
        $presensiTerbaru = Absensi::with(['siswa', 'petugas'])
            ->where('tanggal', $tanggal)
            ->orderBy('jam_scan', 'desc')
            ->take(8)
            ->get();

        // 5. Status pencatatan Absensi Mengajar guru hari ini (mencegah duplikasi & mengingatkan jam yang belum dicatat)
        $statusMengajar = $this->buildStatusMengajar(Auth::user(), $tanggal);

        if ($request->wantsJson() || $request->boolean('live')) {
            return response()->json([
                'totalSiswa' => $totalSiswa,
                'jumlahHadir' => $totalHadir,
                'jumlahTepatWaktu' => $jumlahTepatWaktu,
                'jumlahTerlambat' => $jumlahTerlambat,
                'jumlahIzin' => $jumlahIzin,
                'jumlahSakit' => $jumlahSakit,
                'jumlahAlfa' => $jumlahAlfa,
                'sesiAktif' => $sesiAktif ? [
                    'id' => $sesiAktif->id,
                    'nama_sesi' => $sesiAktif->nama_sesi,
                    'jam_mulai' => substr($sesiAktif->jam_mulai, 0, 5),
                    'jam_terlambat' => substr($sesiAktif->jam_terlambat, 0, 5),
                    'jam_selesai' => substr($sesiAktif->jam_selesai, 0, 5),
                ] : null,
                'presensiTerbaru' => $presensiTerbaru->map(fn ($p) => [
                    'id' => $p->id,
                    'nis' => $p->nis,
                    'nama' => $p->siswa?->nama ?? '-',
                    'kelas' => $p->siswa?->kelas ?? '-',
                    'foto_url' => $p->siswa ? $p->siswa->foto_url : 'https://ui-avatars.com/api/?name=Siswa',
                    'jam_scan' => substr($p->jam_scan, 0, 5),
                    'status' => $p->status,
                    'petugas' => $p->petugas?->name,
                ])->values(),
            ]);
        }

        return view('guru-dashboard.index', compact(
            'today',
            'tanggal',
            'kelasFilter',
            'statusFilter',
            'search',
            'daftarKelas',
            'sesis',
            'sesiAktif',
            'totalSiswa',
            'jumlahTepatWaktu',
            'jumlahTerlambat',
            'jumlahIzin',
            'jumlahSakit',
            'jumlahAlfa',
            'daftarIzinSakit',
            'semuaSiswa',
            'presensiTerbaru',
            'statusMengajar'
        ));
    }

    /**
     * Build the teacher's teaching-attendance status for a given date.
     *
     * @return array{tanggal: string, total_jadwal: int, total_tercatat: int, sudah_lengkap: bool, jadwal: array<int, array<string, mixed>>, riwayat_terakhir: array<int, array<string, mixed>>}
     */
    protected function buildStatusMengajar(?User $user, string $tanggal): array
    {
        $hasil = [
            'tanggal' => $tanggal,
            'total_jadwal' => 0,
            'total_tercatat' => 0,
            'sudah_lengkap' => false,
            'jadwal' => [],
            'riwayat_terakhir' => [],
        ];

        if (! $user) {
            return $hasil;
        }

        $hari = AbsensiMengajarController::getHariIndonesia(Carbon::parse($tanggal));

        // Jadwal guru pada tanggal terpilih
        $jadwals = Jadwal::with(['dataKelas', 'mapel'])
            ->where('hari', $hari)
            ->where(function ($q) use ($user) {
                $q->where('nama_pengajar', (string) $user->id)
                    ->orWhere('nama_pengajar', $user->username);
            })
            ->orderBy('waktu_mulai', 'asc')
            ->get();

        // Catatan absensi mengajar guru pada tanggal terpilih
        $riwayat = AbsensiGuru::with('dataKelas')
            ->whereDate('tanggal', $tanggal)
            ->where(function ($q) use ($user) {
                $q->where('guru_id', $user->id)
                    ->orWhere('nama_guru', (string) $user->id)
                    ->orWhere('nama_guru', $user->name ?: $user->username)
                    ->orWhere('nama_guru', $user->username);
            })
            ->orderBy('id', 'desc')
            ->get();

        foreach ($jadwals as $jadwal) {
            $catatan = $riwayat->first(
                fn (AbsensiGuru $r) => (int) $r->kelas === (int) $jadwal->kelas
                    && $r->matchesJamKe((string) $jadwal->jam_ke)
            );

            $hasil['jadwal'][] = [
                'jam_ke' => $jadwal->jam_ke,
                'waktu' => substr((string) $jadwal->waktu_mulai, 0, 5).' - '.substr((string) $jadwal->waktu_selesai, 0, 5),
                'kelas' => $jadwal->dataKelas?->kelas ?? '-',
                'mapel' => $jadwal->mapel?->kode_mapel ?: (string) $jadwal->mata_pelajaran,
                'sudah_tercatat' => (bool) $catatan,
                'jam_catat' => $catatan?->created_at?->format('H:i'),
            ];
        }

        $jumlahJadwal = count($hasil['jadwal']);
        $jumlahTercatat = collect($hasil['jadwal'])->where('sudah_tercatat', true)->count();

        return [
            'tanggal' => Carbon::parse($tanggal)->translatedFormat('d F Y'),
            'total_jadwal' => $jumlahJadwal,
            'total_tercatat' => $jumlahTercatat,
            'sudah_lengkap' => $jumlahJadwal > 0 && $jumlahTercatat >= $jumlahJadwal,
            'jadwal' => $hasil['jadwal'],
            'riwayat_terakhir' => [
                'jumlah' => $riwayat->count(),
                'terakhir' => $riwayat->first(),
            ],
        ];
    }

    /**
     * Simpan keterangan presensi Izin atau Sakit untuk siswa.
     */
    public function storeIzinSakit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'exists:siswas,nis'],
            'status' => ['required', 'string', 'in:Izin,Ijin,Sakit'],
            'tanggal' => ['required', 'date'],
            'sesi_id' => ['nullable', 'exists:sesi_absensis,id'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'nis.required' => 'Pilih siswa terlebih dahulu.',
            'nis.exists' => 'Data siswa tidak valid atau tidak ditemukan.',
            'status.required' => 'Pilih status presensi (Izin atau Sakit).',
            'tanggal.required' => 'Tanggal presensi wajib diisi.',
        ]);

        $status = in_array($validated['status'], ['Izin', 'Ijin']) ? 'Izin' : 'Sakit';
        $sesiId = $validated['sesi_id'] ?? SesiAbsensi::where('is_aktif', true)->first()?->id ?? SesiAbsensi::first()?->id;
        if (! $sesiId) {
            $defaultSesi = SesiAbsensi::firstOrCreate(
                ['kode_sesi' => 'pagi'],
                [
                    'nama_sesi' => 'Sesi Pagi',
                    'jam_mulai' => '06:00:00',
                    'jam_terlambat' => '07:15:00',
                    'jam_selesai' => '08:00:00',
                    'is_aktif' => true,
                ]
            );
            $sesiId = $defaultSesi->id;
        }

        $absensi = Absensi::updateOrCreate(
            [
                'nis' => $validated['nis'],
                'tanggal' => $validated['tanggal'],
                'sesi_id' => $sesiId,
            ],
            [
                'user_id' => Auth::id(),
                'jam_scan' => Carbon::now()->toTimeString(),
                'status' => $status,
                'keterangan' => $validated['keterangan'] ?? null,
            ]
        );

        $siswa = Siswa::where('nis', $validated['nis'])->first();
        $namaSiswa = $siswa ? "{$siswa->nama} ({$siswa->kelas})" : "NIS {$validated['nis']}";

        return redirect()->back()
            ->with('success', "Presensi {$status} untuk siswa {$namaSiswa} pada tanggal {$validated['tanggal']} berhasil disimpan.");
    }

    /**
     * Perbarui data keterangan Izin atau Sakit.
     */
    public function updateIzinSakit(Request $request, Absensi $absensi): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:Izin,Ijin,Sakit,Tepat Waktu,Terlambat,Alfa'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'status.required' => 'Pilih status presensi.',
        ]);

        $absensi->update([
            'status' => in_array($validated['status'], ['Izin', 'Ijin']) ? 'Izin' : $validated['status'],
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Data presensi berhasil diperbarui.');
    }

    /**
     * Hapus data presensi Izin / Sakit.
     */
    public function destroyIzinSakit(Absensi $absensi): RedirectResponse
    {
        $namaSiswa = $absensi->siswa?->nama ?? 'Siswa';
        $status = $absensi->status;
        $absensi->delete();

        return redirect()->back()->with('success', "Catatan presensi {$status} untuk {$namaSiswa} berhasil dihapus.");
    }

    /**
     * Display homeroom teacher (Wali Kelas) dashboard with monthly, semester, and daily attendance recaps.
     */
    public function indexWaliKelas(Request $request): View
    {
        $now = Carbon::now();
        $currentUser = Auth::user();

        // Deteksi kelas wali kelas
        if ($currentUser && $currentUser->hasRole('wali_kelas') && ! $currentUser->hasRole('admin')) {
            $daftarKelas = $currentUser->getDaftarKelasWali();
            if ($daftarKelas->isEmpty()) {
                $daftarKelas = Siswa::select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
            }
        } else {
            $daftarKelas = Siswa::select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
        }

        $defaultKelas = $daftarKelas->first() ?? 'X A';
        $kelas = $request->input('kelas', $defaultKelas);

        // Kunci hanya pada kelas binaannya jika bukan admin
        if ($currentUser && $currentUser->hasRole('wali_kelas') && ! $currentUser->hasRole('admin')) {
            if (! $daftarKelas->contains($kelas)) {
                $kelas = $defaultKelas;
            }
        }

        $mode = $request->input('mode', 'bulan'); // 'bulan', 'semester', 'harian'

        $siswaList = Siswa::where('kelas', $kelas)->orderBy('nama')->get();
        $nisList = $siswaList->pluck('nis');
        $totalSiswaKelas = $siswaList->count();

        // -------------------------------------------------------------
        // 1. REKAP BULANAN (Pilihan Rekap Bulan)
        // -------------------------------------------------------------
        $bulanInput = $request->input('bulan', $now->format('Y-m'));
        try {
            $carbonBulan = Carbon::createFromFormat('Y-m', $bulanInput)->startOfMonth();
        } catch (\Exception $e) {
            $carbonBulan = $now->copy()->startOfMonth();
            $bulanInput = $now->format('Y-m');
        }

        $startOfMonth = $carbonBulan->copy()->startOfMonth()->toDateString();
        $endOfMonth = $carbonBulan->copy()->endOfMonth()->toDateString();
        $namaBulan = $carbonBulan->translatedFormat('F Y');

        $absensiBulan = Absensi::whereIn('nis', $nisList)
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->get();

        $distinctDatesBulan = $absensiBulan->pluck('tanggal')->unique()->count();
        $hariEfektifBulan = max(1, $distinctDatesBulan ?: 1);

        $rekapBulanSiswa = [];
        foreach ($siswaList as $s) {
            $records = $absensiBulan->where('nis', $s->nis);
            $tepat = $records->where('status', 'Tepat Waktu')->count();
            $lambat = $records->where('status', 'Terlambat')->count();
            $izin = $records->filter(fn ($a) => in_array($a->status, ['Izin', 'Ijin']))->count();
            $sakit = $records->where('status', 'Sakit')->count();
            $alfa = $records->filter(fn ($a) => in_array($a->status, ['Alfa', 'Alpha']))->count();

            $totalHadir = $tepat + $lambat;
            $persentase = round(($totalHadir / $hariEfektifBulan) * 100, 1);
            $persentase = min(100, $persentase);

            $rekapBulanSiswa[] = [
                'siswa' => $s,
                'tepat' => $tepat,
                'lambat' => $lambat,
                'izin' => $izin,
                'sakit' => $sakit,
                'alfa' => $alfa,
                'total_hadir' => $totalHadir,
                'persentase' => $persentase,
            ];
        }

        $bulanTotalTepat = collect($rekapBulanSiswa)->sum('tepat');
        $bulanTotalLambat = collect($rekapBulanSiswa)->sum('lambat');
        $bulanTotalIzin = collect($rekapBulanSiswa)->sum('izin');
        $bulanTotalSakit = collect($rekapBulanSiswa)->sum('sakit');
        $bulanTotalAlfa = collect($rekapBulanSiswa)->sum('alfa');
        $bulanRataRataHadir = count($rekapBulanSiswa) > 0 ? round(collect($rekapBulanSiswa)->avg('persentase'), 1) : 0;

        // -------------------------------------------------------------
        // 2. REKAP SATU SEMESTER (Pilihan Rekap Semester)
        // -------------------------------------------------------------
        $defaultSemester = $now->month >= 7 ? 'ganjil' : 'genap';
        $semesterInput = $request->input('semester', $defaultSemester);
        $tahunInput = (int) $request->input('tahun', $now->year);

        if ($semesterInput === 'ganjil') {
            $startOfSemester = Carbon::create($tahunInput, 7, 1)->toDateString();
            $endOfSemester = Carbon::create($tahunInput, 12, 31)->toDateString();
            $namaSemester = 'Semester Ganjil TA '.$tahunInput.'/'.($tahunInput + 1);
        } else {
            $startOfSemester = Carbon::create($tahunInput, 1, 1)->toDateString();
            $endOfSemester = Carbon::create($tahunInput, 6, 30)->toDateString();
            $namaSemester = 'Semester Genap TA '.($tahunInput - 1).'/'.$tahunInput;
        }

        $absensiSemester = Absensi::whereIn('nis', $nisList)
            ->whereBetween('tanggal', [$startOfSemester, $endOfSemester])
            ->get();

        $distinctDatesSemester = $absensiSemester->pluck('tanggal')->unique()->count();
        $hariEfektifSemester = max(1, $distinctDatesSemester ?: 1);

        $rekapSemesterSiswa = [];
        foreach ($siswaList as $s) {
            $records = $absensiSemester->where('nis', $s->nis);
            $tepat = $records->where('status', 'Tepat Waktu')->count();
            $lambat = $records->where('status', 'Terlambat')->count();
            $izin = $records->filter(fn ($a) => in_array($a->status, ['Izin', 'Ijin']))->count();
            $sakit = $records->where('status', 'Sakit')->count();
            $alfa = $records->filter(fn ($a) => in_array($a->status, ['Alfa', 'Alpha']))->count();

            $totalHadir = $tepat + $lambat;
            $persentase = round(($totalHadir / $hariEfektifSemester) * 100, 1);
            $persentase = min(100, $persentase);

            $predikat = match (true) {
                $persentase >= 90 => 'Sangat Baik',
                $persentase >= 80 => 'Baik',
                $persentase >= 70 => 'Cukup',
                default => 'Perlu Perhatian',
            };

            $rekapSemesterSiswa[] = [
                'siswa' => $s,
                'tepat' => $tepat,
                'lambat' => $lambat,
                'izin' => $izin,
                'sakit' => $sakit,
                'alfa' => $alfa,
                'total_hadir' => $totalHadir,
                'persentase' => $persentase,
                'predikat' => $predikat,
            ];
        }

        $semesterTotalTepat = collect($rekapSemesterSiswa)->sum('tepat');
        $semesterTotalLambat = collect($rekapSemesterSiswa)->sum('lambat');
        $semesterTotalIzin = collect($rekapSemesterSiswa)->sum('izin');
        $semesterTotalSakit = collect($rekapSemesterSiswa)->sum('sakit');
        $semesterTotalAlfa = collect($rekapSemesterSiswa)->sum('alfa');
        $semesterRataRataHadir = count($rekapSemesterSiswa) > 0 ? round(collect($rekapSemesterSiswa)->avg('persentase'), 1) : 0;

        // -------------------------------------------------------------
        // 3. PRESENSI HARIAN (Untuk pantauan harian cepat)
        // -------------------------------------------------------------
        $tanggalHarian = $request->input('tanggal', Carbon::today()->toDateString());
        $absensiHarian = Absensi::whereIn('nis', $nisList)
            ->where('tanggal', $tanggalHarian)
            ->get();

        $harianHadirCount = 0;
        $harianTepatCount = 0;
        $harianLambatCount = 0;
        $harianIzinCount = 0;
        $harianSakitCount = 0;
        $harianAlfaCount = 0;

        $rekapHarianSiswa = [];
        foreach ($siswaList as $s) {
            $rec = $absensiHarian->where('nis', $s->nis)->first();
            $status = $rec ? $rec->status : 'Alfa';
            $jamScan = $rec ? $rec->jam_scan : '-';

            if ($status === 'Tepat Waktu') {
                $harianTepatCount++;
                $harianHadirCount++;
            } elseif ($status === 'Terlambat') {
                $harianLambatCount++;
                $harianHadirCount++;
            } elseif (in_array($status, ['Izin', 'Ijin'])) {
                $harianIzinCount++;
            } elseif ($status === 'Sakit') {
                $harianSakitCount++;
            } else {
                $harianAlfaCount++;
            }

            $rekapHarianSiswa[] = [
                'siswa' => $s,
                'status' => $status,
                'jam_scan' => $jamScan,
            ];
        }
        $harianPersentase = $totalSiswaKelas > 0 ? round(($harianHadirCount / $totalSiswaKelas) * 100, 1) : 0;

        return view('walikelas-dashboard.index', compact(
            'kelas',
            'daftarKelas',
            'mode',
            'totalSiswaKelas',
            // Data Rekap Bulan
            'bulanInput',
            'namaBulan',
            'hariEfektifBulan',
            'rekapBulanSiswa',
            'bulanTotalTepat',
            'bulanTotalLambat',
            'bulanTotalIzin',
            'bulanTotalSakit',
            'bulanTotalAlfa',
            'bulanRataRataHadir',
            // Data Rekap Semester
            'semesterInput',
            'tahunInput',
            'namaSemester',
            'hariEfektifSemester',
            'rekapSemesterSiswa',
            'semesterTotalTepat',
            'semesterTotalLambat',
            'semesterTotalIzin',
            'semesterTotalSakit',
            'semesterTotalAlfa',
            'semesterRataRataHadir',
            // Data Harian
            'tanggalHarian',
            'rekapHarianSiswa',
            'harianHadirCount',
            'harianTepatCount',
            'harianLambatCount',
            'harianIzinCount',
            'harianSakitCount',
            'harianAlfaCount',
            'harianPersentase'
        ));
    }
}
