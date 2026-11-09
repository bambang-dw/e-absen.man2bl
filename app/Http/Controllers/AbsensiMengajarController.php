<?php

namespace App\Http\Controllers;

use App\Models\AbsensiGuru;
use App\Models\Jadwal;
use App\Models\JamBelajar;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class AbsensiMengajarController extends Controller
{
    /**
     * Map English/Carbon day of week to Indonesian school day name.
     */
    public static function getHariIndonesia(?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now();

        return match ($date->dayOfWeek) {
            Carbon::MONDAY => 'Senin',
            Carbon::TUESDAY => 'Selasa',
            Carbon::WEDNESDAY => 'Rabu',
            Carbon::THURSDAY => 'Kamis',
            Carbon::FRIDAY => 'Jumat',
            Carbon::SATURDAY => 'Sabtu',
            default => 'Minggu',
        };
    }

    /**
     * Display the teacher's Absensi Mengajar page (select class, multi jam_ke, auto mapel, and scan class QR code).
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $hariIni = self::getHariIndonesia(Carbon::now());

        // Guru biasa hanya melihat kelas yang diampu (berdasarkan jadwal); admin melihat semua kelas.
        $isAdmin = $user?->hasRole('admin') ?? false;

        $daftarKelas = Kelas::query()
            ->when(! $isAdmin, fn ($query) => $query->whereIn('id', $this->kelasIdsAmpuan($user)))
            ->orderBy('tahun_pelajaran', 'desc')
            ->orderBy('kelas', 'asc')
            ->get();

        $jamBelajars = JamBelajar::orderBy('waktu_mulai', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Guru biasa hanya boleh melihat mata pelajaran yang benar-benar diampu (sesuai jadwal).
        // Admin tetap melihat seluruh data agar dapat mencatat atas nama guru lain.
        $mapels = Mapel::with(['dataKelas', 'pengajar'])
            ->when(! $isAdmin, fn ($query) => $query->whereIn('id', $this->mapelIdsAmpuan($user)))
            ->orderBy('id', 'asc')
            ->get();

        $jadwalsHariIni = Jadwal::with(['dataKelas', 'pengajar'])
            ->where('hari', $hariIni)
            ->when(! $isAdmin && $user, fn ($query) => $this->scopeJadwalGuru($query, $user))
            ->orderBy('waktu_mulai', 'asc')
            ->get();

        $jamKePilihan = $isAdmin
            ? $jamBelajars->map(fn (JamBelajar $jamBelajar) => [
                'jam_ke' => $jamBelajar->jam_ke,
                'waktu_mulai' => $jamBelajar->waktu_mulai,
                'waktu_selesai' => $jamBelajar->waktu_selesai,
                'kelas_ids' => [],
            ])
            : $jadwalsHariIni
                ->filter(fn (Jadwal $jadwal) => trim((string) $jadwal->jam_ke) !== '')
                ->groupBy('jam_ke')
                ->map(fn ($jadwals, $jamKe) => [
                    'jam_ke' => $jamKe,
                    'waktu_mulai' => $jadwals->first()->waktu_mulai,
                    'waktu_selesai' => $jadwals->first()->waktu_selesai,
                    'kelas_ids' => $jadwals->pluck('kelas')->map(fn ($kelasId) => (string) $kelasId)->unique()->values()->all(),
                ])
                ->values();

        if ($isAdmin && $jamKePilihan->isEmpty()) {
            $jamKePilihan = collect(range(1, 10))
                ->map(fn (int $jamKe) => [
                    'jam_ke' => $jamKe,
                    'waktu_mulai' => '',
                    'waktu_selesai' => '',
                    'kelas_ids' => [],
                ]);
        }

        $riwayatMengajar = $isAdmin
            ? AbsensiGuru::with(['dataKelas', 'guru'])
                ->whereDate('tanggal', $today)
                ->orderBy('id', 'desc')
                ->get()
            : collect();

        // Jadwal mengajar guru selama seminggu (Senin - Jumat) untuk ditampilkan di bawah kamera
        $jadwalMingguan = $this->buildJadwalMingguan($user);

        return view('guru-dashboard.absensi-mengajar', compact(
            'user',
            'today',
            'hariIni',
            'daftarKelas',
            'jamBelajars',
            'jamKePilihan',
            'isAdmin',
            'mapels',
            'jadwalsHariIni',
            'riwayatMengajar',
            'jadwalMingguan'
        ));
    }

    /**
     * Build the teacher's weekly schedule (Monday - Friday).
     *
     * @return array<int, array{hari: string, tanggal: string, tanggal_label: string, is_today: bool, items: array<int, array<string, mixed>>}>
     */
    protected function buildJadwalMingguan($user): array
    {
        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        $jadwals = collect();
        if ($user) {
            $jadwals = Jadwal::with(['dataKelas', 'mapel', 'pengajar'])
                ->whereIn('hari', $hariList)
                ->where(function ($q) use ($user) {
                    $q->where('nama_pengajar', (string) $user->id)
                        ->orWhere('nama_pengajar', $user->username);
                })
                ->orderBy('waktu_mulai', 'asc')
                ->get()
                ->sortBy(fn (Jadwal $j) => array_search($j->hari, $hariList, true))
                ->values();
        }

        $hariIni = self::getHariIndonesia(Carbon::now());
        $senin = Carbon::now()->startOfWeek(Carbon::MONDAY);

        $result = [];
        foreach ($hariList as $index => $hari) {
            $tanggal = $senin->copy()->addDays($index);
            $items = $jadwals->where('hari', $hari)->map(fn (Jadwal $j) => [
                'jam_ke' => $j->jam_ke,
                'waktu' => substr((string) $j->waktu_mulai, 0, 5).' - '.substr((string) $j->waktu_selesai, 0, 5),
                'mapel' => $j->mapel ? ($j->mapel->kode_mapel ?: $j->mata_pelajaran) : (string) $j->mata_pelajaran,
                'mapel_lengkap' => (string) $j->mata_pelajaran,
                'kelas' => $j->dataKelas ? $j->dataKelas->kelas : '-',
            ])->values()->all();

            $result[] = [
                'hari' => $hari,
                'tanggal' => $tanggal->toDateString(),
                'tanggal_label' => $tanggal->format('d/m'),
                'is_today' => $hari === $hariIni,
                'items' => $items,
            ];
        }

        return $result;
    }

    /**
     * Query jadwal yang menjadi milik guru (kolom nama_pengajar menyimpan ID atau username guru).
     */
    protected function scopeJadwalGuru(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('nama_pengajar', (string) $user->id)
                ->orWhere('nama_pengajar', $user->username);
        });
    }

    /**
     * Ambil daftar ID mata pelajaran yang diampu guru berdasarkan jadwal mengajarnya.
     *
     * @return array<int, int>
     */
    protected function mapelIdsAmpuan(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return $this->scopeJadwalGuru(Jadwal::query(), $user)
            ->whereNotNull('mapel_id')
            ->distinct()
            ->pluck('mapel_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Ambil daftar ID kelas yang diampu guru berdasarkan jadwal mengajarnya.
     *
     * @return array<int, int>
     */
    protected function kelasIdsAmpuan(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return $this->scopeJadwalGuru(Jadwal::query(), $user)
            ->distinct()
            ->pluck('kelas')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Return JSON list of subjects (mapels) for a selected class ID.
     */
    public function mapelByKelas(Request $request, int $kelasId): JsonResponse
    {
        $user = Auth::user();
        $isAdmin = $user?->hasRole('admin') ?? false;

        $mapels = Mapel::with('pengajar')
            ->where('kelas', $kelasId)
            ->when(! $isAdmin, fn ($query) => $query->whereIn('id', $this->mapelIdsAmpuan($user)))
            ->orderBy('mata_pelajaran', 'asc')
            ->get();

        // Utamakan mata pelajaran yang diampu oleh guru yang sedang login jika ada
        $defaultMapel = $mapels->first()?->mata_pelajaran;

        return response()->json([
            'kelas_id' => $kelasId,
            'default_mata_pelajaran' => $defaultMapel,
            'mapels' => $mapels->map(fn (Mapel $m) => [
                'id' => $m->id,
                'kelas' => $m->kelas,
                'mata_pelajaran' => $m->mata_pelajaran,
                'nama_pengajar_id' => $m->nama_pengajar,
                'nama_pengajar' => $m->pengajar ? ($m->pengajar->name ?: $m->pengajar->username) : null,
                'is_mine' => $user ? ((int) $m->nama_pengajar === (int) $user->id) : false,
            ])->values(),
        ]);
    }

    /**
     * Store teacher teaching attendance (supports selecting multiple jam_ke and verifying scanned class QR code).
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $isAdmin = $user?->hasRole('admin') ?? false;

        $validated = $request->validate([
            'kelas' => ['required', 'exists:kelases,id'],
            'jam_ke' => ['required'],
            'mata_pelajaran' => ['nullable', 'string', 'max:150'],
            'qr_kelas' => [$isAdmin ? 'nullable' : 'required', 'string', 'max:150'],
            'materi_pembelajaran' => ['required', 'string'],
            'foto' => [
                $isAdmin ? 'nullable' : 'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:4096',
            ],
            'status' => ['nullable'],
            'keterangan' => ['nullable', 'string'],
            'tanggal' => ['nullable', 'date'],
            'hari' => ['nullable', 'string', 'max:20'],
        ], [
            'kelas.required' => 'Pilih Kelas terlebih dahulu.',
            'kelas.exists' => 'Data Kelas tidak ditemukan.',
            'jam_ke.required' => 'Pilih minimal satu Jam Ke pembelajaran.',
            'qr_kelas.required' => 'Scan QR Code kelas terlebih dahulu.',
            'materi_pembelajaran.required' => 'Materi pembelajaran wajib diisi.',
            'foto.required' => 'Foto kelas wajib diambil sebelum menyimpan absensi.',
            'foto.image' => 'File foto kelas harus berupa gambar.',
            'foto.mimes' => 'Foto kelas harus berformat JPG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran foto kelas maksimal 4 MB.',
        ]);

        $kelasModel = Kelas::findOrFail($validated['kelas']);

        // Otorisasi: guru hanya boleh mencatat kelas yang memang ada pada jadwal mengajarnya
        if ($user && ! $user->hasRole('admin')) {
            $mengampuKelas = $this->scopeJadwalGuru(Jadwal::query(), $user)
                ->where('kelas', $kelasModel->id)
                ->exists();

            if (! $mengampuKelas) {
                $errorMsg = "Anda tidak memiliki jadwal mengajar di kelas {$kelasModel->kelas}, sehingga absensi tidak dapat dicatat.";

                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg,
                    ], 403);
                }

                return back()->withInput()->with('error', $errorMsg);
            }
        }

        // Verifikasi QR Code Kelas jika dikirimkan dari hasil scan kamera
        if (! empty($validated['qr_kelas'])) {
            $scannedRaw = trim($validated['qr_kelas']);
            $normalizedScan = strtoupper(preg_replace('/\s+/', '', $scannedRaw));
            $expectedIdCode = 'KELAS:'.$kelasModel->id;
            $expectedNameCode = 'KELAS:'.strtoupper(preg_replace('/\s+/', '', $kelasModel->kelas));
            $plainNameCode = strtoupper(preg_replace('/\s+/', '', $kelasModel->kelas));

            $isValidQr = $scannedRaw === (string) $kelasModel->id
                || $normalizedScan === $expectedIdCode
                || $normalizedScan === $expectedNameCode
                || $normalizedScan === $plainNameCode;

            if (! $isValidQr) {
                $errorMsg = "QR Code yang dipindai ({$scannedRaw}) tidak sesuai dengan Kelas {$kelasModel->kelas}.";
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg,
                    ], 422);
                }

                return back()->withInput()->with('error', $errorMsg);
            }
        }

        // Normalisasi pilihan jam_ke (bisa berupa array ['1', '2', '3'] atau string '1,2,3')
        $jamKeInput = $validated['jam_ke'];
        if (is_array($jamKeInput)) {
            $jamKeList = array_values(array_filter(array_map('trim', $jamKeInput), fn ($v) => $v !== ''));
        } else {
            $jamKeList = array_values(array_filter(array_map('trim', explode(',', (string) $jamKeInput)), fn ($v) => $v !== ''));
        }

        if (empty($jamKeList)) {
            $errorMsg = 'Pilih minimal satu Jam Ke pembelajaran.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 422);
            }

            return back()->withInput()->with('error', $errorMsg);
        }

        if ($user && ! $isAdmin) {
            $jadwalHariIni = $this->scopeJadwalGuru(Jadwal::query(), $user)
                ->where('hari', self::getHariIndonesia(Carbon::today()))
                ->where('kelas', $kelasModel->id)
                ->orderBy('waktu_mulai')
                ->get(['jam_ke', 'waktu_mulai', 'waktu_selesai']);
            $jamDiminta = AbsensiGuru::expandJamKe($jamKeList);
            $jamTerjadwalHariIni = $jadwalHariIni
                ->flatMap(fn (Jadwal $jadwal) => AbsensiGuru::expandJamKe($jadwal->jam_ke))
                ->unique()
                ->values()
                ->all();

            if (empty($jamDiminta) || ! empty(array_diff($jamDiminta, $jamTerjadwalHariIni))) {
                $errorMsg = 'Jam yang dipilih tidak sesuai dengan jadwal mengajar Anda di kelas '
                    ."{$kelasModel->kelas} hari ini.";

                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 403);
                }

                return back()->withInput()->with('error', $errorMsg);
            }

            $jadwalDipilih = $jadwalHariIni->filter(
                fn (Jadwal $jadwal) => ! empty(array_intersect(
                    AbsensiGuru::expandJamKe($jadwal->jam_ke),
                    $jamDiminta
                ))
            );

            $waktuSekarang = Carbon::now()->format('H:i:s');
            $waktuMulaiPertama = $jadwalDipilih->first()?->waktu_mulai;
            $waktuSelesaiTerakhir = $jadwalDipilih->last()?->waktu_selesai;

            if (
                $waktuMulaiPertama
                && $waktuSelesaiTerakhir
                && ($waktuSekarang < $waktuMulaiPertama || $waktuSekarang > $waktuSelesaiTerakhir)
            ) {
                $errorMsg = 'Waktu mengajar tidak sesuai jadwal.';

                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 422);
                }

                return back()->withInput()->with('error', $errorMsg);
            }
        }

        // Mata pelajaran yang diampu guru pada kelas ini (sumber: jadwal mengajar)
        $mapelAmpuan = collect();
        if ($user && ! $isAdmin) {
            $mapelAmpuan = $this->scopeJadwalGuru(Jadwal::query(), $user)
                ->where('kelas', $kelasModel->id)
                ->whereNotNull('mapel_id')
                ->with('mapel')
                ->get()
                ->pluck('mapel')
                ->filter()
                ->unique('id')
                ->values();

            $mataPelajaranDiminta = trim((string) ($validated['mata_pelajaran'] ?? ''));
            $diizinkan = $mapelAmpuan->contains(fn (Mapel $m) => $m->mata_pelajaran === $mataPelajaranDiminta);

            if ($mataPelajaranDiminta !== '' && ! $diizinkan) {
                $errorMsg = '"'.$mataPelajaranDiminta.'" bukan mata pelajaran yang Anda ampu di kelas '
                    .$kelasModel->kelas.'. Pilih salah satu: '
                    .($mapelAmpuan->pluck('mata_pelajaran')->implode(', ') ?: '-').'.';

                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 403);
                }

                return back()->withInput()->with('error', $errorMsg);
            }
        }

        // Otomatis isi mata_pelajaran berdasarkan tabel jadwal/kelas/mapel jika belum terisi
        $mataPelajaran = trim((string) ($validated['mata_pelajaran'] ?? ''));
        if ($mataPelajaran === '') {
            if ($mapelAmpuan->isNotEmpty()) {
                $mataPelajaran = $mapelAmpuan->first()->mata_pelajaran;
            } else {
                $firstMapel = Mapel::where('kelas', $kelasModel->id)->first()
                    ?? Jadwal::where('kelas', $kelasModel->id)->first()
                    ?? Mapel::first();
                $mataPelajaran = $firstMapel ? $firstMapel->mata_pelajaran : '-';
            }
        }

        $tanggal = ! empty($validated['tanggal']) ? $validated['tanggal'] : Carbon::today()->toDateString();
        $hari = ! empty($validated['hari']) ? $validated['hari'] : self::getHariIndonesia(Carbon::parse($tanggal));
        $namaGuru = $user ? ($user->name ?: $user->username) : ($request->input('nama_guru') ?: 'Guru');
        $statusFinal = AbsensiGuru::normalizeStatus($validated['status'] ?? 'Hadir');
        $jamKeString = AbsensiGuru::formatJamKe($jamKeList);

        // Cegah duplikasi: kelas & jam yang sama tidak boleh dicatat dua kali oleh guru yang sama pada tanggal yang sama
        $bentrok = AbsensiGuru::findJamBentrok($tanggal, $user?->id, $kelasModel->id, $jamKeList);
        if ($bentrok) {
            $msg = "Absensi Kelas {$kelasModel->kelas} Jam ke-{$bentrok->jam_ke} sudah tercatat hari ini pukul "
                .($bentrok->created_at?->format('H:i') ?? '-').' WIB'
                .($bentrok->materi_pembelajaran ? " ({$bentrok->materi_pembelajaran})" : '')
                .'. Data tidak dicatat ulang agar tidak terjadi duplikasi.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'duplicate' => true,
                    'message' => $msg,
                    'data' => [
                        'id' => $bentrok->id,
                        'jam_ke' => $bentrok->jam_ke,
                    ],
                ], 409);
            }

            return back()->withInput()->with('warning', $msg);
        }

        $foto = $validated['foto'] ?? null;
        $fotoPath = null;
        if ($foto) {
            $fotoDirectory = Carbon::parse($tanggal)->format('Y-m-d');

            $jamKeUntukNamaFoto = str_replace(',', '-', $jamKeString);
            $namaDasarFoto = Str::slug($kelasModel->kelas).'-jam-'.$jamKeUntukNamaFoto;
            $ekstensiFoto = $foto->guessExtension() ?: 'jpg';
            $namaFoto = $namaDasarFoto.'.'.$ekstensiFoto;
            $pathFoto = $fotoDirectory.'/'.$namaFoto;
            $nomorFoto = 2;

            while (Storage::disk('foto_guru')->exists($pathFoto)) {
                $namaFoto = $namaDasarFoto.'-'.$nomorFoto.'.'.$ekstensiFoto;
                $pathFoto = $fotoDirectory.'/'.$namaFoto;
                $nomorFoto++;
            }

            $fotoPath = $foto->storeAs($fotoDirectory, $namaFoto, 'foto_guru');
            if (! $fotoPath) {
                throw new RuntimeException('Foto kelas gagal disimpan.');
            }
        }

        try {
            $record = AbsensiGuru::create([
                'kelas' => $kelasModel->id,
                'hari' => $hari,
                'tanggal' => $tanggal,
                'nama_guru' => $namaGuru,
                'guru_id' => $user?->id,
                'jam_ke' => $jamKeString,
                'mata_pelajaran' => $mataPelajaran,
                'materi_pembelajaran' => $validated['materi_pembelajaran'] ?? null,
                'foto' => $fotoPath,
                'status' => $statusFinal,
                'keterangan' => $validated['keterangan'] ?? "Status: {$statusFinal} di Kelas {$kelasModel->kelas}",
            ]);
        } catch (UniqueConstraintViolationException) {
            if ($fotoPath) {
                Storage::disk('foto_guru')->delete($fotoPath);
            }

            // Pengaman terakhir bila dua request masuk bersamaan (unique index pada tanggal + guru_id + kelas + jam_ke)
            $msg = "Absensi Kelas {$kelasModel->kelas} Jam ke-{$jamKeString} sudah tercatat hari ini. Data tidak dicatat ulang.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'duplicate' => true, 'message' => $msg], 409);
            }

            return back()->withInput()->with('warning', $msg);
        }

        $msg = "Absensi mengajar Kelas {$kelasModel->kelas} (Jam ke-{$jamKeString} - {$mataPelajaran}) berhasil dicatat ({$statusFinal})!";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => [
                    'id' => $record->id,
                    'kelas_id' => $kelasModel->id,
                    'nama_kelas' => $kelasModel->kelas,
                    'hari' => $record->hari,
                    'tanggal' => Carbon::parse($record->tanggal)->format('d/m/Y'),
                    'nama_guru' => $record->nama_guru_tampil,
                    'jam_ke' => $record->jam_ke,
                    'mata_pelajaran' => $record->mata_pelajaran,
                    'materi_pembelajaran' => $record->materi_pembelajaran,
                    'foto' => $record->foto,
                    'foto_url' => $record->foto_url,
                    'status' => $record->status,
                ],
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }
}
