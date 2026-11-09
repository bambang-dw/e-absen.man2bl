<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QrCodeController extends Controller
{
    /**
     * Display printable student ID cards in portrait format on A4 paper.
     */
    public function index(Request $request): View
    {
        $query = Siswa::query();
        $kelas = $request->input('kelas');
        $search = $request->input('search');

        if ($kelas) {
            $query->where('kelas', $kelas);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        $siswas = $query->orderBy('kelas')->orderBy('nama')->get();
        $daftarKelas = Siswa::select('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
        $totalSiswa = Siswa::count();

        return view('qrcode.print', compact('siswas', 'daftarKelas', 'totalSiswa', 'kelas', 'search'));
    }

    /**
     * Print all student ID cards (direct alias for card layout).
     */
    public function printAll(Request $request): View
    {
        return $this->index($request);
    }

    /**
     * Display printable class room QR codes on A4 paper (2 columns x 2 rows per page = 4 QR codes).
     */
    public function printKelas(Request $request): View
    {
        $tahunFilter = $request->input('tahun_pelajaran');

        $query = Kelas::query();
        if (! empty($tahunFilter)) {
            $query->where('tahun_pelajaran', $tahunFilter);
        }

        $daftarKelas = $query->orderBy('tahun_pelajaran', 'desc')
            ->orderBy('kelas', 'asc')
            ->get();

        return view('qrcode.kelas', compact('daftarKelas', 'tahunFilter'));
    }
}
