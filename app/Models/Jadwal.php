<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tahun_pelajaran',
    'semester',
    'kelas',
    'hari',
    'jam_belajar_id',
    'jam_ke',
    'waktu_mulai',
    'waktu_selesai',
    'mapel_id',
    'mata_pelajaran',
    'nama_pengajar',
])]
class Jadwal extends Model
{
    use HasFactory;

    protected $table = 'jadwals';

    /**
     * Get the class (Kelas) associated with this schedule.
     */
    public function dataKelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas', 'id');
    }

    /**
     * Get the learning period (JamBelajar) associated with this schedule.
     */
    public function jamBelajar(): BelongsTo
    {
        return $this->belongsTo(JamBelajar::class, 'jam_belajar_id', 'id');
    }

    /**
     * Get the subject (Mapel) associated with this schedule.
     */
    public function mapel(): BelongsTo
    {
        return $this->belongsTo(Mapel::class, 'mapel_id', 'id');
    }

    /**
     * Get the teacher (User) associated with this schedule.
     */
    public function pengajar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nama_pengajar', 'id');
    }

    /**
     * Get the subject code (kode_mapel) to display in schedules, falling back to mata_pelajaran.
     */
    public function getTampilMapelAttribute(): string
    {
        if ($this->mapel && ! empty($this->mapel->kode_mapel)) {
            return $this->mapel->kode_mapel;
        }

        if (! empty($this->mata_pelajaran)) {
            $matched = Mapel::where('mata_pelajaran', $this->mata_pelajaran)
                ->orWhere('kode_mapel', $this->mata_pelajaran)
                ->first();
            if ($matched && ! empty($matched->kode_mapel)) {
                return $matched->kode_mapel;
            }
        }

        return (string) $this->mata_pelajaran;
    }
}
