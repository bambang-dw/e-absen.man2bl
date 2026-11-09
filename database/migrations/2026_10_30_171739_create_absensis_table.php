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
        Schema::create(
            'absensis',
            function (Blueprint $table) {
                $table->id();
                $table->string('nis', 20);
                $table->foreignId('sesi_id')->constrained('sesi_absensis')->onDelete('cascade');
                $table->date('tanggal');
                $table->time('jam_scan');
                $table->enum('status', ['Tepat Waktu', 'Terlambat', 'Izin', 'Sakit', 'Alfa']);
                $table->text('keterangan')->nullable();
                $table->timestamps();

                $table->foreign('nis')->references('nis')->on('siswas')->onDelete('cascade');
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};
