<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('absensis', 'absensi_siswas');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('absensi_siswas', 'absensis');
    }
};
