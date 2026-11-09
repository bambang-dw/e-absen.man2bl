<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Data Akun Siswa - e-absen MAN 2 Balam</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 15px;
            font-size: 11px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #064e3b;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .school-title {
            font-size: 16px;
            font-weight: bold;
            color: #064e3b;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .school-subtitle {
            font-size: 11px;
            color: #475569;
            margin: 3px 0 0 0;
        }
        .doc-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin: 15px 0 10px;
            text-transform: uppercase;
            color: #064e3b;
        }
        .meta-info {
            margin-bottom: 15px;
            font-size: 10px;
            color: #475569;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #064e3b;
            color: #ffffff;
            font-weight: bold;
            padding: 8px 6px;
            text-align: center;
            border: 1px solid #064e3b;
            font-size: 10px;
            text-transform: uppercase;
        }
        td {
            padding: 6px 6px;
            border: 1px solid #cbd5e1;
            font-size: 10px;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-default { background-color: #e2e8f0; color: #475569; }
        .badge-kustom { background-color: #d1fae5; color: #065f46; }
        .footer-sig {
            margin-top: 30px;
            width: 100%;
        }
        .sig-block {
            float: right;
            width: 200px;
            text-align: center;
        }
        .sig-line {
            margin-top: 50px;
            border-bottom: 1px solid #000;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2 class="school-title">MAN 2 BANDAR LAMPUNG</h2>
        <p class="school-subtitle">Daftar Akun Siswa & Otoritas Login Presensi Digital</p>
    </div>

    <div class="doc-title">
        LAPORAN DATA AKUN LOGIN SISWA
    </div>

    <div class="meta-info">
        <table style="width: 100%; margin-bottom: 0; border: none;">
            <tr style="background: transparent;">
                <td style="border: none; padding: 0; width: 50%;">
                    <strong>Waktu Cetak:</strong> {{ $date }}<br>
                    <strong>Total Siswa:</strong> {{ count($siswas) }} Siswa
                </td>
                <td style="border: none; padding: 0; text-align: right; width: 50%;">
                    <strong>Ketentuan Login:</strong> Username & Password Awal adalah nomor NISN
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">NO</th>
                <th style="width: 90px;">NISN (USERNAME)</th>
                <th style="width: 75px;">NIS</th>
                <th>NAMA SISWA</th>
                <th style="width: 70px;">KELAS</th>
                <th style="width: 110px;">STATUS PASSWORD</th>
                <th style="width: 90px;">NO HP ORTU</th>
            </tr>
        </thead>
        <tbody>
            @forelse($siswas as $idx => $s)
                @php
                    $isKustom = $s->user && $s->user->password_changed_at;
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center" style="font-family: monospace;">{{ $s->nisn ?: '-' }}</td>
                    <td class="text-center" style="font-family: monospace;">{{ $s->nis ?: '-' }}</td>
                    <td>{{ $s->nama }}</td>
                    <td class="text-center">{{ $s->kelas }}</td>
                    <td class="text-center">
                        @if($isKustom)
                            <span class="badge badge-kustom">Kustom (Diubah)</span>
                        @else
                            <span class="badge badge-default">Default (NISN)</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $s->no_hp_ortu ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 15px; color: #64748b;">
                        Tidak ada data akun siswa yang ditemukan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-sig">
        <div class="sig-block">
            <p style="margin: 0;">Bandar Lampung, {{ date('d F Y') }}</p>
            <p style="margin: 3px 0 0 0;">Administrator E-Absen,</p>
            <div class="sig-line"></div>
            <p style="margin: 4px 0 0 0; font-weight: bold;">( Petugas IT Madrasah )</p>
        </div>
        <div style="clear: both;"></div>
    </div>

</body>
</html>
