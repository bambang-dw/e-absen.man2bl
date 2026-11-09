<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\JamBelajar;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\SesiAbsensi;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard page or return live JSON stats for real-time updates.
     */
    public function index(Request $request): View|JsonResponse
    {
        $today = Carbon::today()->toDateString();
        $currentTime = Carbon::now()->toTimeString();

        // Status hari libur (5 hari belajar: Sabtu & Minggu libur, serta kalender libur khusus)
        $statusHariIni = HariLibur::statusLibur();

        // Cek apakah instruksi "Pulang Lebih Awal" sedang aktif hari ini
        $pulangAwal = Cache::get('pulang_awal_'.$today);

        // 1. Total Siswa
        $totalSiswa = Siswa::count();

        // 2. Query absensi hari ini
        $absensiHariIni = Absensi::with(['siswa', 'sesi'])
            ->where('tanggal', $today)
            ->get();

        // Jumlah siswa yang sudah melakukan presensi (unique NIS)
        $jumlahSudahPresensi = $absensiHariIni->pluck('nis')->unique()->count();

        // Hadir Tepat Waktu
        $jumlahTepatWaktu = $absensiHariIni->where('status', 'Tepat Waktu')->pluck('nis')->unique()->count();

        // Terlambat
        $jumlahTerlambat = $absensiHariIni->where('status', 'Terlambat')->pluck('nis')->unique()->count();

        // Ijin & Sakit
        $jumlahIjin = $absensiHariIni->filter(fn ($a) => in_array($a->status, ['Izin', 'Ijin']))->pluck('nis')->unique()->count();
        $jumlahSakit = $absensiHariIni->where('status', 'Sakit')->pluck('nis')->unique()->count();
        $totalIzinSakit = $jumlahIjin + $jumlahSakit;

        // Explicit Alfa records
        $explicitAlfa = $absensiHariIni->whereIn('status', ['Alfa', 'Alpha'])->pluck('nis')->unique()->count();

        // Belum Presensi / Alpha
        $jumlahAlpha = max(0, $totalSiswa - $jumlahSudahPresensi) + $explicitAlfa;

        // Persentase Kehadiran
        $persentaseKehadiran = $totalSiswa > 0 ? round(($jumlahSudahPresensi / $totalSiswa) * 100, 1) : 0;

        // 3. Sesi Absensi saat ini / aktif (otomatis berpindah ke sesi berikutnya jika sesi sebelumnya telah selesai)
        $sesiAktif = SesiAbsensi::getSesiAktif($currentTime);
        $activeSesiIds = SesiAbsensi::getActiveSesiIds($sesiAktif, $currentTime);
        $allSesi = SesiAbsensi::where('is_aktif', true)->orderBy('jam_mulai')->orderBy('id')->get();

        // 4. Data Presensi Terbaru Hari Ini (10 aktivitas terkini beserta identitas petugas scanner)
        $presensiTerbaru = Absensi::with(['siswa', 'sesi', 'petugas'])
            ->where('tanggal', $today)
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        // 5. Statistik Kehadiran per Kelas (untuk chart & ringkasan)
        $daftarKelas = Siswa::select('kelas')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas');

        $chartKelas = [];
        foreach ($daftarKelas as $kelas) {
            $siswaKelas = Siswa::where('kelas', $kelas)->get();
            $nisKelas = $siswaKelas->pluck('nis');
            $countTotal = $siswaKelas->count();

            $absensiKelas = $absensiHariIni->whereIn('nis', $nisKelas);
            $countHadir = $absensiKelas->pluck('nis')->unique()->count();
            $countTepat = $absensiKelas->where('status', 'Tepat Waktu')->pluck('nis')->unique()->count();
            $countLambat = $absensiKelas->where('status', 'Terlambat')->pluck('nis')->unique()->count();
            $countIzin = $absensiKelas->filter(fn ($a) => in_array($a->status, ['Izin', 'Ijin', 'Sakit']))->pluck('nis')->unique()->count();
            $countAlfa = max(0, $countTotal - $countHadir);

            $chartKelas[] = [
                'kelas' => $kelas,
                'total' => $countTotal,
                'hadir' => $countHadir,
                'tepat_waktu' => $countTepat,
                'terlambat' => $countLambat,
                'izin' => $countIzin,
                'alfa' => $countAlfa,
                'persentase' => $countTotal > 0 ? round(($countHadir / $countTotal) * 100, 1) : 0,
            ];
        }

        // Daftar hari libur mendatang untuk widget kalender dashboard
        $daftarHariLiburUpcoming = HariLibur::where('tanggal', '>=', $today)
            ->orderBy('tanggal')
            ->take(5)
            ->get();

        // Data Jam Belajar & Mata Pelajaran untuk tabel di Dashboard Admin
        $jamBelajars = JamBelajar::orderBy('waktu_mulai', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $mapels = Mapel::with(['dataKelas', 'pengajar'])
            ->orderBy('id', 'asc')
            ->get();

        $daftarKelasModel = Kelas::orderBy('tahun_pelajaran', 'desc')
            ->orderBy('kelas', 'asc')
            ->get();

        $daftarPengajarModel = User::where(function ($q) {
            $q->whereNull('role')
                ->orWhere('role', '!=', 'siswa');
        })
            ->orderBy('name', 'asc')
            ->orderBy('username', 'asc')
            ->get();

        if ($request->wantsJson() || $request->boolean('live')) {
            return response()->json([
                'totalSiswa' => $totalSiswa,
                'jumlahSudahPresensi' => $jumlahSudahPresensi,
                'persentaseKehadiran' => $persentaseKehadiran,
                'jumlahTepatWaktu' => $jumlahTepatWaktu,
                'jumlahTerlambat' => $jumlahTerlambat,
                'jumlahIjin' => $jumlahIjin,
                'jumlahSakit' => $jumlahSakit,
                'totalIzinSakit' => $totalIzinSakit,
                'jumlahAlpha' => $jumlahAlpha,
                'sesiAktif' => $sesiAktif ? [
                    'id' => $sesiAktif->id,
                    'nama_sesi' => $sesiAktif->nama_sesi,
                    'jam_mulai' => substr($sesiAktif->jam_mulai, 0, 5),
                    'jam_selesai' => substr($sesiAktif->jam_selesai, 0, 5),
                ] : null,
                'chartKelas' => $chartKelas,
                'presensiTerbaru' => $presensiTerbaru->map(fn ($p) => [
                    'id' => $p->id,
                    'nis' => $p->nis,
                    'nama' => $p->siswa?->nama ?? 'Siswa Tidak Ditemukan',
                    'kelas' => $p->siswa?->kelas ?? '-',
                    'foto_url' => $p->siswa ? $p->siswa->foto_url : 'https://ui-avatars.com/api/?name=Siswa',
                    'nama_sesi' => $p->sesi?->nama_sesi ?? '-',
                    'jam_scan' => substr($p->jam_scan, 0, 5),
                    'status' => $p->status,
                    'petugas' => $p->petugas?->name,
                ])->values(),
            ]);
        }

        return view('dashboard.index', compact(
            'today',
            'currentTime',
            'statusHariIni',
            'pulangAwal',
            'daftarHariLiburUpcoming',
            'jamBelajars',
            'mapels',
            'daftarKelasModel',
            'daftarPengajarModel',
            'totalSiswa',
            'jumlahSudahPresensi',
            'jumlahTepatWaktu',
            'jumlahTerlambat',
            'jumlahIjin',
            'jumlahSakit',
            'totalIzinSakit',
            'jumlahAlpha',
            'persentaseKehadiran',
            'sesiAktif',
            'activeSesiIds',
            'allSesi',
            'presensiTerbaru',
            'chartKelas'
        ));
    }

    /**
     * Push pemulangan seluruh siswa lebih awal (kegiatan khusus / darurat).
     */
    public function pushPulangAwal(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'keterangan' => ['required', 'string', 'max:255'],
            'jam_pulang' => ['nullable', 'string'],
            'sesi_id' => ['nullable', 'exists:sesi_absensis,id'],
            'catat_otomatis' => ['nullable'],
        ], [
            'keterangan.required' => 'Keterangan/alasan pemulangan lebih awal wajib diisi.',
        ]);

        $today = Carbon::today()->toDateString();
        $jamPulang = ! empty($validated['jam_pulang']) ? $validated['jam_pulang'] : Carbon::now()->format('H:i:s');
        if (strlen($jamPulang) === 5) {
            $jamPulang .= ':00';
        }

        // Cari sesi pulang yang dituju atau default sesi pulang
        $sesiPulang = null;
        if (! empty($validated['sesi_id'])) {
            $sesiPulang = SesiAbsensi::find($validated['sesi_id']);
        }
        if (! $sesiPulang) {
            $sesiPulang = SesiAbsensi::where('is_aktif', true)->where('nama_sesi', 'like', '%pulang%')->first()
                ?? SesiAbsensi::where('is_aktif', true)->orderBy('jam_mulai', 'desc')->first();
        }

        if (! $sesiPulang) {
            return redirect()->back()->with('error', 'Tidak ditemukan sesi pulang aktif di sistem.');
        }

        $userId = Auth::id();
        $catatOtomatis = $request->boolean('catat_otomatis', true);
        $countDipulangkan = 0;

        if ($catatOtomatis) {
            // Ambil seluruh siswa yang hadir hari ini (status Tepat Waktu atau Terlambat)
            $hadirNisList = Absensi::where('tanggal', $today)
                ->whereIn('status', ['Tepat Waktu', 'Terlambat'])
                ->pluck('nis')
                ->unique();

            // Siswa yang sudah memiliki catatan sesi pulang hari ini
            $alreadyPulangNis = Absensi::where('tanggal', $today)
                ->where('sesi_id', $sesiPulang->id)
                ->pluck('nis')
                ->toArray();

            $nisToRecord = $hadirNisList->diff($alreadyPulangNis);

            foreach ($nisToRecord as $nis) {
                Absensi::create([
                    'nis' => $nis,
                    'sesi_id' => $sesiPulang->id,
                    'user_id' => $userId,
                    'tanggal' => $today,
                    'jam_scan' => $jamPulang,
                    'status' => 'Tepat Waktu',
                    'keterangan' => 'Dipulangkan lebih awal: '.$validated['keterangan'],
                ]);
                $countDipulangkan++;
            }
        }

        // Aktifkan flag Pulang Awal di cache hari ini
        Cache::put('pulang_awal_'.$today, [
            'aktif' => true,
            'keterangan' => $validated['keterangan'],
            'sesi_id' => $sesiPulang->id,
            'nama_sesi' => $sesiPulang->nama_sesi,
            'jam' => $jamPulang,
            'petugas' => Auth::user()?->name ?? 'Admin',
            'waktu_push' => Carbon::now()->toTimeString(),
            'total_dipulangkan' => $countDipulangkan,
        ], Carbon::now()->endOfDay());

        $pesan = 'Instruksi Pulang Lebih Awal Berhasil! ';
        if ($catatOtomatis) {
            $pesan .= "Sebanyak {$countDipulangkan} siswa yang hadir telah otomatis dicatat 'Pulang'. ";
        }
        $pesan .= "Sesi '{$sesiPulang->nama_sesi}' kini dibuka dan aktif di scanner presensi.";

        return redirect()->back()->with('success', $pesan);
    }

    /**
     * Batalkan mode pulang awal hari ini jika diperlukan.
     */
    public function batalkanPulangAwal(): RedirectResponse
    {
        $today = Carbon::today()->toDateString();
        Cache::forget('pulang_awal_'.$today);

        return redirect()->back()->with('info', 'Status pulang awal hari ini telah dinonaktifkan. Jadwal sesi kembali ke jam reguler.');
    }
}
