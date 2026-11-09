<?php

namespace App\Http\Controllers;

use App\Models\AbsensiGuru;
use App\Models\Jadwal;
use App\Models\JamBelajar;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalController extends Controller
{
    /**
     * Display the Kelola Jadwal Pelajaran page.
     */
    public function index(Request $request): View
    {
        $daftarKelas = Kelas::orderBy('tahun_pelajaran', 'desc')
            ->orderBy('kelas', 'asc')
            ->get();

        $daftarTahun = Kelas::select('tahun_pelajaran')
            ->distinct()
            ->orderBy('tahun_pelajaran', 'desc')
            ->pluck('tahun_pelajaran');

        if ($daftarTahun->isEmpty()) {
            $daftarTahun = collect([date('Y').'/'.(date('Y') + 1)]);
        }

        $daftarSemester = ['Ganjil', 'Genap'];
        $daftarHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        $tahunFilter = $request->input('tahun_pelajaran', $daftarTahun->first());
        $semesterFilter = $request->input('semester', 'Ganjil');
        $kelasFilter = $request->input('kelas', $daftarKelas->first()?->id);
        $hariFilter = $request->input('hari', 'Senin');

        $query = Jadwal::with(['dataKelas', 'jamBelajar', 'mapel', 'pengajar']);

        if ($tahunFilter) {
            $query->where('tahun_pelajaran', $tahunFilter);
        }

        if ($semesterFilter) {
            $query->where('semester', $semesterFilter);
        }

        if ($kelasFilter) {
            $query->where('kelas', $kelasFilter);
        }

        if ($hariFilter) {
            $query->where('hari', $hariFilter);
        }

        $jadwals = $query->orderBy('waktu_mulai', 'asc')
            ->orderBy('jam_ke', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $jamBelajars = JamBelajar::orderBy('waktu_mulai', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $mapelsQuery = Mapel::with(['dataKelas', 'pengajar'])
            ->orderBy('id', 'asc');

        $mapels = $mapelsQuery->get();

        $daftarPengajar = User::where(function ($q) {
            $q->whereNull('role')
                ->orWhere('role', '!=', 'siswa');
        })
            ->orderBy('name', 'asc')
            ->orderBy('username', 'asc')
            ->get();

        return view('jadwal.index', compact(
            'jadwals',
            'daftarKelas',
            'daftarTahun',
            'daftarSemester',
            'daftarHari',
            'tahunFilter',
            'semesterFilter',
            'kelasFilter',
            'hariFilter',
            'jamBelajars',
            'mapels',
            'daftarPengajar'
        ));
    }

    /**
     * Store a newly created jadwal (single or bulk table rows) in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        // Jika dikirim dari tabel jadwal pelajaran sekaligus (bulk per jam_belajar)
        if ($request->has('items') && is_array($request->input('items'))) {
            $header = $request->validate([
                'tahun_pelajaran' => ['required', 'string', 'max:20'],
                'semester' => ['required', 'string', 'max:20'],
                'kelas' => ['required', 'exists:kelases,id'],
                'hari' => ['required', 'string', 'max:20'],
            ]);

            $savedCount = 0;

            foreach ($request->input('items') as $item) {
                $mapelId = $item['mapel_id'] ?? null;
                $pengajarId = ! empty($item['nama_pengajar']) ? $item['nama_pengajar'] : null;
                $jamBelajarId = $item['jam_belajar_id'] ?? null;
                $jamKe = $item['jam_ke'] ?? null;
                $waktuMulai = $item['waktu_mulai'] ?? null;
                $waktuSelesai = $item['waktu_selesai'] ?? null;

                if (! empty($jamBelajarId)) {
                    $jam = JamBelajar::find($jamBelajarId);
                    if ($jam) {
                        $jamKe = $jam->jam_ke;
                        $waktuMulai = $jam->waktu_mulai;
                        $waktuSelesai = $jam->waktu_selesai;
                    }
                }

                if (empty($jamKe)) {
                    continue;
                }

                if (empty($mapelId) && empty($item['mata_pelajaran'])) {
                    Jadwal::where('tahun_pelajaran', $header['tahun_pelajaran'])
                        ->where('semester', $header['semester'])
                        ->where('kelas', $header['kelas'])
                        ->where('hari', $header['hari'])
                        ->where('jam_ke', (string) $jamKe)
                        ->delete();

                    continue;
                }

                $namaMapel = $item['mata_pelajaran'] ?? null;
                if (! empty($mapelId)) {
                    $mapel = Mapel::find($mapelId);
                    if ($mapel) {
                        $namaMapel = $mapel->mata_pelajaran;
                        if (empty($pengajarId) && $mapel->nama_pengajar) {
                            $pengajarId = $mapel->nama_pengajar;
                        }
                    }
                }

                if (empty($namaMapel)) {
                    continue;
                }

                Jadwal::updateOrCreate(
                    [
                        'tahun_pelajaran' => $header['tahun_pelajaran'],
                        'semester' => $header['semester'],
                        'kelas' => $header['kelas'],
                        'hari' => $header['hari'],
                        'jam_ke' => (string) $jamKe,
                    ],
                    [
                        'jam_belajar_id' => $jamBelajarId,
                        'waktu_mulai' => $waktuMulai,
                        'waktu_selesai' => $waktuSelesai,
                        'mapel_id' => $mapelId,
                        'mata_pelajaran' => $namaMapel,
                        'nama_pengajar' => $pengajarId,
                    ]
                );

                $savedCount++;
            }

            return redirect()->back()
                ->with('success', "Berhasil menyimpan {$savedCount} slot jadwal pelajaran untuk Hari {$header['hari']}.");
        }

        $validated = $request->validate([
            'tahun_pelajaran' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'string', 'max:20'],
            'kelas' => ['required', 'exists:kelases,id'],
            'hari' => ['required', 'string', 'max:20'],
            'jam_belajar_id' => ['nullable', 'exists:jam_belajars,id'],
            'jam_ke' => ['nullable', 'string', 'max:50'],
            'waktu_mulai' => ['nullable'],
            'waktu_selesai' => ['nullable'],
            'mapel_id' => ['nullable', 'exists:mapels,id'],
            'mata_pelajaran' => ['nullable', 'string', 'max:150'],
            'nama_pengajar' => ['nullable', 'exists:users,id'],
        ]);

        // Ambil detail jam_ke, waktu_mulai, waktu_selesai dari jam_belajars jika jam_belajar_id dipilih
        if (! empty($validated['jam_belajar_id'])) {
            $jam = JamBelajar::find($validated['jam_belajar_id']);
            if ($jam) {
                $validated['jam_ke'] = $jam->jam_ke;
                $validated['waktu_mulai'] = $jam->waktu_mulai;
                $validated['waktu_selesai'] = $jam->waktu_selesai;
            }
        }

        // Ambil detail mata_pelajaran dan nama_pengajar dari mapels jika mapel_id dipilih
        if (! empty($validated['mapel_id'])) {
            $mapel = Mapel::find($validated['mapel_id']);
            if ($mapel) {
                $validated['mata_pelajaran'] = $mapel->mata_pelajaran;
                if (empty($validated['nama_pengajar']) && $mapel->nama_pengajar) {
                    $validated['nama_pengajar'] = $mapel->nama_pengajar;
                }
            }
        }

        if (empty($validated['jam_ke']) || empty($validated['waktu_mulai']) || empty($validated['waktu_selesai'])) {
            return back()->withInput()->with('error', 'Pilih Jam Belajar atau lengkapi Jam Ke, Waktu Mulai, dan Waktu Selesai.');
        }

        if (empty($validated['mata_pelajaran'])) {
            return back()->withInput()->with('error', 'Pilih Mata Pelajaran terlebih dahulu.');
        }

        Jadwal::updateOrCreate(
            [
                'tahun_pelajaran' => $validated['tahun_pelajaran'],
                'semester' => $validated['semester'],
                'kelas' => $validated['kelas'],
                'hari' => $validated['hari'],
                'jam_ke' => (string) $validated['jam_ke'],
            ],
            $validated
        );

        return redirect()->back()
            ->with('success', "Jadwal pelajaran {$validated['mata_pelajaran']} (Hari {$validated['hari']}, Jam ke-{$validated['jam_ke']}) berhasil disimpan.");
    }

    /**
     * Update the specified jadwal in storage.
     */
    public function update(Request $request, Jadwal $jadwal): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_pelajaran' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'string', 'max:20'],
            'kelas' => ['required', 'exists:kelases,id'],
            'hari' => ['required', 'string', 'max:20'],
            'jam_belajar_id' => ['nullable', 'exists:jam_belajars,id'],
            'jam_ke' => ['nullable', 'string', 'max:50'],
            'waktu_mulai' => ['nullable'],
            'waktu_selesai' => ['nullable'],
            'mapel_id' => ['nullable', 'exists:mapels,id'],
            'mata_pelajaran' => ['nullable', 'string', 'max:150'],
            'nama_pengajar' => ['nullable', 'exists:users,id'],
        ]);

        if (! empty($validated['jam_belajar_id'])) {
            $jam = JamBelajar::find($validated['jam_belajar_id']);
            if ($jam) {
                $validated['jam_ke'] = $jam->jam_ke;
                $validated['waktu_mulai'] = $jam->waktu_mulai;
                $validated['waktu_selesai'] = $jam->waktu_selesai;
            }
        }

        if (! empty($validated['mapel_id'])) {
            $mapel = Mapel::find($validated['mapel_id']);
            if ($mapel) {
                $validated['mata_pelajaran'] = $mapel->mata_pelajaran;
                if (empty($validated['nama_pengajar']) && $mapel->nama_pengajar) {
                    $validated['nama_pengajar'] = $mapel->nama_pengajar;
                }
            }
        }

        $jadwal->update($validated);

        return redirect()->back()
            ->with('success', "Jadwal pelajaran {$jadwal->mata_pelajaran} berhasil diperbarui.");
    }

    /**
     * Remove the specified jadwal from storage.
     */
    public function destroy(Jadwal $jadwal): RedirectResponse
    {
        $info = "{$jadwal->mata_pelajaran} (Jam ke-{$jadwal->jam_ke})";
        $jadwal->delete();

        return redirect()->back()
            ->with('success', "Jadwal pelajaran {$info} berhasil dihapus.");
    }

    /**
     * Store a newly created absensi_guru record (supports multi-select status such as Izin + Tugas or Sakit + Tugas).
     */
    public function storeAbsensiGuru(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kelas' => ['nullable', 'exists:kelases,id'],
            'hari' => ['required', 'string', 'max:20'],
            'tanggal' => ['required', 'date'],
            'nama_guru' => ['required', 'string', 'max:150'],
            'jam_ke' => ['required'],
            'mata_pelajaran' => ['required', 'string', 'max:150'],
            'materi_pembelajaran' => ['nullable', 'string'],
            'status' => ['required'],
            'keterangan' => ['nullable', 'string'],
        ], [
            'status.required' => 'Pilih minimal satu Status kehadiran guru.',
            'jam_ke.required' => 'Jam Ke wajib diisi.',
        ]);

        // Normalisasi jam_ke (bisa array ['1', '2'] atau string '1,2') menjadi format baku '1,2'
        $validated['jam_ke'] = AbsensiGuru::formatJamKe($validated['jam_ke']);

        // Normalisasi status (bisa multi-select misal ['Izin', 'Tugas'] -> 'Izin, Tugas')
        $validated['status'] = AbsensiGuru::normalizeStatus($validated['status']);

        $rawGuru = (string) $validated['nama_guru'];
        $guruId = is_numeric($rawGuru)
            ? (int) $rawGuru
            : User::where('name', $rawGuru)->orWhere('username', $rawGuru)->value('id');
        $validated['guru_id'] = $guruId ?: null;

        // Cegah duplikasi: guru tidak boleh mencatat kelas & jam yang sama dua kali pada tanggal yang sama
        $bentrok = AbsensiGuru::findJamBentrok((string) $validated['tanggal'], $validated['guru_id'], $validated['kelas'] ?? null, $validated['jam_ke']);
        if ($bentrok) {
            return redirect()->back()->withInput()->with(
                'warning',
                "Absensi {$validated['nama_guru']} untuk Jam ke-{$bentrok->jam_ke} tanggal ".Carbon::parse($validated['tanggal'])->translatedFormat('d F Y')
                .' sudah tercatat. Data tidak disimpan ulang agar tidak terjadi duplikasi.'
            );
        }

        try {
            AbsensiGuru::create($validated);
        } catch (UniqueConstraintViolationException) {
            return redirect()->back()->withInput()->with(
                'warning',
                'Absensi guru untuk jam tersebut sudah tercatat. Data tidak disimpan ulang.'
            );
        }

        return redirect()->back()
            ->with('success', "Data absensi guru ({$validated['status']}) berhasil disimpan.");
    }

    /**
     * Remove the specified absensi_guru record.
     */
    public function destroyAbsensiGuru(AbsensiGuru $absensiGuru): RedirectResponse
    {
        $absensiGuru->delete();

        return redirect()->back()
            ->with('success', 'Data absensi guru berhasil dihapus.');
    }
}
