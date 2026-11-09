<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

#[Fillable(['kode_sesi', 'nama_sesi', 'jam_mulai', 'jam_terlambat', 'jam_selesai', 'is_aktif'])]
class SesiAbsensi extends Model
{
    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class, 'sesi_id');
    }

    /**
     * Dapatkan sesi yang sedang aktif saat ini.
     * Aturan:
     * - Jika ada instruksi "Pulang Lebih Awal" hari ini, sesi Pulang langsung diaktifkan.
     * - Jika hari ini hari libur (Sabtu, Minggu, atau kalender libur), seluruh sesi tidak aktif (return null).
     * - Sesi hanya dianggap aktif jika waktu saat ini berada dalam rentang jam_mulai s.d jam_selesai.
     * - Jika suatu sesi telah berakhir dan sesi berikutnya belum tercapai, mengembalikan null (mute).
     */
    public static function getSesiAktif(?string $currentTime = null, ?Carbon $date = null): ?self
    {
        $dt = $date ?? Carbon::now();
        $todayStr = $dt->toDateString();

        // 1. Cek apakah ada status "Pulang Lebih Awal" aktif hari ini
        $pulangAwal = Cache::get('pulang_awal_'.$todayStr);
        if ($pulangAwal && ! empty($pulangAwal['aktif'])) {
            $sesiPulang = self::where('is_aktif', true)->find($pulangAwal['sesi_id'])
                ?? self::where('is_aktif', true)->where('nama_sesi', 'like', '%pulang%')->first()
                ?? self::where('is_aktif', true)->orderBy('jam_mulai', 'desc')->first();

            if ($sesiPulang) {
                return $sesiPulang;
            }
        }

        // 2. Cek hari libur (Sabtu - 5 hari belajar, Minggu, atau kalender libur khusus)
        if (HariLibur::isHariLibur($dt)) {
            return null;
        }

        $time = Carbon::parse($currentTime ?? $dt->toTimeString())->format('H:i:s');

        // 3. Ambil seluruh sesi aktif lalu bandingkan rentang waktu
        return self::where('is_aktif', true)
            ->orderBy('jam_mulai')
            ->orderBy('id')
            ->get()
            ->first(function (self $sesi) use ($time) {
                $mulai = Carbon::parse($sesi->jam_mulai)->format('H:i:s');
                $selesai = Carbon::parse($sesi->jam_selesai)->format('H:i:s');

                return $time >= $mulai && $time <= $selesai;
            });
    }

    /**
     * Apakah ada sesi yang sedang berlangsung pada waktu tertentu.
     */
    public static function adaSesiAktif(?string $currentTime = null, ?Carbon $date = null): bool
    {
        return self::getSesiAktif($currentTime, $date) !== null;
    }

    /**
     * Dapatkan daftar ID sesi yang aktif (termasuk sesi paralel pada rentang waktu yang sama).
     *
     * @return int[]
     */
    public static function getActiveSesiIds(?self $sesiAktif = null, ?string $currentTime = null, ?Carbon $date = null): array
    {
        $sesiAktif = $sesiAktif ?? self::getSesiAktif($currentTime, $date);
        if (! $sesiAktif) {
            return [];
        }

        return self::where('is_aktif', true)
            ->where('jam_mulai', $sesiAktif->jam_mulai)
            ->where('jam_selesai', $sesiAktif->jam_selesai)
            ->pluck('id')
            ->toArray();
    }
}
