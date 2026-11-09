<?php

namespace App\Http\Controllers;

use App\Models\JamBelajar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JamBelajarController extends Controller
{
    /**
     * Display a listing of jam_belajars.
     */
    public function index(): View
    {
        $jamBelajars = JamBelajar::orderBy('waktu_mulai', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return view('jam-belajar.index', compact('jamBelajars'));
    }

    /**
     * Store a newly created jam_belajar in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jam_ke' => ['required', 'string', 'max:50'],
            'waktu_mulai' => ['required'],
            'waktu_selesai' => ['required'],
        ], [
            'jam_ke.required' => 'Kolom Jam Ke wajib diisi.',
            'waktu_mulai.required' => 'Waktu Mulai wajib diisi.',
            'waktu_selesai.required' => 'Waktu Selesai wajib diisi.',
        ]);

        JamBelajar::create($validated);

        return redirect()->back()
            ->with('success', "Jam belajar (Jam ke-{$validated['jam_ke']}) berhasil ditambahkan.");
    }

    /**
     * Update the specified jam_belajar in storage.
     */
    public function update(Request $request, JamBelajar $jamBelajar): RedirectResponse
    {
        $validated = $request->validate([
            'jam_ke' => ['required', 'string', 'max:50'],
            'waktu_mulai' => ['required'],
            'waktu_selesai' => ['required'],
        ], [
            'jam_ke.required' => 'Kolom Jam Ke wajib diisi.',
            'waktu_mulai.required' => 'Waktu Mulai wajib diisi.',
            'waktu_selesai.required' => 'Waktu Selesai wajib diisi.',
        ]);

        $jamBelajar->update($validated);

        return redirect()->back()
            ->with('success', "Data jam belajar (Jam ke-{$jamBelajar->jam_ke}) berhasil diperbarui.");
    }

    /**
     * Remove the specified jam_belajar from storage.
     */
    public function destroy(JamBelajar $jamBelajar): RedirectResponse
    {
        $label = $jamBelajar->jam_ke;
        $jamBelajar->delete();

        return redirect()->back()
            ->with('success', "Jam belajar (Jam ke-{$label}) berhasil dihapus.");
    }
}
