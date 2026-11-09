<?php

namespace App\Console\Commands;

use App\Models\Jadwal;
use App\Models\JamBelajar;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

#[Signature('app:import-jadwal-excel
    {file : Path file Excel jadwal}
    {--tahun=2026/2027 : Tahun pelajaran}
    {--semester=Ganjil : Semester}
    {--dry-run : Tampilkan hasil pencocokan tanpa menyimpan data}')]
#[Description('Impor jadwal mengajar dari file Excel (kolom kelas + kode mapel diabaikan)')]
class ImportJadwalExcel extends Command
{
    /**
     * Map kode mapel di Excel ke mata pelajaran pada tabel mapels.
     *
     * @var array<string, string>
     */
    private const KODE_PREFIX_MAPEL = [
        'AQHD' => 'Al Quran Hadits',
        'AKAH' => 'Akidah Ahlak',
        'FIK' => 'Fikih',
        'SKI' => 'Sejarah Kebudayaan Islam',
        'ARB' => 'Bahasa Arab',
        'PPKN' => 'Pendidikan Pancasila',
        'INDO' => 'Bahasa Indonesia',
        'MTKW' => 'Matematika Wajib',
        'MTKL' => 'Matematika Tingkat Lanjut',
        'BIO' => 'Biologi',
        'FIS' => 'Fisika',
        'KIM' => 'Kimia',
        'EKO' => 'Ekonomi',
        'SOS' => 'Sosiologi',
        'GEO' => 'Geografi',
        'SEJR' => 'Sejarah Indonesia',
        'SEJTL' => 'Sejarah Tingkat Lanjut',
        'INGG' => 'Bahasa Inggris',
        'INFO' => 'Informatika',
        'SBD' => 'Seni Budaya',
        'PJOK' => 'Pendidikan Jasmani, Olahraga dan Kesehatan',
        'OTO' => 'Keterampilan Otomotif',
        'AL' => 'Keterampilan Teknik Pendingin',
        'TTBS' => 'Keterampilan Tata Busana',
        'SING' => 'Sastra Inggris',
        'UPACARA' => 'Upacara Bendera',
        'SENAM' => 'Senam Pagi',
        'AGM' => 'Penguatan Agama',
        'KOSSMOS' => 'Pembinaan Prestasi Akademik',
    ];

    /**
     * Alias nama guru pada Excel ke nama guru pada tabel users.
     * Kunci adalah hasil normalizeNamaAkademik() dari nama di Excel.
     *
     * @var array<string, string>
     */
    private const ALIAS_GURU = [
        'abdul basith' => 'Abdul Basit Hamhij, S.Pd.I',
    ];

