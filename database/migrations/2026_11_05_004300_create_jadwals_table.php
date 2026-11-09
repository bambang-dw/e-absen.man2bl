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
        Schema::create('jadwals', function (Blueprint $table) {
            $table->id();
            $table->string('tahun_pelajaran', 20);
            $table->string('semester', 20);
            $table->foreignId('kelas')->constrained('kelases')->cascadeOnDelete();
            $table->string('hari', 20);
            $table->foreignId('jam_belajar_id')->nullable()->constrained('jam_belajars')->nullOnDelete();
            $table->string('jam_ke', 50);
            $table->time('waktu_mulai');
            $table->time('waktu_selesai');
            $table->foreignId('mapel_id')->nullable()->constrained('mapels')->nullOnDelete();
            $table->string('mata_pelajaran', 150);
            $table->foreignId('nama_pengajar')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwals');
    }
};
