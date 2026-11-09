<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tanggal', 'keterangan', 'created_by'])]
class HariLibur extends Model
{
    protected $casts = [
        'tanggal' => 'date',
    ];

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Cek apakah tanggal yang diberikan merupakan hari libur (Sabtu, Minggu, atau terdaftar di database).
     *
     * @return array{is_libur: bool, keterangan: ?string, tipe: string}
     */
    public static function statusLibur(?Carbon $date = null): array
    {
        $dt = $date ?? Carbon::now();
        $tglStr = $dt->toDateString();

        // 1. Cek Hari Sabtu (sekolah 5 hari, Sabtu tidak belajar)
        if ($dt->isSaturday()) {
            return [
                'is_libur' => true,
                'keterangan' => 'Libur Akhir Pekan (Sabtu - 5 Hari Belajar)',
                'tipe' => 'weekend_sabtu',
            ];
        }

        // 2. Cek Hari Minggu
        if ($dt->isSunday()) {
            return [
                'is_libur' => true,
                'keterangan' => 'Libur Akhir Pekan (Minggu)',
                'tipe' => 'weekend_minggu',
            ];
        }

        // 3. Cek apakah tanggal ini terdaftar di tabel hari_liburs
        $liburDb = self::where('tanggal', $tglStr)->first();
        if ($liburDb) {
            return [
                'is_libur' => true,
                'keterangan' => $liburDb->keterangan,
                'tipe' => 'holiday_event',
            ];
        }

        return [
            'is_libur' => false,
            'keterangan' => null,
            'tipe' => 'hari_sekolah',
        ];
    }

    /**
     * Cek apakah hari ini atau tanggal tertentu libur.
     */
    public static function isHariLibur(?Carbon $date = null): bool
    {
        return self::statusLibur($date)['is_libur'];
    }
}