    /**
     * Alias nama mata pelajaran pada Excel ke mata pelajaran standar pada tabel mapels.
     *
     * @var array<string, string>
     */
    private const ALIAS_MAPEL = [
        'aqidah akhlak' => 'Akidah Ahlak',
        'al-quran hadits' => 'Al Quran Hadits',
        'alquran hadits' => 'Al Quran Hadits',
        'fiqih' => 'Fikih',
        'matematika tk lanjut' => 'Matematika Tingkat Lanjut',
        'pjk' => 'Pendidikan Jasmani, Olahraga dan Kesehatan',
        'ket tata busana' => 'Keterampilan Tata Busana',
        'ket teknik pendingin' => 'Keterampilan Teknik Pendingin',
        'sejarah' => 'Sejarah Indonesia',
        'sejarah/sejarah indonesia' => 'Sejarah Indonesia',
        'sejarah tk lanjut' => 'Sejarah Tingkat Lanjut',
        'pjok' => 'Pendidikan Jasmani, Olahraga dan Kesehatan',
        'al quran hadits' => "Al Qur'an Hadits",
        'al-quran hadits' => "Al Qur'an Hadits",
        'aqidah akhlaq' => 'Akidah Ahlak',
        'sejarah sejarah indonesia' => 'Sejarah Indonesia',
        'ket perb sepeda motor' => 'Keterampilan Otomotif',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $file = (string) $this->argument('file');
        $tahunPelajaran = (string) $this->option('tahun');
        $semester = (string) $this->option('semester');
        $isDryRun = (bool) $this->option('dry-run');

        if (! is_file($file)) {
            $this->components->error("File tidak ditemukan: {$file}");

            return self::FAILURE;
        }

        $sheet = IOFactory::load($file)->getActiveSheet();
        $kelasIds = $this->mapKelasByHeader($sheet);
        $guruIds = $this->mapGuruByName();
        $mapelIds = $this->mapMapelByName();
        $jamBelajars = JamBelajar::orderBy('id')->get()->keyBy('jam_ke');

        $records = $this->parseSheet($sheet, $kelasIds, $guruIds, $mapelIds);
        $records = $this->applyJamBelajar($records, $jamBelajars);

        if ($errors = $this->validateRecords($records)) {
            foreach ($errors as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        if ($isDryRun) {
            $this->components->info(sprintf(
                'Dry run: %d slot jadwal valid untuk %s %s (belum disimpan).',
                count($records['jadwal']),
                $tahunPelajaran,
                $semester
            ));

            if ($records['mapel_baru']->isNotEmpty()) {
                $this->components->warn('Mapel baru yang akan ditambahkan: '.$records['mapel_baru']->implode(', '));
            }

            return self::SUCCESS;
        }

        DB::transaction(function () use ($tahunPelajaran, $semester, $records) {
            Jadwal::where('tahun_pelajaran', $tahunPelajaran)
                ->where('semester', $semester)
                ->delete();

            foreach ($records['mapel_baru'] as $namaMapel) {
                Mapel::create([
                    'kode_mapel' => $this->generateKodeMapel($namaMapel),
                    'mata_pelajaran' => $namaMapel,
                ]);
            }

            foreach (array_chunk($records['jadwal'], 200) as $chunk) {
                Jadwal::insert($chunk);
            }
        });

        $this->components->info(sprintf(
            'Berhasil mengimpor %d slot jadwal untuk %s semester %s (%d kelas).',
            count($records['jadwal']),
            $tahunPelajaran,
            $semester,
            $records['kelas_terpakai']
        ));

        if ($records['mapel_baru']->isNotEmpty()) {
            $this->components->warn('Mapel baru ditambahkan: '.$records['mapel_baru']->implode(', '));
        }

        return self::SUCCESS;
    }

    /**
     * Petakan kolom header kelas di baris 5 ke ID kelas pada database.
     *
     * @return array<int, int> [kolom => id kelas]
     */
    private function mapKelasByHeader(Worksheet $sheet): array
    {
        $kelasByName = Kelas::all()->keyBy(fn (Kelas $kelas) => $this->normalizeKelas($kelas->kelas));
        $kelasIds = [];

        $kolomTerakhir = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        // Header kelas pada baris 5 berada di kolom D, F, H, ... (setiap 2 kolom)
        for ($kolom = 4; $kolom <= $kolomTerakhir; $kolom += 2) {
            $huruf = Coordinate::stringFromColumnIndex($kolom);
            $key = $this->normalizeKelas((string) $sheet->getCell($huruf.'5')->getValue());

            if (isset($kelasByName[$key])) {
                $kelasIds[$kolom] = $kelasByName[$key]->id;
            }
        }

        if ($kelasIds === []) {
            $this->components->warn('Tidak ada nama kelas pada baris 5 yang cocok dengan data kelas.');
        }

        return $kelasIds;
    }

    /**
     * Petakan nama guru (dinormalisasi) ke ID user.
     *
     * @return array<string, int>
     */
    private function mapGuruByName(): array
    {
        return User::orderBy('id')
            ->get(['id', 'name'])
            ->reverse()
            ->mapWithKeys(fn (User $user) => [$this->normalizeGuru($user->name) => $user->id])
            ->all();
    }

    /**
     * Petakan nama mata pelajaran (dinormalisasi) ke ID mapel.
     *
     * @return array<string, int>
     */
    private function mapMapelByName(): array
    {
        return Mapel::orderBy('id')
            ->get(['id', 'mata_pelajaran'])
            ->reverse()
            ->mapWithKeys(fn (Mapel $mapel) => [$this->normalizeMapel($mapel->mata_pelajaran) => $mapel->id])
            ->all();
    }

    /**
     * Baca seluruh sel jadwal dari sheet.
     *
     * @param  array<int, int>  $kelasIds
     * @param  array<string, int>  $guruIds
     * @param  array<string, int>  $mapelIds
     * @return array{jadwal: array<int, array<string, mixed>>, mapel_baru: Collection, kelas_terpakai: int, errors: array<int, string>}
     */
    private function parseSheet(Worksheet $sheet, array $kelasIds, array $guruIds, array $mapelIds): array
    {
        $hariAktif = null;
        $jadwals = [];
        $mapelBaru = collect();
        $errors = [];
        $kelasTerpakai = [];

        for ($nomorBaris = 1; $nomorBaris <= $sheet->getHighestRow(); $nomorBaris++) {
            $namaHari = strtoupper(trim((string) $sheet->getCell('A'.$nomorBaris)->getValue()));

            if (in_array($namaHari, ['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT'], true)) {
                $hariAktif = Str::ucfirst(Str::lower($namaHari));
            }

            $jamKe = $sheet->getCell('B'.$nomorBaris)->getValue();
            if (! $hariAktif || ! is_numeric($jamKe)) {
                continue;
            }

            $jamKe = (int) $jamKe;
            $waktu = (string) $sheet->getCell('C'.$nomorBaris)->getValue();

            foreach ($kelasIds as $kolom => $kelasId) {
                // Kolom kelas memuat kode mapel, kolom berikutnya nama mapel,
                // nama guru berada pada kolom yang sama dengan nama mapel di baris berikutnya.
                $kolomKode = Coordinate::stringFromColumnIndex($kolom);
                $kolomMapel = Coordinate::stringFromColumnIndex($kolom + 1);
                $kodeMapel = $this->bersihkanKodeMapel((string) $sheet->getCell($kolomKode.$nomorBaris)->getValue());
                $namaMapelSel = trim((string) $sheet->getCell($kolomMapel.$nomorBaris)->getValue());

                if ($namaMapelSel === '') {
                    continue;
                }

                $mataPelajaran = $this->bersihkanKodeMapel($namaMapelSel);
                $namaGuru = trim((string) $sheet->getCell($kolomMapel.($nomorBaris + 1))->getValue());

                if ($mataPelajaran === '') {
                    continue;
                }

                $namaMapelStandar = $this->resolveNamaMapel($mataPelajaran);
                $mapelId = $mapelIds[$this->normalizeMapel($namaMapelStandar)] ?? null;

                if ($mapelId === null) {
                    $mapelBaru->push($namaMapelStandar);
                }

                $guruId = null;
                if ($namaGuru !== '') {
                    $guruId = $guruIds[$this->normalizeGuru($namaGuru)]
                        ?? $this->cariGuruTerdekat($namaGuru, $guruIds);

                    if ($guruId === null) {
                        $errors[] = "Guru '{$namaGuru}' (baris {$nomorBaris}) tidak ditemukan di database.";
                    }
                }

                $kelasTerpakai[$kelasId] = true;

                $jadwals[] = [
                    'tahun_pelajaran' => (string) $this->option('tahun'),
                    'semester' => (string) $this->option('semester'),
                    'kelas' => $kelasId,
                    'hari' => $hariAktif,
                    'jam_belajar_id' => null,
                    'jam_ke' => (string) $jamKe,
                    'waktu_mulai' => $this->parseWaktu($waktu, 'mulai'),
                    'waktu_selesai' => $this->parseWaktu($waktu, 'selesai'),
                    'mapel_id' => $mapelId,
                    'mata_pelajaran' => $namaMapelStandar,
                    'nama_pengajar' => $guruId,
                ];
            }
        }

        return [
            'jadwal' => $jadwals,
            'mapel_baru' => $mapelBaru->unique()->values(),
            'kelas_terpakai' => count($kelasTerpakai),
            'errors' => $errors,
        ];
    }

    /**
     * Hubungkan jam_belajar_id berdasarkan jam ke serta samakan waktu bila tersedia.
     *
     * @param  array{jadwal: array<int, array<string, mixed>>}  $records
     * @param  Collection<string, JamBelajar>  $jamBelajars
     * @return array{jadwal: array<int, array<string, mixed>>, mapel_baru: Collection, kelas_terpakai: int, errors: array<int, string>}
     */
    private function applyJamBelajar(array $records, $jamBelajars): array
    {
        foreach ($records['jadwal'] as $index => $jadwal) {
            $jamBelajar = $jamBelajars->get($jadwal['jam_ke']);

            if (! $jamBelajar) {
                continue;
            }

            $records['jadwal'][$index]['jam_belajar_id'] = $jamBelajar->id;
            $records['jadwal'][$index]['waktu_mulai'] = $jamBelajar->waktu_mulai;
            $records['jadwal'][$index]['waktu_selesai'] = $jamBelajar->waktu_selesai;
        }

        return $records;
    }

    /**
     * Validasi kelengkapan data sebelum disimpan.
     *
     * @param  array{jadwal: array<int, array<string, mixed>>, errors: array<int, string>}  $records
     * @return array<int, string>
     */
    private function validateRecords(array $records): array
    {
        $errors = $records['errors'];

        if ($records['jadwal'] === []) {
            $errors[] = 'Tidak ada data jadwal yang terbaca dari file Excel.';
        }

        foreach ($records['jadwal'] as $jadwal) {
            if (empty($jadwal['waktu_mulai']) || empty($jadwal['waktu_selesai'])) {
                $errors[] = "Waktu jam ke-{$jadwal['jam_ke']} ({$jadwal['hari']}) tidak valid: '{$jadwal['waktu_mulai']} - {$jadwal['waktu_selesai']}'.";
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * Hilangkan kode angka di depan nama mapel (misal "57 - Kimia" menjadi "Kimia").
     */
    private function bersihkanKodeMapel(string $nilai): string
    {
        $teks = trim($nilai);
        $teks = preg_replace('/^\s*\d+\s*[-.:)]\s*/', '', $teks) ?? $teks;
        $teks = preg_replace('/^\s*\d+\s+(?=\D)/', '', $teks) ?? $teks;

        return trim($teks);
    }

    /**
     * Resolusi nama mapel ke nama standar pada tabel mapels.
     */
    private function resolveNamaMapel(string $namaMapel): string
    {
        $key = $this->normalizeMapel($namaMapel);

        if (isset(self::ALIAS_MAPEL[$key])) {
            return self::ALIAS_MAPEL[$key];
        }

        return Str::of($namaMapel)->squish()->title()->toString();
    }

    /**
     * Buat kode mapel baru berdasarkan nama mapel.
     */
    private function generateKodeMapel(string $namaMapel): string
    {
        $kata = preg_split('/\s+/', strtoupper($this->normalizeMapel($namaMapel))) ?: [];
        $kode = Str::substr(implode('', array_map(fn ($k) => Str::substr($k, 0, 3), $kata)), 0, 12);

        return $kode !== '' ? $kode : 'MAPEL';
    }

    /**
     * Ambil nilai waktu mulai atau selesai dari teks "07.15 - 08.00".
     */
    private function parseWaktu(string $teks, string $posisi): ?string
    {
        if (! preg_match('/(\d{1,2})[.:](\d{2})\s*-\s*(\d{1,2})[.:](\d{2})/', $teks, $matches)) {
            return null;
        }

        $nilai = $posisi === 'mulai'
            ? [$matches[1], $matches[2]]
            : [$matches[3], $matches[4]];

        return sprintf('%02d:%02d:00', (int) $nilai[0], (int) $nilai[1]);
    }

    /**
     * Normalisasi nama kelas (misal "X A" atau "XII A1").
     */
    private function normalizeKelas(string $namaKelas): string
    {
        return (string) Str::of($namaKelas)->upper()->trim()->replaceMatches('/\s+/', ' ')->toString();
    }

    /**
     * Normalisasi nama guru dengan menghapus gelar dan tanda baca.
     */
    private function normalizeGuru(string $namaGuru): string
    {
        $nama = $this->normalizeNamaAkademik($namaGuru);

        return isset(self::ALIAS_GURU[$nama])
            ? $this->normalizeNamaAkademik(self::ALIAS_GURU[$nama])
            : $nama;
    }

    /**
     * Normalisasi nama tanpa menerapkan alias guru.
     */
    private function normalizeNamaAkademik(string $namaGuru): string
    {
        $nama = Str::of($namaGuru)
            ->lower()
            ->replace(["'", '’', ','], ' ')
            ->replaceMatches('/[^a-z\s]/', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        // Hapus token gelar akademik yang berdiri sendiri (contoh: "s", "pd", "i", "si", "m", "pfis").
        $kata = array_values(array_filter(
            explode(' ', (string) $nama),
            fn (string $k) => $k !== '' && ! in_array($k, ['h', 'hj', 'dr', 'dra', 'drs', 's', 'm'], true)
        ));

        $namaTanpaGelar = array_filter($kata, fn (string $k) => mb_strlen($k) > 2);

        return implode(' ', array_values($namaTanpaGelar !== [] ? $namaTanpaGelar : $kata));
    }

    /**
     * Cari nama guru terdekat bila pencocokan persis gagal.
     *
     * @param  array<string, int>  $guruIds
     */
    private function cariGuruTerdekat(string $namaGuru, array $guruIds): ?int
    {
        $terbaik = null;
        $skorTerbaik = 0.0;

        foreach ($guruIds as $namaTersimpan => $id) {
            $skor = 0.0;
            similar_text($this->normalizeGuru($namaGuru), $namaTersimpan, $skor);

            if ($skor > $skorTerbaik) {
                $skorTerbaik = $skor;
                $terbaik = $id;
            }
        }

        return $skorTerbaik >= 88.0 ? $terbaik : null;
    }

    /**
     * Normalisasi nama mata pelajaran.
     */
    private function normalizeMapel(string $namaMapel): string
    {
        $nama = Str::of($namaMapel)->lower()->replaceMatches('/[^a-z0-9\s]/', ' ');
        $nama = (string) $nama->replaceMatches('/\s+/', ' ')->trim();

        return str_starts_with($nama, 'al quran') ? 'al quran'.substr($nama, 8) : $nama;
    }
}
