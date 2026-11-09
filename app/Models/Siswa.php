<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

#[Fillable(['nis', 'nisn', 'nama', 'kelas', 'no_hp_siswa', 'no_hp_ortu', 'foto'])]
class Siswa extends Model
{
    /**
     * Get all absensis for the siswa.
     */
    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class, 'nis', 'nis');
    }

    /**
     * Get the user account for this student.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'siswa_id');
    }

    /**
     * Get the student photo URL or avatar fallback.
     */
    public function getFotoUrlAttribute(): string
    {
        if ($this->foto) {
            $normalizedPath = ltrim(str_replace('\\', '/', $this->foto), '/');

            if (file_exists(public_path($normalizedPath))) {
                $mtime = @filemtime(public_path($normalizedPath)) ?: '';

                return asset($normalizedPath).($mtime ? '?v='.$mtime : '');
            }

            if (file_exists(public_path('storage/'.$normalizedPath))) {
                $mtime = @filemtime(public_path('storage/'.$normalizedPath)) ?: '';

                return asset('storage/'.$normalizedPath).($mtime ? '?v='.$mtime : '');
            }

            if (Storage::disk('public')->exists($normalizedPath)) {
                return asset('storage/'.$normalizedPath);
            }
        }

        return 'https://ui-avatars.com/api/?name='.urlencode((string) $this->nama).'&background=059669&color=ffffff&bold=true&size=128';
    }

    /**
     * Delete existing student photo files from public and storage directories.
     */
    public function deleteExistingFoto(): void
    {
        if (! $this->foto) {
            return;
        }

        $normalizedPath = ltrim(str_replace('\\', '/', $this->foto), '/');

        if (file_exists(public_path($normalizedPath)) && is_file(public_path($normalizedPath))) {
            @unlink(public_path($normalizedPath));
        }

        if (Storage::disk('public')->exists($normalizedPath)) {
            Storage::disk('public')->delete($normalizedPath);
        }
    }
}
