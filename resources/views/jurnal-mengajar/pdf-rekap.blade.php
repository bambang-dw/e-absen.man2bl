<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Absen Guru</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1e293b;
            font-size: 10px;
        }

        h1 {
            margin: 0 0 6px;
            font-size: 18px;
        }

        p {
            margin: 0 0 12px;
            color: #475569;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #cbd5e1;
            padding: 6px;
        }

        th {
            background: #1e3a8a;
            color: #ffffff;
            text-align: center;
        }

        td.number {
            text-align: center;
        }

        tfoot {
            background: #e2e8f0;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h1>Rekapitulasi Absen Guru</h1>
    <p>Periode: {{ $filterPeriodeRekap['nama_periode'] }} | Dicetak: {{ $tanggalCetak }} WIB</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Guru</th>
                <th>Hadir</th>
                <th>Izin dengan Tugas</th>
                <th>Sakit dengan Tugas</th>
                <th>Cuti</th>
                <th>Tidak Hadir</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rekapAbsensiGuru as $index => $rekapGuru)
                <tr>
                    <td class="number">{{ $index + 1 }}</td>
                    <td>{{ $rekapGuru->nama_guru }}</td>
                    <td class="number">{{ $rekapGuru->hadir }}</td>
                    <td class="number">{{ $rekapGuru->izin_tugas }}</td>
                    <td class="number">{{ $rekapGuru->sakit_tugas }}</td>
                    <td class="number">{{ $rekapGuru->cuti }}</td>
                    <td class="number">{{ $rekapGuru->tidak_hadir }}</td>
                    <td class="number">
                        {{ (int) $rekapGuru->hadir + (int) $rekapGuru->izin_tugas + (int) $rekapGuru->sakit_tugas + (int) $rekapGuru->cuti + (int) $rekapGuru->tidak_hadir }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="number">Belum ada data absensi guru pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total</td>
                <td class="number">{{ $totalKategoriRekap['hadir'] }}</td>
                <td class="number">{{ $totalKategoriRekap['izin_tugas'] }}</td>
                <td class="number">{{ $totalKategoriRekap['sakit_tugas'] }}</td>
                <td class="number">{{ $totalKategoriRekap['cuti'] }}</td>
                <td class="number">{{ $totalKategoriRekap['tidak_hadir'] }}</td>
                <td class="number">{{ array_sum($totalKategoriRekap) }}</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>
