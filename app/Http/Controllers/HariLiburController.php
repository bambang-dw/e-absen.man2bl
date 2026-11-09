<?php

namespace App\Http\Controllers;

use App\Models\HariLibur;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HariLiburController extends Controller
{
    /**
     * Display a listing of holidays and school calendar settings.
     */
    public function index(Request $request): View
    {
        $query = HariLibur::with('pembuat')->orderBy('tanggal', 'desc');

        if ($search = $request->input('search')) {
            $query->where('keterangan', 'like', "%{$search}%")
                ->orWhere('tanggal', 'like', "%{$search}%");
        }

        $hariLiburs = $query->paginate(15)->withQueryString();
        $statusHariIni = HariLibur::statusLibur();

        return view('hari-libur.index', compact('hariLiburs', 'statusHariIni', 'search'));
    }

    /**
     * Store a newly created holiday in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date', 'unique:hari_liburs,tanggal'],
            'keterangan' => ['required', 'string', 'max:255'],
        ], [
            'tanggal.required' => 'Tanggal libur wajib diisi.',
            'tanggal.unique' => 'Tanggal libur ini sudah pernah didaftarkan.',
            'keterangan.required' => 'Keterangan hari libur wajib diisi.',
        ]);

        $validated['created_by'] = Auth::id();

        $holiday = HariLibur::create($validated);
        $tglFormat = Carbon::parse($holiday->tanggal)->translatedFormat('d F Y');

        return redirect()->back()
            ->with('success', "Hari libur untuk tanggal {$tglFormat} ({$holiday->keterangan}) berhasil ditambahkan.");
    }

    /**
     * Remove the specified holiday from storage.
     */
    public function destroy(HariLibur $hariLibur): RedirectResponse
    {
        $tglFormat = Carbon::parse($hariLibur->tanggal)->translatedFormat('d F Y');
        $ket = $hariLibur->keterangan;
        $hariLibur->delete();

        return redirect()->back()
            ->with('success', "Hari libur {$tglFormat} ({$ket}) berhasil dihapus.");
    }
}
