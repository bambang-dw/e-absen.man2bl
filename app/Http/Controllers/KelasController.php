<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KelasController extends Controller
{
    /**
     * Display a listing of classes.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $tahun = $request->input('tahun_pelajaran');

        $query = Kelas::withCount('siswas');

        if ($tahun) {
            $query->where('tahun_pelajaran', $tahun);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('kelas', 'like', "%{$search}%")
                    ->orWhere('wali_kelas', 'like', "%{$search}%")
                    ->orWhere('tahun_pelajaran', 'like', "%{$search}%");
            });
        }

        $kelases = $query->orderBy('tahun_pelajaran', 'desc')
            ->orderBy('kelas', 'asc')
            ->paginate(15)
            ->withQueryString();

        $daftarTahun = Kelas::select('tahun_pelajaran')
            ->distinct()
            ->orderBy('tahun_pelajaran', 'desc')
            ->pluck('tahun_pelajaran');

        // Daftar guru/wali kelas untuk rekomendasi dropdown
        $daftarGuru = User::whereIn('role', ['guru', 'wali_kelas'])->orderBy('name')->get();

        return view('kelas.index', compact(
            'kelases',
            'daftarTahun',
            'daftarGuru',
            'search',
            'tahun'
        ));
    }

    /**
     * Store a newly created class in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_pelajaran' => ['required', 'string', 'max:20'],
            'kelas' => ['required', 'string', 'max:50'],
            'wali_kelas' => ['required', 'string', 'max:150'],
        ], [
            'tahun_pelajaran.required' => 'Tahun Pelajaran wajib diisi.',
            'kelas.required' => 'Nama Kelas wajib diisi.',
            'wali_kelas.required' => 'Nama Wali Kelas wajib diisi.',
        ]);

        // Cek duplikasi tahun pelajaran dan kelas
        $exists = Kelas::where('tahun_pelajaran', $validated['tahun_pelajaran'])
            ->where('kelas', $validated['kelas'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', "Kelas {$validated['kelas']} pada Tahun Pelajaran {$validated['tahun_pelajaran']} sudah ada.");
        }

        Kelas::create($validated);

        return redirect()->route('kelas.index')->with('success', "Kelas {$validated['kelas']} berhasil ditambahkan.");
    }

    /**
     * Update the specified class in storage.
     */
    public function update(Request $request, Kelas $kela): RedirectResponse
    {
        $validated = $request->validate([
            'tahun_pelajaran' => ['required', 'string', 'max:20'],
            'kelas' => ['required', 'string', 'max:50'],
            'wali_kelas' => ['required', 'string', 'max:150'],
        ], [
            'tahun_pelajaran.required' => 'Tahun Pelajaran wajib diisi.',
            'kelas.required' => 'Nama Kelas wajib diisi.',
            'wali_kelas.required' => 'Nama Wali Kelas wajib diisi.',
        ]);

        // Cek duplikasi dengan kelas lain
        $duplicate = Kelas::where('tahun_pelajaran', $validated['tahun_pelajaran'])
            ->where('kelas', $validated['kelas'])
            ->where('id', '!=', $kela->id)
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', "Kelas {$validated['kelas']} pada Tahun Pelajaran {$validated['tahun_pelajaran']} sudah digunakan.");
        }

        $kela->update($validated);

        return redirect()->route('kelas.index')->with('success', "Data Kelas {$kela->kelas} berhasil diperbarui.");
    }

    /**
     * Remove the specified class from storage.
     */
    public function destroy(Kelas $kela): RedirectResponse
    {
        $namaKelas = $kela->kelas;
        $kela->delete();

        return redirect()->route('kelas.index')->with('success', "Kelas {$namaKelas} berhasil dihapus.");
    }
}
