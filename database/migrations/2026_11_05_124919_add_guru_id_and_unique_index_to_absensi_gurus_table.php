<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('absensi_gurus', function (Blueprint $table) {
            $table->foreignId('guru_id')
                ->nullable()
                ->after('nama_guru')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Isi guru_id dari nama_guru lama (yang menyimpan ID user atau nama/username guru)
        foreach (DB::table('absensi_gurus')->whereNull('guru_id')->get(['id', 'nama_guru']) as $row) {
            $userId = is_numeric($row->nama_guru)
                ? DB::table('users')->where('id', (int) $row->nama_guru)->value('id')
                : DB::table('users')->where('name', $row->nama_guru)->orWhere('username', $row->nama_guru)->value('id');

            if ($userId) {
                DB::table('absensi_gurus')->where('id', $row->id)->update(['guru_id' => $userId]);
            }
        }

        // Hapus duplikat lama, sisakan record pertama pada setiap kombinasi tanggal + guru + kelas + jam_ke
        $duplikat = DB::table('absensi_gurus')
            ->select('tanggal', 'guru_id', 'kelas', 'jam_ke', DB::raw('MIN(id) as id_pertama'))
            ->whereNotNull('guru_id')
            ->whereNotNull('kelas')
            ->groupBy('tanggal', 'guru_id', 'kelas', 'jam_ke')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplikat as $grup) {
            DB::table('absensi_gurus')
                ->where('tanggal', $grup->tanggal)
                ->where('guru_id', $grup->guru_id)
                ->where('kelas', $grup->kelas)
                ->where('jam_ke', $grup->jam_ke)
                ->where('id', '!=', $grup->id_pertama)
                ->delete();
        }

        // Kunci di level database: satu guru tidak bisa mencatat kelas & jam yang sama dua kali pada tanggal yang sama
        Schema::table('absensi_gurus', function (Blueprint $table) {
            $table->unique(['tanggal', 'guru_id', 'kelas', 'jam_ke'], 'absensi_gurus_unik_per_jam');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensi_gurus', function (Blueprint $table) {
            $table->dropUnique('absensi_gurus_unik_per_jam');
            $table->dropConstrainedForeignId('guru_id');
        });
    }
};
