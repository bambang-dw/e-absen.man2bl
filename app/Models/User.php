<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['siswa_id', 'username', 'password', 'password_changed_at', 'role', 'no_hp', 'name', 'email'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the student associated with the user if role is siswa.
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    /**
     * Get the subjects (Mapel) taught by this user.
     */
    public function mapels(): HasMany
    {
        return $this->hasMany(Mapel::class, 'nama_pengajar', 'id');
    }

    /**
     * Get array of all assigned roles for this user.
     *
     * @return array<int, string>
     */
    public function getRolesAttribute(): array
    {
        if (empty($this->role)) {
            return [];
        }

        $roles = array_values(array_filter(array_map('trim', explode(',', $this->role))));

        // Petugas dilebur ke dalam role guru
        $mapped = array_map(fn ($r) => $r === 'petugas' ? 'guru' : $r, $roles);

        return array_values(array_unique($mapped));
    }

    /**
     * Determine if the user has a specific role or any of the given roles.
     */
    public function hasRole(string|array $role): bool
    {
        $assigned = $this->roles;

        // Guru otomatis memiliki previlage dan hak akses sebagai petugas scanner
        if ($role === 'petugas' && in_array('guru', $assigned, true)) {
            return true;
        }

        if (is_array($role)) {
            if (in_array('petugas', $role, true) && in_array('guru', $assigned, true)) {
                return true;
            }

            return ! empty(array_intersect($role, $assigned));
        }

        return in_array($role, $assigned, true);
    }

    /**
     * Scope a query to only include users with a given role.
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->where(function ($q) use ($role) {
            $q->where('role', $role)
                ->orWhere('role', 'like', "{$role},%")
                ->orWhere('role', 'like', "%,{$role}")
                ->orWhere('role', 'like', "%,{$role},%");

            // Guru otomatis mencakup akun warisan dengan role petugas
            if ($role === 'guru') {
                $q->orWhere('role', 'petugas')
                    ->orWhere('role', 'like', 'petugas,%')
                    ->orWhere('role', 'like', '%,petugas')
                    ->orWhere('role', 'like', '%,petugas,%');
            }
        });
    }

    /**
     * Dapatkan daftar nama kelas binaan yang diampu oleh Wali Kelas ini.
     *
     * @return Collection<int, string>
     */
    public function getDaftarKelasWali(): Collection
    {
        // 1. Pencocokan langsung berdasarkan nama atau username/NIP
        $kelas = Kelas::where('wali_kelas', $this->name)
            ->orWhere('wali_kelas', $this->username)
            ->pluck('kelas');

        if ($kelas->isNotEmpty()) {
            return $kelas->unique()->values();
        }

        // 2. Pencocokan nama bersih sebelum tanda koma (gelar akademik)
        $cleanName = trim(explode(',', (string) $this->name)[0]);
        if (! empty($cleanName)) {
            $kelas = Kelas::where('wali_kelas', 'like', "%{$cleanName}%")->pluck('kelas');
            if ($kelas->isNotEmpty()) {
                return $kelas->unique()->values();
            }
        }

        // 3. Pencocokan kata spesifik dalam nama (panjang >= 4 huruf)
        $words = array_values(array_filter(
            explode(' ', preg_replace('/[^a-zA-Z]/', ' ', $cleanName ?: (string) $this->name)),
            fn ($w) => strlen($w) >= 4 && ! in_array(strtolower($w), ['siti', 'agus', 'muhammad', 'nurul', 'ahmad', 'fitri'], true)
        ));

        foreach ($words as $word) {
            $kelas = Kelas::where('wali_kelas', 'like', "%{$word}%")->pluck('kelas');
            if ($kelas->isNotEmpty()) {
                return $kelas->unique()->values();
            }
        }

        // 4. Fallback kata umum (panjang >= 3 huruf)
        $allWords = array_values(array_filter(
            explode(' ', preg_replace('/[^a-zA-Z]/', ' ', $cleanName ?: (string) $this->name)),
            fn ($w) => strlen($w) >= 3
        ));

        foreach ($allWords as $word) {
            $kelas = Kelas::where('wali_kelas', 'like', "%{$word}%")->pluck('kelas');
            if ($kelas->isNotEmpty()) {
                return $kelas->unique()->values();
            }
        }

        return collect([]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
