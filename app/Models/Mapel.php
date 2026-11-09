<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kode_mapel', 'kelas', 'mata_pelajaran', 'nama_pengajar'])]
class Mapel extends Model
{
    use HasFactory;

    protected $table = 'mapels';

    /**
     * Get the class (Kelas) associated with this subject.
     */
    public function dataKelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas', 'id');
    }

    /**
     * Get the teacher (User) associated with this subject.
     */
    public function pengajar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nama_pengajar', 'id');
    }
}
