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
        Schema::create('absensi_gurus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas')->nullable()->constrained('kelases')->nullOnDelete();
            $table->string('hari', 20);
            $table->date('tanggal');
            $table->string('nama_guru', 150);
            $table->string('jam_ke', 50);
            $table->string('mata_pelajaran', 150);
            $table->text('materi_pembelajaran')->nullable();
            $table->string('status', 50)->default('Hadir');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi_gurus');
    }
};
