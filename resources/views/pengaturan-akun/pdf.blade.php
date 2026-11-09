<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Data Akun Pengguna - e-absen MAN 2 Balam</title>
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
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .school-title {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
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
            color: #1e1b4b;
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
            background-color: #1e1b4b;
            color: #ffffff;
            font-weight: bold;
            padding: 8px 6px;
            text-align: center;
            border: 1px solid #1e1b4b;
            font-size: 10px;
            text-transform: uppercase;
        }
        td {
            padding: 7px 6px;
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
            text-transform: uppercase;
        }
        .badge-admin { background-color: #e0e7ff; color: #3730a3; }
        .badge-petugas { background-color: #d1fae5; color: #065f46; }
        .badge-guru { background-color: #e0f2fe; color: #0369a1; }
        .badge-kepsek { background-color: #fef3c7; color: #92400e; }
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
        <h2 class="school-title">E-ABSENSI PRESENSI DIGITAL</h2>
        <p class="school-subtitle">Sistem Manajemen Presensi Siswa & Otoritas Akun Pengguna</p>
    </div>

    <div class="doc-title">Laporan Data Akun Pengguna Sistem</div>

    <div class="meta-info">
        <table style="border: none; margin-bottom: 5px;">
            <tr style="background: none;">
                <td style="border: none; padding: 2px 0;"><strong>Dicetak Pada:</strong> {{ $date }}</td>
                <td style="border: none; padding: 2px 0; text-align: right;"><strong>Total Akun:</strong> {{ $users->count() }} Pengguna</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 130px;">Username</th>
                <th>Nama Lengkap</th>
                <th style="width: 90px;">Role</th>
                <th style="width: 110px;">No HP</th>
                <th style="width: 110px;">Tanggal Dibuat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $idx => $u)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-left"><strong>{{ $u->username ?: '-' }}</strong></td>
                    <td class="text-left">{{ $u->name ?: '-' }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $u->role ?: 'admin' }}">
                            {{ strtoupper($u->role ?: 'ADMIN') }}
                        </span>
                    </td>
                    <td class="text-center">{{ $u->no_hp ?: '-' }}</td>
                    <td class="text-center">{{ $u->created_at ? $u->created_at->format('d/m/Y') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer-sig">
        <div class="sig-block">
            <div>Mengetahui,</div>
            <div style="font-weight: bold; margin-bottom: 45px;">Administrator Sistem</div>
            <div class="sig-line"></div>
            <div style="font-size: 10px; margin-top: 4px;">NIP / ID Petugas</div>
        </div>
        <div style="clear: both;"></div>
    </div>

</body>
</html>
