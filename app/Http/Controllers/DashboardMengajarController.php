<?php

namespace App\Http\Controllers;

use App\Models\AbsensiGuru;
use App\Models\Jadwal;
use App\Models\JamBelajar;
use App\Models\Kelas;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardMengajarController extends Controller
{
    /**
     * Determine the grade level ('X', 'XI', 'XII', or 'Lainnya') from a class name.
     */
    public static function resolveLevel(string $namaKelas): string
    {
        $upper = strtoupper(trim($namaKelas));
        if (str_starts_with($upper, 'XII') || str_starts_with($upper, '12')) {
            return 'XII';
        }
        if (str_starts_with($upper, 'XI') || str_starts_with($upper, '11')) {
            return 'XI';
        }
        if (str_starts_with($upper, 'X') || str_starts_with($upper, '10')) {
            return 'X';
        }

        return 'Lainnya';
    }

    /**
     * Display the Admin Dashboard Mengajar (schedule matrix per level with green/red teacher attendance indicators).
     */
    public function index(Request $request): View|JsonResponse
    {
        $today = Carbon::today()->toDateString();
        $tanggal = $request->input('tanggal', $today);
        $defaultHari = AbsensiMengajarController::getHariIndonesia(Carbon::parse($tanggal));

        $daftarHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        if ($request->filled('hari')) {
            $hariFilter = $request->input('hari');
        } elseif (in_array($defaultHari, $daftarHari, true)) {
            $hariFilter = $defaultHari;
        } else {
            $hariFilter = 'Senin';
        }

        $levelFilter = $request->input('level', '');
        $tahunFilter = $request->input('tahun_pelajaran', '');
        $semesterFilter = $request->input('semester', '');

        $daftarTahun = Kelas::select('tahun_pelajaran')
            ->distinct()
            ->orderBy('tahun_pelajaran', 'desc')
            ->pluck('tahun_pelajaran');

        $kelasQuery = Kelas::orderBy('kelas', 'asc');
        if (! empty($tahunFilter)) {
            $kelasQuery->where('tahun_pelajaran', $tahunFilter);
        }
        $semuaKelas = $kelasQuery->get();

        // Kelompokkan kelas berdasarkan Level (X, XI, XII, Lainnya)
        $groupedLevels = [
            'X' => collect(),
            'XI' => collect(),
            'XII' => collect(),
            'Lainnya' => collect(),
        ];

        foreach ($semuaKelas as $k) {
            $lvl = self::resolveLevel($k->kelas);
            if (! empty($levelFilter) && $lvl !== $levelFilter) {
                continue;
            }
            $groupedLevels[$lvl]->push($k);
        }

        // Hapus level yang kosong
        $groupedLevels = array_filter($groupedLevels, fn ($collection) => $collection->isNotEmpty());

        // Ambil daftar Jam Belajar
        $jamBelajars = JamBelajar::orderBy('waktu_mulai', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Ambil jadwal pada hari terpilih
        $jadwalQuery = Jadwal::with(['dataKelas', 'mapel', 'pengajar'])
            ->where('hari', $hariFilter);

        if (! empty($tahunFilter)) {
            $jadwalQuery->where('tahun_pelajaran', $tahunFilter);
        }
        if (! empty($semesterFilter)) {
            $jadwalQuery->where('semester', $semesterFilter);
        }

        $jadwals = $jadwalQuery->orderBy('waktu_mulai', 'asc')->get();

        // Jika tabel jam_belajars kosong namun ada data di jadwals, bangun baris jam dari jadwals
        if ($jamBelajars->isEmpty() && $jadwals->isNotEmpty()) {
            $jamBelajars = $jadwals->unique('jam_ke')->map(fn ($j) => (object) [
                'id' => $j->jam_belajar_id ?? $j->id,
                'jam_ke' => $j->jam_ke,
                'waktu_mulai' => $j->waktu_mulai,
                'waktu_selesai' => $j->waktu_selesai,
            ])->values();
        }

        // Ambil data absensi mengajar guru pada tanggal terpilih
        $absensiGurus = AbsensiGuru::with(['dataKelas', 'guru'])
            ->whereDate('tanggal', $tanggal)
            ->get();

        // Hitung statistik kehadiran mengajar hari ini
        $totalJadwalSlot = 0;
        $totalHadirSlot = 0;
        $totalTugasSlot = 0;
        $totalBelumHadirSlot = 0;

        // Bangun matriks jadwal per Level: [level => ['kelasList' => ..., 'rows' => ...]]
        $matrixPerLevel = [];

        foreach ($groupedLevels as $levelKey => $kelasList) {
            $rows = [];
            foreach ($jamBelajars as $jb) {
                $cells = [];
                foreach ($kelasList as $k) {
                    // Cari jadwal untuk kelas & jam_ke ini
                    $jadwalCell = $jadwals->first(function ($j) use ($k, $jb) {
                        return (int) $j->kelas === (int) $k->id
                            && trim((string) $j->jam_ke) === trim((string) $jb->jam_ke);
                    });

                    // Jika mapel kosong, abaikan jadwal
                    if ($jadwalCell && empty(trim((string) $jadwalCell->mata_pelajaran))) {
                        $jadwalCell = null;
                    }

                    // Cek catatan absensi_gurus untuk kelas & jam_ke ini (Hadir, Izin+Tugas, Sakit+Tugas, dll.)
                    $absensiMatch = $absensiGurus->first(function (AbsensiGuru $ag) use ($k, $jb, $jadwalCell) {
                        if (! $ag->matchesJamKe($jb->jam_ke)) {
                            return false;
                        }
                        if (! empty($ag->kelas)) {
                            return (int) $ag->kelas === (int) $k->id;
                        }
                        if ($jadwalCell) {
                            $agMapel = strtolower(trim($ag->mata_pelajaran));

                            return $agMapel === strtolower(trim($jadwalCell->mata_pelajaran))
                                || $agMapel === strtolower(trim($jadwalCell->tampil_mapel));
                        }

                        return false;
                    });

                    if ($jadwalCell || $absensiMatch) {
                        $mapelName = $jadwalCell
                            ? $jadwalCell->tampil_mapel
                            : $absensiMatch->mata_pelajaran;

                        $guruName = $jadwalCell
                            ? ($jadwalCell->pengajar ? ($jadwalCell->pengajar->name ?: $jadwalCell->pengajar->username) : ($absensiMatch ? $absensiMatch->nama_guru_tampil : '-'))
                            : $absensiMatch->nama_guru_tampil;

                        $hasTugas = $absensiMatch ? $absensiMatch->hasTugas() : false;
                        $isHadir = ($absensiMatch && ! $hasTugas) ? $absensiMatch->isHadir() : false;

                        // Guru belum diisi pada jadwal: tampil aktif, tetapi tidak dihitung dalam jadwal yang dipantau
                        $isGuruKosong = $jadwalCell && empty($jadwalCell->nama_pengajar);

                        // Kegiatan non-KBM tanpa guru (Upacara jam ke-1 Senin, Senam Pagi jam ke-1 Selasa) ditampilkan dengan warna mute
                        $isMute = $isGuruKosong
                            && trim((string) $jb->jam_ke) === '1'
                            && in_array(strtolower(trim((string) $jadwalCell->hari)), ['senin', 'selasa'], true);

                        if ($hasTugas) {
                            $indicator = 'cyan';
                            $statusLabel = $absensiMatch->status;
                        } elseif ($isHadir) {
                            $indicator = 'green';
                            $statusLabel = 'Hadir';
                        } elseif ($isMute) {
                            $indicator = 'mute';
                            $statusLabel = 'Kegiatan';
                        } elseif ($isGuruKosong) {
                            $indicator = 'orange';
                            $statusLabel = 'Guru Belum Diisi';
                        } else {
                            $indicator = 'red';
                            $statusLabel = $absensiMatch ? $absensiMatch->status : 'Belum Hadir';
                        }

                        if (! $isGuruKosong) {
                            $totalJadwalSlot++;
                            if ($hasTugas) {
                                $totalTugasSlot++;
                            } elseif ($isHadir) {
                                $totalHadirSlot++;
                            } else {
                                $totalBelumHadirSlot++;
                            }
                        }

                        $cells[$k->id] = [
                            'scheduled' => true,
                            'kelas_id' => $k->id,
                            'nama_kelas' => $k->kelas,
                            'jam_ke' => $jb->jam_ke,
                            'mata_pelajaran' => $mapelName,
                            'nama_pengajar' => $guruName,
                            'is_hadir' => $isHadir,
                            'is_tugas' => $hasTugas,
                            'is_guru_kosong' => $isGuruKosong,
                            'is_mute' => $isMute,
                            'is_dipantau' => ! $isGuruKosong,
                            'indicator' => $indicator,
                            'status_label' => $statusLabel,
                            'materi' => $absensiMatch?->materi_pembelajaran,
                            'waktu_scan' => $absensiMatch?->created_at?->format('H:i'),
                            'foto_url' => $absensiMatch?->foto_url,
                            'foto_nama' => $absensiMatch?->foto ? basename($absensiMatch->foto) : null,
                        ];
                    } else {
                        $cells[$k->id] = [
                            'scheduled' => false,
                            'kelas_id' => $k->id,
                            'nama_kelas' => $k->kelas,
                            'jam_ke' => $jb->jam_ke,
                            'mata_pelajaran' => null,
                            'nama_pengajar' => null,
                            'is_hadir' => false,
                            'is_tugas' => false,
                            'is_guru_kosong' => false,
                            'is_dipantau' => false,
                            'indicator' => 'none',
                            'status_label' => null,
                            'foto_url' => null,
                            'foto_nama' => null,
                        ];
                    }
                }

                $rows[] = [
                    'jam_ke' => $jb->jam_ke,
                    'waktu_mulai' => substr((string) $jb->waktu_mulai, 0, 5),
                    'waktu_selesai' => substr((string) $jb->waktu_selesai, 0, 5),
                    'cells' => $cells,
                ];
            }

            $matrixPerLevel[$levelKey] = [
                'level' => $levelKey,
                'label' => $levelKey === 'Lainnya' ? 'Level Kelas Lainnya' : "Level Kelas {$levelKey}",
                'kelasList' => $kelasList,
                'rows' => $rows,
            ];
        }

        if ($request->wantsJson() || $request->boolean('live')) {
            return response()->json([
                'tanggal' => $tanggal,
                'hari' => $hariFilter,
                'totalJadwalSlot' => $totalJadwalSlot,
                'totalHadirSlot' => $totalHadirSlot,
                'totalTugasSlot' => $totalTugasSlot,
                'totalBelumHadirSlot' => $totalBelumHadirSlot,
                'matrixPerLevel' => $matrixPerLevel,
            ]);
        }

        return view('dashboard-mengajar.index', compact(
            'today',
            'tanggal',
            'hariFilter',
            'daftarHari',
            'levelFilter',
            'tahunFilter',
            'semesterFilter',
            'daftarTahun',
            'semuaKelas',
            'jamBelajars',
            'matrixPerLevel',
            'totalJadwalSlot',
            'totalHadirSlot',
            'totalTugasSlot',
            'totalBelumHadirSlot'
        ));
    }
}
