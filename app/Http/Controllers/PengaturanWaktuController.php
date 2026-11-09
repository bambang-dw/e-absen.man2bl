<?php

namespace App\Http\Controllers;

use App\Models\SesiAbsensi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengaturanWaktuController extends Controller
{
    /**
     * Display time settings and session lists.
     */
    public function index(): View
    {
        $sesis = SesiAbsensi::withCount('absensis')->orderBy('jam_mulai')->get();

        return view('pengaturan-waktu.index', compact('sesis'));
    }

    /**
     * Store a newly created session.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_sesi' => ['required', 'string', 'max:50', 'unique:sesi_absensis,kode_sesi'],
            'nama_sesi' => ['required', 'string', 'max:100'],
            'jam_mulai' => ['required'],
            'jam_terlambat' => ['required'],
            'jam_selesai' => ['required'],
            'is_aktif' => ['nullable'],
        ], [
            'kode_sesi.unique' => 'Kode sesi sudah digunakan.',
            'kode_sesi.required' => 'Kode sesi wajib diisi.',
            'nama_sesi.required' => 'Nama sesi wajib diisi.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_terlambat.required' => 'Batas jam tepat waktu/terlambat wajib diisi.',
            'jam_selesai.required' => 'Jam selesai sesi wajib diisi.',
        ]);

        $validated['is_aktif'] = $request->boolean('is_aktif');

        SesiAbsensi::create($validated);

        return redirect()->route('pengaturan-waktu.index')
            ->with('success', "Sesi {$validated['nama_sesi']} berhasil ditambahkan.");
    }

    /**
     * Update the specified session.
     */
    public function update(Request $request, SesiAbsensi $sesi): RedirectResponse
    {
        $validated = $request->validate([
            'kode_sesi' => ['required', 'string', 'max:50', 'unique:sesi_absensis,kode_sesi,'.$sesi->id],
            'nama_sesi' => ['required', 'string', 'max:100'],
            'jam_mulai' => ['required'],
            'jam_terlambat' => ['required'],
            'jam_selesai' => ['required'],
            'is_aktif' => ['nullable'],
        ]);

        $validated['is_aktif'] = $request->boolean('is_aktif');

        $sesi->update($validated);

        return redirect()->route('pengaturan-waktu.index')
            ->with('success', "Sesi {$sesi->nama_sesi} berhasil diperbarui.");
    }

    /**
     * Remove the specified session.
     */
    public function destroy(SesiAbsensi $sesi): RedirectResponse
    {
        $nama = $sesi->nama_sesi;
        $sesi->delete();

        return redirect()->route('pengaturan-waktu.index')
            ->with('success', "Sesi {$nama} berhasil dihapus.");
    }

    /**
     * Quick toggle active status for a session.
     */
    public function toggle(SesiAbsensi $sesi): RedirectResponse
    {
        $sesi->update(['is_aktif' => ! $sesi->is_aktif]);
        $statusStr = $sesi->is_aktif ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('pengaturan-waktu.index')
            ->with('success', "Sesi {$sesi->nama_sesi} berhasil {$statusStr}.");
    }
}
