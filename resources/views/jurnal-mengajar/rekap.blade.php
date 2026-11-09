<div class="card-custom p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h5 class="fw-bold text-dark mb-1">
                <i class="bi bi-bar-chart-fill text-primary me-2"></i> Rekapitulasi Absen Guru
            </h5>
            <p class="text-muted small mb-0">
                Periode: <strong>{{ $namaPeriodeRekap }}</strong> · Jumlah dihitung berdasarkan sesi/jam mengajar.
            </p>
        </div>
        <div class="d-flex flex-wrap align-items-end gap-2">
            <form action="{{ route('jurnal-mengajar.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label for="periode-rekap-guru" class="form-label small fw-bold mb-1">Periode</label>
                    <select id="periode-rekap-guru" name="periode" class="form-select form-select-sm">
                        <option value="bulan" {{ $periodeRekap === 'bulan' ? 'selected' : '' }}>Per Bulan</option>
                        <option value="semester" {{ $periodeRekap === 'semester' ? 'selected' : '' }}>Per Semester</option>
                    </select>
                </div>
                <div class="col-auto" data-rekap-filter="bulan" @if ($periodeRekap !== 'bulan') hidden @endif>
                    <label for="bulan-rekap-guru" class="form-label small fw-bold mb-1">Bulan</label>
                    <select id="bulan-rekap-guru" name="bulan" class="form-select form-select-sm">
                        @for ($bulan = 1; $bulan <= 12; $bulan++)
                            <option value="{{ $bulan }}" {{ $bulanRekap === $bulan ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create($tahunRekap, $bulan, 1)->locale('id')->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-auto" data-rekap-filter="semester" @if ($periodeRekap !== 'semester') hidden @endif>
                    <label for="semester-rekap-guru" class="form-label small fw-bold mb-1">Semester</label>
                    <select id="semester-rekap-guru" name="semester" class="form-select form-select-sm">
                        <option value="ganjil" {{ $semesterRekap === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                        <option value="genap" {{ $semesterRekap === 'genap' ? 'selected' : '' }}>Genap</option>
                    </select>
                </div>
                <div class="col-auto" data-rekap-filter="tahun">
                    <label for="tahun-rekap-guru" class="form-label small fw-bold mb-1" data-tahun-label>
                        {{ $periodeRekap === 'semester' ? 'Tahun Ajaran Mulai' : 'Tahun' }}
                    </label>
                    <input id="tahun-rekap-guru" type="number" name="tahun" class="form-control form-control-sm"
                        min="2000" max="2100" value="{{ $tahunRekap }}" required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-funnel-fill me-1"></i> Tampilkan
                    </button>
                </div>
            </form>

            @php
                $parameterEksporRekap = request()->only(['periode', 'bulan', 'tahun', 'semester']);
            @endphp
            <a href="{{ route('jurnal-mengajar.export.excel', $parameterEksporRekap) }}"
                class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </a>
            <a href="{{ route('jurnal-mengajar.export.pdf', $parameterEksporRekap) }}"
                class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="border rounded-3 p-3 h-100">
                <h6 class="fw-bold text-dark mb-3">Jumlah Sesi per Kategori</h6>
                <div style="height: 280px;">
                    <canvas id="rekapAbsensiGuruChart" role="img"
                        aria-label="Diagram batang rekapitulasi absen guru"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="border rounded-3 overflow-hidden h-100">
                <div class="px-3 py-2 bg-light border-bottom">
                    <h6 class="fw-bold text-dark mb-0">Rekapitulasi Absen Mengajar</h6>
                </div>
                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light position-sticky top-0" style="z-index: 1;">
                            <tr class="small text-uppercase">
                                <th class="ps-3">Nama Guru</th>
                                <th class="text-center">Hadir</th>
                                <th class="text-center">Izin dengan Tugas</th>
                                <th class="text-center">Sakit dengan Tugas</th>
                                <th class="text-center">Cuti</th>
                                <th class="text-center">Tidak Hadir</th>
                                <th class="text-center pe-3">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rekapAbsensiGuru as $rekapGuru)
                                @php
                                    $totalSesiGuru = (int) $rekapGuru->hadir
                                        + (int) $rekapGuru->izin_tugas
                                        + (int) $rekapGuru->sakit_tugas
                                        + (int) $rekapGuru->cuti
                                        + (int) $rekapGuru->tidak_hadir;
                                @endphp
                                <tr>
                                    <td class="ps-3 fw-semibold text-nowrap">{{ $rekapGuru->nama_guru }}</td>
                                    <td class="text-center">{{ number_format($rekapGuru->hadir) }}</td>
                                    <td class="text-center">{{ number_format($rekapGuru->izin_tugas) }}</td>
                                    <td class="text-center">{{ number_format($rekapGuru->sakit_tugas) }}</td>
                                    <td class="text-center">{{ number_format($rekapGuru->cuti) }}</td>
                                    <td class="text-center">{{ number_format($rekapGuru->tidak_hadir) }}</td>
                                    <td class="text-center pe-3 fw-bold">{{ number_format($totalSesiGuru) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Belum ada data absensi guru pada periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td class="ps-3">Total Sesi</td>
                                <td class="text-center">{{ number_format($totalKategoriRekap['hadir']) }}</td>
                                <td class="text-center">{{ number_format($totalKategoriRekap['izin_tugas']) }}</td>
                                <td class="text-center">{{ number_format($totalKategoriRekap['sakit_tugas']) }}</td>
                                <td class="text-center">{{ number_format($totalKategoriRekap['cuti']) }}</td>
                                <td class="text-center">{{ number_format($totalKategoriRekap['tidak_hadir']) }}</td>
                                <td class="text-center pe-3">{{ number_format(array_sum($totalKategoriRekap)) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
