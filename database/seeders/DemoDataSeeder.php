<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $students = [
            ['nis' => '20260001', 'nama' => 'Ahmad Fauzan', 'kelas' => 'X-A', 'no_hp_ortu' => '081200000001'],
            ['nis' => '20260002', 'nama' => 'Siti Nurhaliza', 'kelas' => 'X-A', 'no_hp_ortu' => '081200000002'],
            ['nis' => '20260003', 'nama' => 'Budi Santoso', 'kelas' => 'X-A', 'no_hp_ortu' => '081200000003'],
            ['nis' => '20260004', 'nama' => 'Dewi Anggraini', 'kelas' => 'X-A', 'no_hp_ortu' => '081200000004'],
            ['nis' => '20260005', 'nama' => 'Rizky Pratama', 'kelas' => 'X-A', 'no_hp_ortu' => '081200000005'],
            ['nis' => '20260006', 'nama' => 'Putri Maharani', 'kelas' => 'X-B', 'no_hp_ortu' => '081200000006'],
            ['nis' => '20260007', 'nama' => 'Andi Wijaya', 'kelas' => 'X-B', 'no_hp_ortu' => '081200000007'],
            ['nis' => '20260008', 'nama' => 'Rina Lestari', 'kelas' => 'X-B', 'no_hp_ortu' => '081200000008'],
            ['nis' => '20260009', 'nama' => 'Fajar Hidayat', 'kelas' => 'X-B', 'no_hp_ortu' => '081200000009'],
            ['nis' => '20260010', 'nama' => 'Intan Permata', 'kelas' => 'X-B', 'no_hp_ortu' => '081200000010'],
            ['nis' => '20260011', 'nama' => 'Dimas Saputra', 'kelas' => 'XI-A', 'no_hp_ortu' => '081200000011'],
            ['nis' => '20260012', 'nama' => 'Nur Aisyah', 'kelas' => 'XI-A', 'no_hp_ortu' => '081200000012'],
            ['nis' => '20260013', 'nama' => 'Agus Setiawan', 'kelas' => 'XI-A', 'no_hp_ortu' => '081200000013'],
            ['nis' => '20260014', 'nama' => 'Maya Sari', 'kelas' => 'XI-A', 'no_hp_ortu' => '081200000014'],
            ['nis' => '20260015', 'nama' => 'Rendra Kurniawan', 'kelas' => 'XI-A', 'no_hp_ortu' => '081200000015'],
            ['nis' => '20260016', 'nama' => 'Lilis Kartika', 'kelas' => 'XI-B', 'no_hp_ortu' => '081200000016'],
            ['nis' => '20260017', 'nama' => 'Eko Prasetyo', 'kelas' => 'XI-B', 'no_hp_ortu' => '081200000017'],
            ['nis' => '20260018', 'nama' => 'Vina Oktaviani', 'kelas' => 'XI-B', 'no_hp_ortu' => '081200000018'],
            ['nis' => '20260019', 'nama' => 'Yoga Firmansyah', 'kelas' => 'XI-B', 'no_hp_ortu' => '081200000019'],
            ['nis' => '20260020', 'nama' => 'Nabila Ramadhani', 'kelas' => 'XI-B', 'no_hp_ortu' => '081200000020'],
        ];

        DB::table('siswas')->upsert(
            array_map(fn (array $student): array => [
                ...$student,
                'created_at' => $now,
                'updated_at' => $now,
            ], $students),
            ['nis'],
            ['nama', 'kelas', 'no_hp_ortu', 'updated_at'],
        );

        $sessions = [
            ['kode_sesi' => 'demo-01', 'nama_sesi' => 'Kedatangan Pagi', 'jam_mulai' => '06:30:00', 'jam_terlambat' => '07:00:00', 'jam_selesai' => '07:15:00'],
            ['kode_sesi' => 'demo-02', 'nama_sesi' => 'Apel Pagi', 'jam_mulai' => '07:15:00', 'jam_terlambat' => '07:30:00', 'jam_selesai' => '07:45:00'],
            ['kode_sesi' => 'demo-03', 'nama_sesi' => 'Literasi Pagi', 'jam_mulai' => '07:45:00', 'jam_terlambat' => '08:00:00', 'jam_selesai' => '08:15:00'],
            ['kode_sesi' => 'demo-04', 'nama_sesi' => 'Pelajaran Pertama', 'jam_mulai' => '08:15:00', 'jam_terlambat' => '08:30:00', 'jam_selesai' => '09:00:00'],
            ['kode_sesi' => 'demo-05', 'nama_sesi' => 'Pelajaran Kedua', 'jam_mulai' => '09:00:00', 'jam_terlambat' => '09:15:00', 'jam_selesai' => '09:45:00'],
            ['kode_sesi' => 'demo-06', 'nama_sesi' => 'Istirahat Pagi', 'jam_mulai' => '09:45:00', 'jam_terlambat' => '10:00:00', 'jam_selesai' => '10:15:00'],
            ['kode_sesi' => 'demo-07', 'nama_sesi' => 'Pelajaran Ketiga', 'jam_mulai' => '10:15:00', 'jam_terlambat' => '10:30:00', 'jam_selesai' => '11:00:00'],
            ['kode_sesi' => 'demo-08', 'nama_sesi' => 'Pelajaran Keempat', 'jam_mulai' => '11:00:00', 'jam_terlambat' => '11:15:00', 'jam_selesai' => '11:45:00'],
            ['kode_sesi' => 'demo-09', 'nama_sesi' => 'Istirahat Siang', 'jam_mulai' => '11:45:00', 'jam_terlambat' => '12:00:00', 'jam_selesai' => '12:15:00'],
            ['kode_sesi' => 'demo-10', 'nama_sesi' => 'Pelajaran Kelima', 'jam_mulai' => '12:15:00', 'jam_terlambat' => '12:30:00', 'jam_selesai' => '13:00:00'],
            ['kode_sesi' => 'demo-11', 'nama_sesi' => 'Pelajaran Keenam', 'jam_mulai' => '13:00:00', 'jam_terlambat' => '13:15:00', 'jam_selesai' => '13:45:00'],
            ['kode_sesi' => 'demo-12', 'nama_sesi' => 'Pelajaran Ketujuh', 'jam_mulai' => '13:45:00', 'jam_terlambat' => '14:00:00', 'jam_selesai' => '14:30:00'],
            ['kode_sesi' => 'demo-13', 'nama_sesi' => 'Kegiatan Ekstrakurikuler', 'jam_mulai' => '14:30:00', 'jam_terlambat' => '14:45:00', 'jam_selesai' => '15:15:00'],
            ['kode_sesi' => 'demo-14', 'nama_sesi' => 'Kepulangan Siang', 'jam_mulai' => '15:15:00', 'jam_terlambat' => '15:30:00', 'jam_selesai' => '15:45:00'],
            ['kode_sesi' => 'demo-15', 'nama_sesi' => 'Kedatangan Sore', 'jam_mulai' => '12:30:00', 'jam_terlambat' => '12:45:00', 'jam_selesai' => '13:00:00'],
            ['kode_sesi' => 'demo-16', 'nama_sesi' => 'Literasi Sore', 'jam_mulai' => '13:00:00', 'jam_terlambat' => '13:15:00', 'jam_selesai' => '13:30:00'],
            ['kode_sesi' => 'demo-17', 'nama_sesi' => 'Pelajaran Sore Pertama', 'jam_mulai' => '13:30:00', 'jam_terlambat' => '13:45:00', 'jam_selesai' => '14:15:00'],
            ['kode_sesi' => 'demo-18', 'nama_sesi' => 'Pelajaran Sore Kedua', 'jam_mulai' => '14:15:00', 'jam_terlambat' => '14:30:00', 'jam_selesai' => '15:00:00'],
            ['kode_sesi' => 'demo-19', 'nama_sesi' => 'Pelajaran Sore Ketiga', 'jam_mulai' => '15:00:00', 'jam_terlambat' => '15:15:00', 'jam_selesai' => '15:45:00'],
            ['kode_sesi' => 'demo-20', 'nama_sesi' => 'Kepulangan Sore', 'jam_mulai' => '15:45:00', 'jam_terlambat' => '16:00:00', 'jam_selesai' => '16:15:00'],
        ];

        foreach ($sessions as $session) {
            DB::table('sesi_absensis')->updateOrInsert(
                ['kode_sesi' => $session['kode_sesi']],
                [
                    ...$session,
                    'is_aktif' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
}
