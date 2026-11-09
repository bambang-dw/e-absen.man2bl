<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengaturanAplikasiController extends Controller
{
    /**
     * Display the application maintenance settings page.
     */
    public function index(): View
    {
        $totalAbsensi = Absensi::count();
        $tanggalAwal = Absensi::min('tanggal');
        $tanggalAkhir = Absensi::max('tanggal');

        $totalTepatWaktu = Absensi::where('status', 'Tepat Waktu')->count();
        $totalTerlambat = Absensi::where('status', 'Terlambat')->count();
        $totalIzinSakit = Absensi::whereIn('status', ['Izin', 'Ijin', 'Sakit'])->count();
        $totalAlfa = Absensi::whereIn('status', ['Alfa', 'Alpha'])->count();

        $totalSiswa = Siswa::count();

        return view('pengaturan-aplikasi.index', compact(
            'totalAbsensi',
            'tanggalAwal',
            'tanggalAkhir',
            'totalTepatWaktu',
            'totalTerlambat',
            'totalIzinSakit',
            'totalAlfa',
            'totalSiswa'
        ));
    }

    /**
     * Export complete attendance data archive to Excel before resetting database.
     */
    public function exportArsip(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Arsip Presensi');

        // Page Title & Header
        $sheet->setCellValue('A1', 'MAN 2 BANDAR LAMPUNG');
        $sheet->setCellValue('A2', 'ARSIP LENGKAP REKAPITULASI PRESENSI SISWA');
        $sheet->setCellValue('A3', 'Dicadangkan pada: '.Carbon::now()->translatedFormat('d F Y, H:i').' WIB | Total Catatan: '.Absensi::count().' Baris');

        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A3:I3');

        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true)->getColor()->setRGB('064E3B');
        $sheet->getStyle('A2')->getFont()->setSize(12)->setBold(true);
        $sheet->getStyle('A3')->getFont()->setSize(10)->setItalic(true);

        // Table Column Headers
        $headers = [
            'A5' => 'No',
            'B5' => 'Tanggal Presensi',
            'C5' => 'Sesi Absensi',
            'D5' => 'NIS',
            'E5' => 'NISN',
            'F5' => 'Nama Siswa',
            'G5' => 'Kelas',
            'H5' => 'Jam Scan Presensi',
            'I5' => 'Status Kehadiran',
        ];

        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']],
            ],
        ];
        $sheet->getStyle('A5:I5')->applyFromArray($headerStyle);
        $sheet->getRowDimension(5)->setRowHeight(26);

        // Fetch attendance records with related student and session
        $absensis = Absensi::with(['siswa', 'sesi'])
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam_scan', 'desc')
            ->get();

        $rowNumber = 6;
        $no = 1;

        foreach ($absensis as $ab) {
            $siswa = $ab->siswa;
            $sheet->setCellValue('A'.$rowNumber, $no++);
            $sheet->setCellValue('B'.$rowNumber, $ab->tanggal);
            $sheet->setCellValue('C'.$rowNumber, $ab->sesi?->nama_sesi ?? 'Presensi Pagi');
            $sheet->setCellValueExplicit('D'.$rowNumber, $ab->nis, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E'.$rowNumber, $siswa?->nisn ?: '-', DataType::TYPE_STRING);
            $sheet->setCellValue('F'.$rowNumber, $siswa?->nama ?? '-');
            $sheet->setCellValue('G'.$rowNumber, $siswa?->kelas ?? '-');
            $sheet->setCellValue('H'.$rowNumber, $ab->jam_scan ? substr($ab->jam_scan, 0, 8).' WIB' : '-');
            $sheet->setCellValue('I'.$rowNumber, $ab->status);

            // Centering specific columns
            $sheet->getStyle('A'.$rowNumber)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B'.$rowNumber)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D'.$rowNumber)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E'.$rowNumber)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G'.$rowNumber)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H'.$rowNumber)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('I'.$rowNumber)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Row Borders
            $sheet->getStyle('A'.$rowNumber.':I'.$rowNumber)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);

            $rowNumber++;
        }

        // Auto-size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Arsip_Presensi_MAN2_Bandar_Lampung_'.date('Y-m-d_His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Purge / Reset attendance database so the application can start fresh for a new academic year.
     */
    public function resetPresensi(Request $request): RedirectResponse
    {
        $request->validate([
            'konfirmasi' => ['required', 'string', 'in:KOSONGKAN'],
            'lingkup' => ['required', 'string', 'in:semua,sebelum_tanggal'],
            'tanggal_batas' => ['nullable', 'required_if:lingkup,sebelum_tanggal', 'date'],
        ], [
            'konfirmasi.required' => 'Ketik kata konfirmasi KOSONGKAN untuk melanjutkan.',
            'konfirmasi.in' => 'Kata konfirmasi harus tepat: KOSONGKAN.',
            'lingkup.required' => 'Pilih lingkup data yang ingin dikosongkan.',
            'tanggal_batas.required_if' => 'Tentukan tanggal batas data yang ingin dihapus.',
        ]);

        $lingkup = $request->input('lingkup');

        if ($lingkup === 'sebelum_tanggal' && $request->filled('tanggal_batas')) {
            $tanggalBatas = $request->input('tanggal_batas');
            $jumlahTerhapus = Absensi::where('tanggal', '<=', $tanggalBatas)->count();
            Absensi::where('tanggal', '<=', $tanggalBatas)->delete();

            $formattedDate = Carbon::parse($tanggalBatas)->translatedFormat('d F Y');

            return redirect()->route('pengaturan-aplikasi.index')->with('success', "Pembersihan berhasil! Sebanyak {$jumlahTerhapus} catatan presensi sebelum tanggal {$formattedDate} telah dikosongkan dari database.");
        }

        // Gunakan delete() alih-alih truncate() agar aman terhadap foreign key constraint
        // pada tabel absensi_siswas (nis -> siswas, sesi_id -> sesi_absensis, user_id -> users).
        $jumlahTerhapus = Absensi::count();
        Absensi::query()->delete();

        return redirect()->route('pengaturan-aplikasi.index')->with('success', "Pembersihan berhasil! Seluruh data presensi ({$jumlahTerhapus} catatan) telah dikosongkan. Sistem kini bersih dan siap mencatat presensi tahun ajaran baru.");
    }
}
