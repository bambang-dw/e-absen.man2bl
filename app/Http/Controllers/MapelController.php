<?php

namespace App\Http\Controllers;

use App\Models\Mapel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapelController extends Controller
{
    /**
     * Display a listing of mapels.
     */
    public function index(): View
    {
        $mapels = Mapel::orderBy('id', 'asc')
            ->paginate(15);

        return view('mapel.index', compact('mapels'));
    }

    /**
     * Store a newly created mapel in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_mapel' => ['nullable', 'string', 'max:50'],
            'mata_pelajaran' => ['required', 'string', 'max:150'],
            'kelas' => ['nullable', 'exists:kelases,id'],
            'nama_pengajar' => ['nullable', 'exists:users,id'],
        ], [
            'mata_pelajaran.required' => 'Nama Mata Pelajaran wajib diisi.',
        ]);

        Mapel::create($validated);

        return redirect()->back()
            ->with('success', "Mata pelajaran {$validated['mata_pelajaran']} berhasil ditambahkan.");
    }

    /**
     * Update the specified mapel in storage.
     */
    public function update(Request $request, Mapel $mapel): RedirectResponse
    {
        $validated = $request->validate([
            'kode_mapel' => ['nullable', 'string', 'max:50'],
            'mata_pelajaran' => ['required', 'string', 'max:150'],
            'kelas' => ['nullable', 'exists:kelases,id'],
            'nama_pengajar' => ['nullable', 'exists:users,id'],
        ], [
            'mata_pelajaran.required' => 'Nama Mata Pelajaran wajib diisi.',
        ]);

        $mapel->update($validated);

        return redirect()->back()
            ->with('success', "Mata pelajaran {$mapel->mata_pelajaran} berhasil diperbarui.");
    }

    /**
     * Remove the specified mapel from storage.
     */
    public function destroy(Mapel $mapel): RedirectResponse
    {
        $namaMapel = $mapel->mata_pelajaran;
        $mapel->delete();

        return redirect()->back()
            ->with('success', "Mata pelajaran {$namaMapel} berhasil dihapus.");
    }
}
