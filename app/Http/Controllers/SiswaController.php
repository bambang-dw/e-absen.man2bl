<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Siswa::query();

        // Search by Nama, NIS, or NISN
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        // Filter by Kelas
        if ($kelas = $request->input('kelas')) {
            $query->where('kelas', $kelas);
        }

        $siswas = $query->orderBy('kelas')->orderBy('nama')->paginate(15)->withQueryString();

        $daftarKelas = Siswa::select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');

        $totalSiswa = Siswa::count();
        $totalDenganFoto = Siswa::whereNotNull('foto')->count();

        return view('siswa.index', compact('siswas', 'daftarKelas', 'totalSiswa', 'totalDenganFoto'));
    }

    /**
     * Store a newly created student in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:20', 'unique:siswas,nis'],
            'nisn' => ['nullable', 'string', 'max:20', 'unique:siswas,nisn'],
            'nama' => ['required', 'string', 'max:100'],
            'kelas' => ['required', 'string', 'max:50'],
            'no_hp_siswa' => ['nullable', 'string', 'max:20'],
            'no_hp_ortu' => ['nullable', 'string', 'max:20'],
            'foto' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,gif,bmp,avif,heic', 'max:5120'],
        ], [
            'nis.unique' => 'NIS sudah digunakan oleh siswa lain.',
            'nisn.unique' => 'NISN sudah digunakan oleh siswa lain.',
            'foto.mimes' => 'File harus berupa gambar (JPG, JPEG, PNG, WebP).',
            'foto.max' => 'Ukuran foto maksimal 5MB.',
        ]);

        if (empty($validated['nisn'])) {
            $validated['nisn'] = '00'.$validated['nis'];
        }

        // Upload photo if present
        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $validated['foto'] = $this->saveStudentPhoto($request->file('foto'), $validated['nis']);
        }

        Siswa::create($validated);

        return redirect()->route('siswa.index')
            ->with('success', "Data siswa {$validated['nama']} berhasil ditambahkan.");
    }

    /**
     * Update the specified student in storage.
     */
    public function update(Request $request, Siswa $siswa): RedirectResponse
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:20', 'unique:siswas,nis,'.$siswa->id],
            'nisn' => ['nullable', 'string', 'max:20', 'unique:siswas,nisn,'.$siswa->id],
            'nama' => ['required', 'string', 'max:100'],
            'kelas' => ['required', 'string', 'max:50'],
            'no_hp_siswa' => ['nullable', 'string', 'max:20'],
            'no_hp_ortu' => ['nullable', 'string', 'max:20'],
            'foto' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,gif,bmp,avif,heic', 'max:5120'],
        ], [
            'nis.unique' => 'NIS sudah digunakan oleh siswa lain.',
            'nisn.unique' => 'NISN sudah digunakan oleh siswa lain.',
            'foto.mimes' => 'File harus berupa gambar (JPG, JPEG, PNG, WebP).',
            'foto.max' => 'Ukuran foto maksimal 5MB.',
        ]);

        if (empty($validated['nisn'])) {
            $validated['nisn'] = $siswa->nisn ?: ('00'.$validated['nis']);
        }

        // Handle photo remove request
        if ($request->boolean('hapus_foto') && $siswa->foto) {
            $siswa->deleteExistingFoto();
            $validated['foto'] = null;
        }

        // Handle new photo upload
        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $siswa->deleteExistingFoto();
            $validated['foto'] = $this->saveStudentPhoto($request->file('foto'), $validated['nis']);
        }

        $siswa->update($validated);

        return redirect()->route('siswa.index')
            ->with('success', "Data siswa {$siswa->nama} berhasil diperbarui.");
    }

    /**
     * Remove the specified student from storage.
     */
    public function destroy(Siswa $siswa): RedirectResponse
    {
        $nama = $siswa->nama;

        $siswa->deleteExistingFoto();

        $siswa->delete();

        return redirect()->route('siswa.index')
            ->with('success', "Data siswa {$nama} berhasil dihapus.");
    }

    /**
     * Store uploaded student photo into public/FotoSiswa and storage/app/public/FotoSiswa.
     */
    private function saveStudentPhoto(UploadedFile $file, string $nis): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: ($file->extension() ?: 'jpg'));
        $safeNis = preg_replace('/[^a-zA-Z0-9_-]/', '', $nis) ?: 'siswa';
        $filename = 'siswa_'.$safeNis.'_'.time().'.'.$ext;

        $publicFotoPath = public_path('FotoSiswa');
        $storageFotoPath = storage_path('app/public/FotoSiswa');

        if (! is_dir($publicFotoPath)) {
            @mkdir($publicFotoPath, 0777, true);
        }
        if (! is_dir($storageFotoPath)) {
            @mkdir($storageFotoPath, 0777, true);
        }

        $file->move($publicFotoPath, $filename);
        @copy($publicFotoPath.DIRECTORY_SEPARATOR.$filename, $storageFotoPath.DIRECTORY_SEPARATOR.$filename);

        return 'FotoSiswa/'.$filename;
    }

    /**
     * Import students from an uploaded XLSX file.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file_xlsx' => ['required', 'file', 'mimes:xlsx,xls'],
        ], [
            'file_xlsx.required' => 'Pilih file Excel (.xlsx) terlebih dahulu.',
            'file_xlsx.mimes' => 'File harus berformat .xlsx atau .xls.',
        ]);

        $file = $request->file('file_xlsx');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray(null, true, true, true);

            if (count($rows) < 2) {
                return redirect()->route('siswa.index')
                    ->with('error', 'File Excel kosong atau tidak memiliki data baris.');
            }

            // Identify header row columns
            $headerRow = array_shift($rows);
            $columnMap = [];

            foreach ($headerRow as $col => $headerName) {
                $clean = strtolower(trim((string) $headerName));
                $clean = str_replace([' ', '_', '-'], '', $clean);

                if (str_contains($clean, 'nisn')) {
                    $columnMap['nisn'] = $col;
                } elseif (str_contains($clean, 'nis')) {
                    $columnMap['nis'] = $col;
                } elseif (str_contains($clean, 'nama')) {
                    $columnMap['nama'] = $col;
                } elseif (str_contains($clean, 'kelas')) {
                    $columnMap['kelas'] = $col;
                } elseif (str_contains($clean, 'hpsiswa') || str_contains($clean, 'nomorhpsiswa') || str_contains($clean, 'nohpsiswa')) {
                    $columnMap['no_hp_siswa'] = $col;
                } elseif (str_contains($clean, 'hp') || str_contains($clean, 'telepon') || str_contains($clean, 'kontak') || str_contains($clean, 'ortu')) {
                    $columnMap['no_hp_ortu'] = $col;
                }
            }

            if (! isset($columnMap['nis']) || ! isset($columnMap['nama']) || ! isset($columnMap['kelas'])) {
                return redirect()->route('siswa.index')
                    ->with('error', 'Format kolom Excel tidak sesuai. Minimal harus memiliki kolom: NIS, NAMA, dan KELAS.');
            }

            $inserted = 0;
            $updated = 0;

            foreach ($rows as $row) {
                $nis = trim((string) ($row[$columnMap['nis']] ?? ''));
                $nama = trim((string) ($row[$columnMap['nama']] ?? ''));
                $kelas = trim((string) ($row[$columnMap['kelas']] ?? ''));
                $nisn = isset($columnMap['nisn']) ? trim((string) ($row[$columnMap['nisn']] ?? '')) : '';
                $noHpSiswa = isset($columnMap['no_hp_siswa']) ? trim((string) ($row[$columnMap['no_hp_siswa']] ?? '')) : null;
                $noHp = isset($columnMap['no_hp_ortu']) ? trim((string) ($row[$columnMap['no_hp_ortu']] ?? '')) : null;

                if (empty($nis) || empty($nama) || empty($kelas)) {
                    continue;
                }

                if (empty($nisn)) {
                    $nisn = '00'.$nis;
                }

                $existing = Siswa::where('nis', $nis)->first();

                if ($existing) {
                    $existing->update([
                        'nisn' => $nisn,
                        'nama' => $nama,
                        'kelas' => $kelas,
                        'no_hp_siswa' => $noHpSiswa ?: $existing->no_hp_siswa,
                        'no_hp_ortu' => $noHp ?: $existing->no_hp_ortu,
                    ]);
                    $updated++;
                } else {
                    Siswa::create([
                        'nis' => $nis,
                        'nisn' => $nisn,
                        'nama' => $nama,
                        'kelas' => $kelas,
                        'no_hp_siswa' => $noHpSiswa,
                        'no_hp_ortu' => $noHp,
                    ]);
                    $inserted++;
                }
            }

            return redirect()->route('siswa.index')
                ->with('success', "Import Excel berhasil! {$inserted} siswa baru ditambahkan, {$updated} siswa diperbarui.");
        } catch (\Exception $e) {
            return redirect()->route('siswa.index')
                ->with('error', 'Terjadi kesalahan saat memproses file Excel: '.$e->getMessage());
        }
    }

    /**
     * Download the standard Excel import template.
     */
    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import Siswa');

        // Headers
        $headers = ['NIS', 'NISN', 'NAMA LENGKAP', 'KELAS', 'NO HP ORTU'];
        $cols = ['A', 'B', 'C', 'D', 'E'];

        foreach ($headers as $index => $header) {
            $col = $cols[$index];
            $sheet->setCellValue("{$col}1", $header);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("{$col}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2563EB');
            $sheet->getStyle("{$col}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Example rows
        $sampleData = [
            ['20261001', '0020261001', 'Ahmad Rizky Pratama', 'X-A', '081234567890'],
            ['20261002', '0020261002', 'Siti Nur Aisyah', 'X-A', '081234567891'],
            ['20261003', '0020261003', 'Budi Santoso', 'X-B', '081234567892'],
        ];

        $rowNum = 2;
        foreach ($sampleData as $data) {
            foreach ($data as $index => $val) {
                $col = $cols[$index];
                $sheet->setCellValueExplicit("{$col}{$rowNum}", $val, DataType::TYPE_STRING);
            }
            $rowNum++;
        }

        // Auto width
        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'Template_Import_Siswa_E-Absen.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export students data to XLSX.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = Siswa::query();

        if ($kelas = $request->input('kelas')) {
            $query->where('kelas', $kelas);
        }

        $siswas = $query->orderBy('kelas')->orderBy('nama')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Siswa');

        // Headers
        $headers = ['NO', 'NIS', 'NISN', 'NAMA LENGKAP', 'KELAS', 'NO HP SISWA', 'NO HP ORTU', 'FOTO'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        foreach ($headers as $index => $header) {
            $col = $cols[$index];
            $sheet->setCellValue("{$col}1", $header);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("{$col}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E293B');
            $sheet->getStyle("{$col}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $rowNum = 2;
        foreach ($siswas as $idx => $s) {
            $sheet->setCellValue("A{$rowNum}", $idx + 1);
            $sheet->setCellValueExplicit("B{$rowNum}", $s->nis, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$rowNum}", $s->nisn ?: '-', DataType::TYPE_STRING);
            $sheet->setCellValue("D{$rowNum}", $s->nama);
            $sheet->setCellValue("E{$rowNum}", $s->kelas);
            $sheet->setCellValueExplicit("F{$rowNum}", $s->no_hp_siswa ?: '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$rowNum}", $s->no_hp_ortu ?: '-', DataType::TYPE_STRING);
            $sheet->setCellValue("H{$rowNum}", $s->foto ? 'Ada' : 'Belum Ada');

            // Alignment
            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $rowNum++;
        }

        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'Data_Siswa_'.($kelas ? "Kelas_{$kelas}_" : '').date('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
