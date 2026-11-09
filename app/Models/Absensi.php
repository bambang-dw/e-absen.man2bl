<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;

#[Fillable(['nis', 'sesi_id', 'user_id', 'tanggal', 'jam_scan', 'status', 'keterangan'])]
class Absensi extends Model
{
    protected $table = 'absensi_siswas';

    /**
     * Tentukan apakah QueryException terjadi akibat pelanggaran unique constraint absensi.
     */
    public static function isDuplicateEntry(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true)
            || ($exception->errorInfo[1] ?? null) === 1062;
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'nis', 'nis');
    }

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiAbsensi::class, 'sesi_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
