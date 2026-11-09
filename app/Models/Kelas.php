<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tahun_pelajaran', 'kelas', 'wali_kelas'])]
class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelases';

    /**
     * Relationship with Siswa in this class.
     */
    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas', 'kelas');
    }

    /**
     * Relationship with Mapel in this class.
     */
    public function mapels(): HasMany
    {
        return $this->hasMany(Mapel::class, 'kelas', 'id');
    }
}
