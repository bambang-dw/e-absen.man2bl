<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\SesiAbsensi;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RekapitulasiController extends Controller
{
    /**
     * Display attendance recap by class and grand total with period and class filters.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $currentUser = Auth::user();

        // Akun Guru tanpa role admin atau wali kelas tidak dapat mengakses rekapitulasi
        if ($currentUser && $currentUser->hasRole('guru') && ! $currentUser->hasRole('admin') && ! $currentUser->hasRole('wali_kelas')) {
            return redirect()->route('guru.dashboard')->with('error', 'Menu Rekapitulasi hanya dapat diakses oleh Administrator dan Wali Kelas.');
        }

        // Deteksi dan batasi kelas jika login sebagai Wali Kelas
        $isWaliKelasOnly = $currentUser && $currentUser->hasRole('wali_kelas') && ! $currentUser->hasRole('admin');
        if ($isWaliKelasOnly) {
            $daftarKelasWali = $currentUser->getDaftarKelasWali();
            $semuaDaftarKelas = $daftarKelasWali->isNotEmpty() ? $daftarKelasWali : Siswa::select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
        } else {
            $semuaDaftarKelas = Siswa::select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
        }

        $periode = $request->input('periode', 'harian');
        $now = Carbon::today();
        $tanggal = $request->input('tanggal', $now->toDateString());
        $bulan = (int) $request->input('bulan', $now->month);
        $semester = $request->input('semester', $now->month >= 7 ? '1' : '2');
        $tahun = (int) ($request->input('tahun_bulan') ?: ($request->input('tahun_semester') ?: $request->input('tahun', $now->year)));
        $sesiId = $request->input('sesi_id', 'all');
        $selectedKelas = $request->input('kelas', null);
        $selectedKategori = $request->input('kategori', null);

        // Jika Wali Kelas, kunci filter kelas hanya pada kelas binaannya
        if ($isWaliKelasOnly) {
            if ($selectedKelas === null || ! $semuaDaftarKelas->contains($selectedKelas)) {
                $selectedKelas = $semuaDaftarKelas->first();
            }
        }

        $namaBulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        if ($periode === 'bulan') {
            $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString();
            $namaPeriode = 'Bulan '.($namaBulanList[$bulan] ?? $bulan).' '.$tahun;
        } elseif ($periode === 'semester') {
            if ($semester === '1') {
                // Semester 1: Juli - Desember
                $startDate = Carbon::create($tahun, 7, 1)->toDateString();
                $endDate = Carbon::create($tahun, 12, 31)->toDateString();
                $namaPeriode = 'Semester 1 (Juli - Desember '.$tahun.')';
            } else {
                // Semester 2: Januari - Juli (sesuai permintaan user: "semester 2: Januari - Juli")
                $startDate = Carbon::create($tahun, 1, 1)->toDateString();
                $endDate = Carbon::create($tahun, 7, 31)->toDateString();
                $namaPeriode = 'Semester 2 (Januari - Juli '.$tahun.')';
            }
        } else {
            // Harian (default)
            $periode = 'harian';
            $startDate = $tanggal;
            $endDate = $tanggal;
            $namaPeriode = Carbon::parse($tanggal)->translatedFormat('l, d F Y');
        }

        $rekapData = $this->calculateRekapData($startDate, $endDate, $sesiId, $selectedKelas, $periode);

        $daftarSesi = SesiAbsensi::where('is_aktif', true)->orderBy('jam_mulai')->get();
        $selectedSesi = $sesiId !== 'all' ? SesiAbsensi::find($sesiId) : null;

        // Category bar chart data (Tepat Waktu, Terlambat, Izin, Sakit, Alpha)
        $categoryData = [
            'labels' => ['Tepat Waktu', 'Terlambat', 'Izin', 'Sakit', 'Alpha'],
            'values' => [
                $rekapData['grandTotal']['tepat_waktu'],
                $rekapData['grandTotal']['terlambat'],
                $rekapData['grandTotal']['izin'],
                $rekapData['grandTotal']['sakit'],
                $rekapData['grandTotal']['alfa'],
            ],
            'colors' => ['#10b981', '#f59e0b', '#0ea5e9', '#64748b', '#ef4444'],
            'borderColors' => ['#059669', '#d97706', '#0284c7', '#475569', '#dc2626'],
        ];

        return view('rekapitulasi.index', array_merge($rekapData, [
            'periode' => $periode,
            'tanggal' => $tanggal,
            'bulan' => $bulan,
            'semester' => $semester,
            'tahun' => $tahun,
            'namaPeriode' => $namaPeriode,
            'namaBulanList' => $namaBulanList,
            'sesiId' => $sesiId,
            'selectedKelas' => $selectedKelas,
            'selectedKategori' => $selectedKategori,
            'semuaDaftarKelas' => $semuaDaftarKelas,
            'daftarSesi' => $daftarSesi,
            'selectedSesi' => $selectedSesi,
            'categoryData' => $categoryData,
        ]));
    }

    /**
     * Export attendance recap to Excel file.
     */
    public function export(Request $request): StreamedResponse
    {
        $currentUser = Auth::user();

        if ($currentUser && $currentUser->hasRole('guru') && ! $currentUser->hasRole('admin') && ! $currentUser->hasRole('wali_kelas')) {
            abort(403, 'Akses Rekapitulasi hanya untuk Administrator dan Wali Kelas.');
        }

        $periode = $request->input('periode', 'harian');
        $now = Carbon::today();
        $tanggal = $request->input('tanggal', $now->toDateString());
        $bulan = (int) $request->input('bulan', $now->month);
        $semester = $request->input('semester', $now->month >= 7 ? '1' : '2');
        $tahun = (int) ($request->input('tahun_bulan') ?: ($request->input('tahun_semester') ?: $request->input('tahun', $now->year)));
        $sesiId = $request->input('sesi_id', 'all');
        $selectedKelas = $request->input('kelas', null);

        $isWaliKelasOnly = $currentUser && $currentUser->hasRole('wali_kelas') && ! $currentUser->hasRole('admin');
        if ($isWaliKelasOnly) {
            $daftarKelasWali = $currentUser->getDaftarKelasWali();
            $validKelas = $daftarKelasWali->isNotEmpty() ? $daftarKelasWali : Siswa::select('kelas')->distinct()->pluck('kelas');
            if ($selectedKelas === null || ! $validKelas->contains($selectedKelas)) {
                $selectedKelas = $validKelas->first();
            }
        }

        $namaBulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        if ($periode === 'bulan') {
            $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString();
            $namaPeriode = 'Bulan '.($namaBulanList[$bulan] ?? $bulan).' '.$tahun;
            $fileSlug = "Bulan_{$bulan}_{$tahun}";
        } elseif ($periode === 'semester') {
            if ($semester === '1') {
                $startDate = Carbon::create($tahun, 7, 1)->toDateString();
                $endDate = Carbon::create($tahun, 12, 31)->toDateString();
                $namaPeriode = 'Semester 1 (Juli - Desember '.$tahun.')';
                $fileSlug = "Semester_1_{$tahun}";
            } else {
                $startDate = Carbon::create($tahun, 1, 1)->toDateString();
                $endDate = Carbon::create($tahun, 7, 31)->toDateString();
                $namaPeriode = 'Semester 2 (Januari - Juli '.$tahun.')';
                $fileSlug = "Semester_2_{$tahun}";
            }
        } else {
            $periode = 'harian';
            $startDate = $tanggal;
            $endDate = $tanggal;
            $namaPeriode = Carbon::parse($tanggal)->translatedFormat('l, d F Y');
            $fileSlug = $tanggal;
        }

        $rekapData = $this->calculateRekapData($startDate, $endDate, $sesiId, $selectedKelas, $periode);
        $sesiName = $sesiId !== 'all' ? (SesiAbsensi::find($sesiId)?->nama_sesi ?? 'Sesi') : 'Semua Sesi';
        $kelasLabel = $selectedKelas ? "Kelas {$selectedKelas}" : 'Semua Kelas';

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Presensi');

        // Title & metadata
        $sheet->setCellValue('A1', 'LAPORAN REKAPITULASI PRESENSI SISWA');
        $sheet->setCellValue('A2', "Periode: {$namaPeriode} | Kelas: {$kelasLabel} | Sesi: {$sesiName}");
        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(11);
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header Table
        $headers = [
            'NO',
            'KELAS',
            'TOTAL SISWA',
            'TEPAT WAKTU',
            'TERLAMBAT',
            'IZIN',
            'SAKIT',
            'ALFA',
            'TOTAL HADIR',
            'PERSENTASE',
        ];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

        $rowNum = 4;
        foreach ($headers as $index => $header) {
            $col = $cols[$index];
            $sheet->setCellValue("{$col}{$rowNum}", $header);
            $sheet->getStyle("{$col}{$rowNum}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("{$col}{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A8A');
            $sheet->getStyle("{$col}{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Data rows
        $rowNum = 5;
        foreach ($rekapData['tabelKelas'] as $idx => $row) {
            $sheet->setCellValue("A{$rowNum}", $idx + 1);
            $sheet->setCellValue("B{$rowNum}", $row['kelas']);
            $sheet->setCellValue("C{$rowNum}", $row['total_siswa']);
            $sheet->setCellValue("D{$rowNum}", $row['tepat_waktu']);
            $sheet->setCellValue("E{$rowNum}", $row['terlambat']);
            $sheet->setCellValue("F{$rowNum}", $row['izin']);
            $sheet->setCellValue("G{$rowNum}", $row['sakit']);
            $sheet->setCellValue("H{$rowNum}", $row['alfa']);
            $sheet->setCellValue("I{$rowNum}", $row['total_hadir']);
            $sheet->setCellValue("J{$rowNum}", "{$row['persentase']}%");

            foreach ($cols as $col) {
                $sheet->getStyle("{$col}{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            $rowNum++;
        }

        // Grand Total Row
        $grand = $rekapData['grandTotal'];
        $sheet->setCellValue("A{$rowNum}", 'JUMLAH TOTAL');
        $sheet->mergeCells("A{$rowNum}:B{$rowNum}");
        $sheet->setCellValue("C{$rowNum}", $grand['total_siswa']);
        $sheet->setCellValue("D{$rowNum}", $grand['tepat_waktu']);
        $sheet->setCellValue("E{$rowNum}", $grand['terlambat']);
        $sheet->setCellValue("F{$rowNum}", $grand['izin']);
        $sheet->setCellValue("G{$rowNum}", $grand['sakit']);
        $sheet->setCellValue("H{$rowNum}", $grand['alfa']);
        $sheet->setCellValue("I{$rowNum}", $grand['total_hadir']);
        $sheet->setCellValue("J{$rowNum}", "{$grand['persentase']}%");

        // Styling for grand total
        $sheet->getStyle("A{$rowNum}:J{$rowNum}")->getFont()->setBold(true);
        $sheet->getStyle("A{$rowNum}:J{$rowNum}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
        foreach ($cols as $col) {
            $sheet->getStyle("{$col}{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Border all table
        $sheet->getStyle("A4:J{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = "Rekap_Presensi_{$fileSlug}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Calculate attendance per class and grand total across date range.
     */
    private function calculateRekapData(string $startDate, string $endDate, string $sesiId, ?string $selectedKelas = null, string $periode = 'harian'): array
    {
        $currentUser = Auth::user();
        $isWaliKelasOnly = $currentUser && $currentUser->hasRole('wali_kelas') && ! $currentUser->hasRole('admin');

        if ($isWaliKelasOnly) {
            $waliClasses = $currentUser->getDaftarKelasWali();
            if ($waliClasses->isNotEmpty()) {
                if ($selectedKelas && $selectedKelas !== 'all' && $waliClasses->contains($selectedKelas)) {
                    $daftarKelas = collect([$selectedKelas]);
                } else {
                    $daftarKelas = $waliClasses;
                    $selectedKelas = $waliClasses->first();
                }
            } else {
                $daftarKelas = $selectedKelas ? collect([$selectedKelas]) : Siswa::select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
            }
        } else {
            $allClassesQuery = Siswa::select('kelas')->distinct()->orderBy('kelas');
            if ($selectedKelas && $selectedKelas !== 'all') {
                $daftarKelas = Siswa::where('kelas', $selectedKelas)->select('kelas')->distinct()->pluck('kelas');
            } else {
                $daftarKelas = $allClassesQuery->pluck('kelas');
            }
        }

        $waliKelasMap = Kelas::pluck('wali_kelas', 'kelas')->toArray();

        $tabelKelas = [];
        $semuaSiswa = [];
        $grandTotal = [
            'total_siswa' => 0,
            'tepat_waktu' => 0,
            'terlambat' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alfa' => 0,
            'total_hadir' => 0,
            'persentase' => 0,
        ];

        $isHarian = ($periode === 'harian' || $startDate === $endDate);

        // Fetch all absensi in date range
        $globalAbsensiQuery = Absensi::whereBetween('tanggal', [$startDate, $endDate]);
        if ($sesiId !== 'all') {
            $globalAbsensiQuery->where('sesi_id', $sesiId);
        }
        $allAbsensis = $globalAbsensiQuery->get();
        $distinctDates = $allAbsensis->pluck('tanggal')->unique()->count();
        $hariEfektif = max(1, $distinctDates ?: 1);

        foreach ($daftarKelas as $kelas) {
            $siswaList = Siswa::where('kelas', $kelas)->orderBy('nama')->get();
            $nisList = $siswaList->pluck('nis');
            $totalSiswaKelas = $siswaList->count();

            $absensiRecords = $allAbsensis->whereIn('nis', $nisList);

            $siswaDetail = [];
            $tepatCount = 0;
            $lambatCount = 0;
            $izinCount = 0;
            $sakitCount = 0;
            $alfaCount = 0;

            foreach ($siswaList as $s) {
                $records = $absensiRecords->where('nis', $s->nis);

                $tepatStudent = $records->where('status', 'Tepat Waktu')->count();
                $lambatStudent = $records->where('status', 'Terlambat')->count();
                $izinStudent = $records->filter(fn ($a) => in_array($a->status, ['Izin', 'Ijin']))->count();
                $sakitStudent = $records->where('status', 'Sakit')->count();
                $alfaStudent = $records->filter(fn ($a) => in_array($a->status, ['Alfa', 'Alpha']))->count();

                if ($isHarian) {
                    $rec = $records->first();
                    $status = $rec ? $rec->status : 'Alfa';
                    $jamScan = $rec ? $rec->jam_scan : '-';
                    $studentTotalHadir = in_array($status, ['Tepat Waktu', 'Terlambat']) ? 1 : 0;
                    $studentPersen = $studentTotalHadir * 100;

                    if ($status === 'Tepat Waktu') {
                        $tepatCount++;
                    } elseif ($status === 'Terlambat') {
                        $lambatCount++;
                    } elseif (in_array($status, ['Izin', 'Ijin'])) {
                        $izinCount++;
                    } elseif ($status === 'Sakit') {
                        $sakitCount++;
                    } else {
                        $alfaCount++;
                    }
                } else {
                    $tepatCount += $tepatStudent;
                    $lambatCount += $lambatStudent;
                    $izinCount += $izinStudent;
                    $sakitCount += $sakitStudent;
                    $alfaCount += $alfaStudent;

                    $studentTotalHadir = $tepatStudent + $lambatStudent;
                    $studentPersen = round(($studentTotalHadir / $hariEfektif) * 100, 1);
                    $studentPersen = min(100, $studentPersen);
                    $jamScan = $records->count() > 0 ? $records->count().' Hari Hadir' : '-';

                    $status = match (true) {
                        $studentTotalHadir >= ($hariEfektif * 0.85) => 'Tepat Waktu',
                        $lambatStudent > $tepatStudent => 'Terlambat',
                        $izinStudent > 0 && $studentTotalHadir == 0 => 'Izin',
                        $sakitStudent > 0 && $studentTotalHadir == 0 => 'Sakit',
                        $studentTotalHadir > 0 => 'Tepat Waktu',
                        default => 'Alfa',
                    };
                }

                $studentData = [
                    'id' => $s->id,
                    'nis' => $s->nis,
                    'nisn' => $s->nisn,
                    'nama' => $s->nama,
                    'kelas' => $kelas,
                    'foto_url' => $s->foto_url,
                    'no_hp_ortu' => $s->no_hp_ortu,
                    'status' => $status,
                    'jam_scan' => $jamScan,
                    'tepat' => $tepatStudent,
                    'lambat' => $lambatStudent,
                    'izin' => $izinStudent,
                    'sakit' => $sakitStudent,
                    'alfa' => $alfaStudent,
                    'total_hadir' => $studentTotalHadir,
                    'persentase' => $studentPersen,
                ];

                $siswaDetail[] = $studentData;
                $semuaSiswa[] = $studentData;
            }

            $totalHadirKelas = $tepatCount + $lambatCount;
            if ($isHarian) {
                $persentaseKelas = $totalSiswaKelas > 0 ? round(($totalHadirKelas / $totalSiswaKelas) * 100, 1) : 0;
            } else {
                $possibleAttendance = $totalSiswaKelas * $hariEfektif;
                $persentaseKelas = $possibleAttendance > 0 ? round(($totalHadirKelas / $possibleAttendance) * 100, 1) : 0;
            }

            $tabelKelas[] = [
                'kelas' => $kelas,
                'wali_kelas' => $waliKelasMap[$kelas] ?? null,
                'total_siswa' => $totalSiswaKelas,
                'tepat_waktu' => $tepatCount,
                'terlambat' => $lambatCount,
                'izin' => $izinCount,
                'sakit' => $sakitCount,
                'alfa' => $alfaCount,
                'total_hadir' => $totalHadirKelas,
                'persentase' => $persentaseKelas,
                'siswa_detail' => $siswaDetail,
            ];

            // Add to grand total
            $grandTotal['total_siswa'] += $totalSiswaKelas;
            $grandTotal['tepat_waktu'] += $tepatCount;
            $grandTotal['terlambat'] += $lambatCount;
            $grandTotal['izin'] += $izinCount;
            $grandTotal['sakit'] += $sakitCount;
            $grandTotal['alfa'] += $alfaCount;
            $grandTotal['total_hadir'] += $totalHadirKelas;
        }

        if ($isHarian) {
            $grandTotal['persentase'] = $grandTotal['total_siswa'] > 0
                ? round(($grandTotal['total_hadir'] / $grandTotal['total_siswa']) * 100, 1)
                : 0;
        } else {
            $totalPossible = $grandTotal['total_siswa'] * $hariEfektif;
            $grandTotal['persentase'] = $totalPossible > 0
                ? round(($grandTotal['total_hadir'] / $totalPossible) * 100, 1)
                : 0;
        }

        return [
            'tabelKelas' => $tabelKelas,
            'grandTotal' => $grandTotal,
            'semuaSiswa' => $semuaSiswa,
            'hariEfektif' => $hariEfektif,
            'isHarian' => $isHarian,
        ];
    }
}
