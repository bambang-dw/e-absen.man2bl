<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['jam_ke', 'waktu_mulai', 'waktu_selesai'])]
class JamBelajar extends Model
{
    use HasFactory;

    protected $table = 'jam_belajars';
}
