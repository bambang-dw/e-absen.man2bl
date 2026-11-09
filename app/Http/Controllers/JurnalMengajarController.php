<?php

namespace App\Http\Controllers;

use App\Models\AbsensiGuru;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JurnalMengajarController extends Controller
{
    /**
     * Menampilkan tabel Absensi & Jurnal Mengajar Guru pada halaman tersendiri.
     *
     * Administrator melihat seluruh catatan, sedangkan guru dan wali kelas hanya
     * melihat catatan miliknya sendiri agar jurnal tidak disalahgunakan.
     */
    public function index(Request $request): View
    {
        $currentUser = Auth::user();
        $isAdmin = $currentUser?->hasRole('admin') ?? false;

        $filterPeriodeRekap = $this->resolveFilterPeriodeRekap($request);
        $periodeRekap = $filterPeriodeRekap['periode'];
        $bulanRekap = $filterPeriodeRekap['bulan'];
        $tahunRekap = $filterPeriodeRekap['tahun'];
        $semesterRekap = $filterPeriodeRekap['semester'];
        $namaPeriodeRekap = $filterPeriodeRekap['nama_periode'];

        $search = $isAdmin ? trim((string) $request->input('search', '')) : '';
        $tanggalMulai = $request->input('tanggal_mulai') ?: '';
        $tanggalSelesai = $request->input('tanggal_selesai') ?: '';
        $kelasFilter = (string) $request->input('kelas', '');
        $statusFilter = trim((string) $request->input('status', ''));

        $query = AbsensiGuru::with(['guru', 'dataKelas'])
            ->when(! $isAdmin, fn (Builder $q) => $this->batasiMilikGuru($q, $currentUser))
            ->when($isAdmin && $search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('nama_guru', 'like', "%{$search}%")
                        ->orWhere('mata_pelajaran', 'like', "%{$search}%")
                        ->orWhere('materi_pembelajaran', 'like', "%{$search}%");
                });
            })
            ->when($tanggalMulai !== '', fn (Builder $q) => $q->whereDate('tanggal', '>=', $tanggalMulai))
            ->when($tanggalSelesai !== '', fn (Builder $q) => $q->whereDate('tanggal', '<=', $tanggalSelesai))
            ->when($kelasFilter !== '', fn (Builder $q) => $q->where('kelas', (int) $kelasFilter))
            ->when($statusFilter !== '', fn (Builder $q) => $q->where('status', 'like', "%{$statusFilter}%"));

        $jurnal = $query->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();

        // Ringkasan statistik untuk kartu atas berdasarkan filter yang sedang aktif
        $ringkasanQuery = (clone $query);
        $totalCatatan = (clone $ringkasanQuery)->count();
        $totalMateri = (clone $ringkasanQuery)->whereNotNull('materi_pembelajaran')
            ->where('materi_pembelajaran', '!=', '')
            ->count();
        $totalTugas = (clone $ringkasanQuery)->where('status', 'like', '%Tugas%')->count();

        $rekapAbsensiGuru = collect();
        $totalKategoriRekap = [
            'hadir' => 0,
            'izin_tugas' => 0,
            'sakit_tugas' => 0,
            'cuti' => 0,
            'tidak_hadir' => 0,
        ];

        if ($isAdmin) {
            $rekapAbsensiGuru = $this->queryRekapAbsensiGuru(
                $filterPeriodeRekap['tanggal_mulai'],
                $filterPeriodeRekap['tanggal_akhir'],
            );
            $totalKategoriRekap = $this->hitungTotalKategori($rekapAbsensiGuru);
        }

        $daftarKelas = $this->daftarKelasTersedia($currentUser, $isAdmin);
        $daftarPengajar = $this->daftarPengajar($currentUser, $isAdmin);
        $daftarStatus = ['Hadir', 'Izin', 'Sakit', 'Tugas', 'Alfa'];
        $daftarHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $hariIni = Carbon::today()->translatedFormat('l');
        $tanggalHariIni = $this->tanggalHariIni();

        return view('jurnal-mengajar.index', compact(
            'jurnal',
            'daftarKelas',
            'daftarPengajar',
            'daftarStatus',
            'daftarHari',
            'hariIni',
            'tanggalHariIni',
            'search',
            'tanggalMulai',
            'tanggalSelesai',
            'kelasFilter',
            'statusFilter',
            'totalCatatan',
            'totalMateri',
            'totalTugas',
            'periodeRekap',
            'bulanRekap',
            'tahunRekap',
            'semesterRekap',
            'namaPeriodeRekap',
            'rekapAbsensiGuru',
            'totalKategoriRekap',
            'isAdmin'
        ));
    }

    /**
     * Export the selected teacher attendance summary to an Excel workbook.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $this->pastikanAdministrator();
        $filterPeriodeRekap = $this->resolveFilterPeriodeRekap($request);
        $rekapAbsensiGuru = $this->queryRekapAbsensiGuru(
            $filterPeriodeRekap['tanggal_mulai'],
            $filterPeriodeRekap['tanggal_akhir'],
        );
        $totalKategoriRekap = $this->hitungTotalKategori($rekapAbsensiGuru);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Absen Guru');
        $sheet->setCellValue('A1', 'Rekapitulasi Absen Guru');
        $sheet->setCellValue('A2', 'Periode: '.$filterPeriodeRekap['nama_periode']);
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = [
            'No',
            'Nama Guru',
            'Hadir',
            'Izin dengan Tugas',
            'Sakit dengan Tugas',
            'Cuti',
            'Tidak Hadir',
            'Total',
        ];
        foreach ($headers as $index => $header) {
            $column = chr(65 + $index);
            $sheet->setCellValue("{$column}4", $header);
        }

        $sheet->getStyle('A4:H4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A4:H4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A8A');
        $sheet->getStyle('A4:H4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 5;
        foreach ($rekapAbsensiGuru as $index => $rekapGuru) {
            $sheet->setCellValue("A{$row}", $index + 1);
            $sheet->setCellValueExplicit("B{$row}", (string) $rekapGuru->nama_guru, DataType::TYPE_STRING);
            $sheet->setCellValue("C{$row}", (int) $rekapGuru->hadir);
            $sheet->setCellValue("D{$row}", (int) $rekapGuru->izin_tugas);
            $sheet->setCellValue("E{$row}", (int) $rekapGuru->sakit_tugas);
            $sheet->setCellValue("F{$row}", (int) $rekapGuru->cuti);
            $sheet->setCellValue("G{$row}", (int) $rekapGuru->tidak_hadir);
            $sheet->setCellValue("H{$row}", $this->hitungTotalSesiGuru($rekapGuru));
            $row++;
        }

        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("C{$row}", $totalKategoriRekap['hadir']);
        $sheet->setCellValue("D{$row}", $totalKategoriRekap['izin_tugas']);
        $sheet->setCellValue("E{$row}", $totalKategoriRekap['sakit_tugas']);
        $sheet->setCellValue("F{$row}", $totalKategoriRekap['cuti']);
        $sheet->setCellValue("G{$row}", $totalKategoriRekap['tidak_hadir']);
        $sheet->setCellValue("H{$row}", array_sum($totalKategoriRekap));
        $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:H{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
        $sheet->getStyle("A4:H{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A5:H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B5:B'.($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $fileName = 'Rekap_Absen_Guru_'.Str::slug($filterPeriodeRekap['nama_periode']).'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export the selected teacher attendance summary to a PDF document.
     */
    public function exportPdf(Request $request): Response
    {
        $this->pastikanAdministrator();
        $filterPeriodeRekap = $this->resolveFilterPeriodeRekap($request);
        $rekapAbsensiGuru = $this->queryRekapAbsensiGuru(
            $filterPeriodeRekap['tanggal_mulai'],
            $filterPeriodeRekap['tanggal_akhir'],
        );
        $totalKategoriRekap = $this->hitungTotalKategori($rekapAbsensiGuru);
        $tanggalCetak = Carbon::now()->locale('id')->translatedFormat('d F Y H:i');

        return Pdf::loadView('jurnal-mengajar.pdf-rekap', compact(
            'filterPeriodeRekap',
            'rekapAbsensiGuru',
            'totalKategoriRekap',
            'tanggalCetak',
        ))->setPaper('a4', 'landscape')
            ->download('Rekap_Absen_Guru_'.Str::slug($filterPeriodeRekap['nama_periode']).'.pdf');
    }

    /**
     * Validate the selected reporting period and resolve its date range and label.
     *
     * @return array{periode: string, bulan: int, tahun: int, semester: string, tanggal_mulai: string, tanggal_akhir: string, nama_periode: string}
     */
    private function resolveFilterPeriodeRekap(Request $request): array
    {
        $now = Carbon::today();
        $tahunAjaranMulai = $now->month >= 7 ? $now->year : $now->year - 1;
        $periodeRekap = (string) $request->input('periode', 'bulan');

        $request->merge([
            'periode' => $periodeRekap,
            'bulan' => $request->input('bulan', $now->month),
            'tahun' => $request->input('tahun', $periodeRekap === 'semester' ? $tahunAjaranMulai : $now->year),
            'semester' => $request->input('semester', $now->month >= 7 ? 'ganjil' : 'genap'),
        ]);

        $aturanFilterRekap = [
            'periode' => ['required', 'in:bulan,semester'],
        ];

        if ($periodeRekap === 'bulan') {
            $aturanFilterRekap['bulan'] = ['required', 'integer', 'between:1,12'];
            $aturanFilterRekap['tahun'] = ['required', 'integer', 'between:2000,2100'];
        } elseif ($periodeRekap === 'semester') {
            $aturanFilterRekap['semester'] = ['required', 'in:ganjil,genap'];
            $aturanFilterRekap['tahun'] = ['required', 'integer', 'between:2000,2100'];
        }

        $filterRekap = $request->validate($aturanFilterRekap);
        $bulanRekap = (int) ($filterRekap['bulan'] ?? $now->month);
        $tahunRekap = (int) ($filterRekap['tahun'] ?? ($periodeRekap === 'semester' ? $tahunAjaranMulai : $now->year));
        $semesterRekap = $filterRekap['semester'] ?? ($now->month >= 7 ? 'ganjil' : 'genap');

        if ($periodeRekap === 'semester' && $semesterRekap === 'ganjil') {
            $tanggalMulaiRekap = Carbon::create($tahunRekap, 7, 1);
            $tanggalAkhirRekap = Carbon::create($tahunRekap, 12, 31);
            $namaPeriodeRekap = "Semester Ganjil TA {$tahunRekap}/".($tahunRekap + 1);
        } elseif ($periodeRekap === 'semester') {
            $tanggalMulaiRekap = Carbon::create($tahunRekap + 1, 1, 1);
            $tanggalAkhirRekap = Carbon::create($tahunRekap + 1, 6, 30);
            $namaPeriodeRekap = "Semester Genap TA {$tahunRekap}/".($tahunRekap + 1);
        } else {
            $tanggalMulaiRekap = Carbon::create($tahunRekap, $bulanRekap, 1)->startOfMonth();
            $tanggalAkhirRekap = $tanggalMulaiRekap->copy()->endOfMonth();
            $namaPeriodeRekap = $tanggalMulaiRekap->locale('id')->translatedFormat('F Y');
        }

        return [
            'periode' => $periodeRekap,
            'bulan' => $bulanRekap,
            'tahun' => $tahunRekap,
            'semester' => $semesterRekap,
            'tanggal_mulai' => $tanggalMulaiRekap->toDateString(),
            'tanggal_akhir' => $tanggalAkhirRekap->toDateString(),
            'nama_periode' => $namaPeriodeRekap,
        ];
    }

    /**
     * Aggregate teacher attendance sessions into the report categories.
     *
     * @return Collection<int, object>
     */
    private function queryRekapAbsensiGuru(string $tanggalMulai, string $tanggalAkhir): Collection
    {
        $statusLower = 'LOWER(absensi_gurus.status)';
        $statusHadir = "({$statusLower} LIKE '%hadir%' AND {$statusLower} NOT LIKE '%tidak hadir%') OR {$statusLower} LIKE '%tepat waktu%'";
        $statusIzinDenganTugas = "({$statusLower} LIKE '%izin%' OR {$statusLower} LIKE '%ijin%') AND {$statusLower} LIKE '%tugas%'";
        $statusSakitDenganTugas = "{$statusLower} LIKE '%sakit%' AND {$statusLower} LIKE '%tugas%'";
        $statusCuti = "{$statusLower} LIKE '%cuti%'";

        return AbsensiGuru::query()
            ->leftJoin('users as guru_users', 'guru_users.id', '=', 'absensi_gurus.guru_id')
            ->whereDate('absensi_gurus.tanggal', '>=', $tanggalMulai)
            ->whereDate('absensi_gurus.tanggal', '<=', $tanggalAkhir)
            ->selectRaw('COALESCE(guru_users.name, guru_users.username, absensi_gurus.nama_guru) as nama_guru')
            ->selectRaw("SUM(CASE WHEN {$statusHadir} THEN 1 ELSE 0 END) as hadir")
            ->selectRaw("SUM(CASE WHEN {$statusIzinDenganTugas} THEN 1 ELSE 0 END) as izin_tugas")
            ->selectRaw("SUM(CASE WHEN {$statusSakitDenganTugas} THEN 1 ELSE 0 END) as sakit_tugas")
            ->selectRaw("SUM(CASE WHEN {$statusCuti} THEN 1 ELSE 0 END) as cuti")
            ->selectRaw("SUM(CASE WHEN NOT (({$statusHadir}) OR ({$statusIzinDenganTugas}) OR ({$statusSakitDenganTugas}) OR ({$statusCuti})) THEN 1 ELSE 0 END) as tidak_hadir")
            ->groupByRaw('COALESCE(absensi_gurus.guru_id, absensi_gurus.nama_guru)')
            ->groupByRaw('COALESCE(guru_users.name, guru_users.username, absensi_gurus.nama_guru)')
            ->orderBy('nama_guru')
            ->get();
    }

    /**
     * @param  Collection<int, object>  $rekapAbsensiGuru
     * @return array{hadir: int, izin_tugas: int, sakit_tugas: int, cuti: int, tidak_hadir: int}
     */
    private function hitungTotalKategori(Collection $rekapAbsensiGuru): array
    {
        return [
            'hadir' => (int) $rekapAbsensiGuru->sum('hadir'),
            'izin_tugas' => (int) $rekapAbsensiGuru->sum('izin_tugas'),
            'sakit_tugas' => (int) $rekapAbsensiGuru->sum('sakit_tugas'),
            'cuti' => (int) $rekapAbsensiGuru->sum('cuti'),
            'tidak_hadir' => (int) $rekapAbsensiGuru->sum('tidak_hadir'),
        ];
    }

    private function hitungTotalSesiGuru(object $rekapGuru): int
    {
        return (int) $rekapGuru->hadir
            + (int) $rekapGuru->izin_tugas
            + (int) $rekapGuru->sakit_tugas
            + (int) $rekapGuru->cuti
            + (int) $rekapGuru->tidak_hadir;
    }

    private function pastikanAdministrator(): void
    {
        abort_unless(Auth::user()?->hasRole('admin'), 403);
    }

    /**
     * Batasi query ke catatan milik guru yang sedang login (termasuk data lama
     * yang masih menyimpan nama/username pada kolom nama_guru).
     */
    protected function batasiMilikGuru(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('guru_id', $user->id)
                ->orWhere(function (Builder $legacy) use ($user) {
                    $legacy->whereNull('guru_id')
                        ->where(function (Builder $nama) use ($user) {
                            $nama->where('nama_guru', (string) $user->id)
                                ->orWhere('nama_guru', $user->name)
                                ->orWhere('nama_guru', $user->username);
                        });
                });
        });
    }

    /**
     * Daftar kelas yang dapat dipilih pada filter (guru dibatasi ke kelas ampuannya).
     *
     * @return Collection<int, Kelas>
     */
    protected function daftarKelasTersedia(?User $user, bool $isAdmin)
    {
        $query = Kelas::query();

        if (! $isAdmin && $user) {
            $kelasIds = Jadwal::query()
                ->where(function (Builder $q) use ($user) {
                    $q->where('nama_pengajar', (string) $user->id)
                        ->orWhere('nama_pengajar', $user->username);
                })
                ->distinct()
                ->pluck('kelas')
                ->map(fn ($id) => (int) $id)
                ->all();

            $query->whereIn('id', $kelasIds);
        }

        return $query->orderBy('kelas', 'asc')->get();
    }

    /**
     * Daftar pengajar untuk form catat absensi guru.
     *
     * @return Collection<int, User>
     */
    protected function daftarPengajar(?User $user, bool $isAdmin)
    {
        if (! $isAdmin && $user) {
            return collect([$user]);
        }

        return User::where(function ($q) {
            $q->whereNull('role')->orWhere('role', '!=', 'siswa');
        })
            ->orderBy('name', 'asc')
            ->orderBy('username', 'asc')
            ->get();
    }

    /**
     * Tanggal hari ini dalam format Y-m-d untuk nilai awal form modal.
     */
    protected function tanggalHariIni(): string
    {
        return Carbon::today()->toDateString();
    }
}
