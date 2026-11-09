<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            // Mencegah duplikasi absensi siswa pada sesi & tanggal yang sama,
            // termasuk saat dua proses scan terjadi bersamaan.
            $table->unique(['nis', 'sesi_id', 'tanggal'], 'absensis_nis_sesi_tanggal_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropUnique('absensis_nis_sesi_tanggal_unique');
        });
    }
};
