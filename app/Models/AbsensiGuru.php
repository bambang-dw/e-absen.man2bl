<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'kelas',
    'hari',
    'tanggal',
    'nama_guru',
    'guru_id',
    'jam_ke',
    'mata_pelajaran',
    'materi_pembelajaran',
    'foto',
    'status',
    'keterangan',
])]
class AbsensiGuru extends Model
{
    use HasFactory;

    protected $table = 'absensi_gurus';

    /**
     * Get the class (Kelas) associated with this teacher attendance record.
     */
    public function dataKelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas', 'id');
    }

    /**
     * Get the teacher user if nama_guru stores a user ID.
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nama_guru', 'id');
    }

    /**
     * Get the displayable teacher name.
     */
    public function getNamaGuruTampilAttribute(): string
    {
        if (is_numeric($this->nama_guru) && $this->guru) {
            return $this->guru->name ?: $this->guru->username;
        }

        return (string) $this->nama_guru;
    }

    /**
     * Get the public URL of the class photo, including photos stored before the public folder change.
     */
    public function getFotoUrlAttribute(): ?string
    {
        if (! $this->foto) {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $this->foto), '/');

        return str_starts_with($path, 'absensi-guru/')
            ? asset('storage/'.$path)
            : asset('foto-guru/'.$path);
    }

    /**
     * Check whether this attendance record covers a specific jam_ke (supports '1', '1,2,3', or '1-3').
     */
    public function matchesJamKe(string|int $targetJamKe): bool
    {
        $target = trim((string) $targetJamKe);
        $raw = str_ireplace('jam ke-', '', (string) $this->jam_ke);
        $parts = array_map('trim', explode(',', $raw));

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if (str_contains($part, '-')) {
                [$start, $end] = array_map('trim', explode('-', $part, 2));
                if (is_numeric($start) && is_numeric($end) && (int) $start <= (int) $end) {
                    foreach (range((int) $start, (int) $end) as $num) {
                        if ((string) $num === $target) {
                            return true;
                        }
                    }
                }
            } elseif ($part === $target) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether this attendance record includes an assignment (Tugas).
     */
    public function hasTugas(): bool
    {
        return str_contains(strtolower((string) $this->status), 'tugas');
    }

    /**
     * Determine whether this attendance record indicates the teacher is present (Hadir).
     */
    public function isHadir(): bool
    {
        $lower = strtolower((string) $this->status);

        return str_contains($lower, 'hadir') || str_contains($lower, 'tepat waktu');
    }

    /**
     * Get the teacher user ID this record belongs to (supports legacy rows storing a name/username).
     */
    public function getGuruUserIdAttribute(): ?int
    {
        if (! empty($this->guru_id)) {
            return (int) $this->guru_id;
        }

        $raw = (string) $this->nama_guru;
        if ($raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            return (int) $raw;
        }

        return User::where('name', $raw)->orWhere('username', $raw)->value('id');
    }

    /**
     * Expand a jam_ke value into individual numbers (supports '1', '1,2,3', or '1-3').
     *
     * @return array<int, int>
     */
    public static function expandJamKe(string|int|array|null $jamKe): array
    {
        $items = is_array($jamKe) ? $jamKe : explode(',', (string) $jamKe);
        $result = [];

        foreach ($items as $item) {
            $part = trim(str_ireplace('jam ke-', '', (string) $item));
            if ($part === '') {
                continue;
            }

            if (str_contains($part, '-')) {
                [$start, $end] = array_map('trim', explode('-', $part, 2));
                if (is_numeric($start) && is_numeric($end) && (int) $start <= (int) $end) {
                    $result = array_merge($result, range((int) $start, (int) $end));
                }

                continue;
            }

            if (is_numeric($part)) {
                $result[] = (int) $part;
            }
        }

        $result = array_values(array_unique($result));
        sort($result);

        return $result;
    }

    /**
     * Format jam numbers into a normalized string, e.g. [2, 1] -> '1,2'.
     *
     * @param  array<int, int|string>|string|int|null  $jamKe
     */
    public static function formatJamKe(array|string|int|null $jamKe): string
    {
        return implode(',', self::expandJamKe($jamKe));
    }

    /**
     * Check whether a teacher already recorded the same class on any of the given jam numbers.
     */
    public static function findJamBentrok(string $tanggal, ?int $guruId, ?int $kelasId, array|string|int|null $jamKe): ?self
    {
        $targetJams = self::expandJamKe($jamKe);
        if (empty($targetJams) || empty($guruId)) {
            return null;
        }

        // Tanpa kelas (pencatatan admin), bentrok dinilai dari guru + jam pada tanggal yang sama
        $cocok = function (self $r) use ($guruId, $kelasId): bool {
            return $r->guruUserId === $guruId && (empty($kelasId) || (int) $r->kelas === (int) $kelasId);
        };

        $existing = self::with('dataKelas')
            ->whereDate('tanggal', $tanggal)
            ->where('guru_id', $guruId)
            ->when($kelasId, fn ($q) => $q->where('kelas', $kelasId))
            ->get();

        // Lapis kedua: data lama yang guru_id-nya belum terisi
        $records = $existing->isNotEmpty()
            ? $existing
            : self::with('dataKelas')->whereDate('tanggal', $tanggal)
                ->when($kelasId, fn ($q) => $q->where('kelas', $kelasId))
                ->get()
                ->filter($cocok);

        return $records->first(fn (self $r) => ! empty(array_intersect($targetJams, self::expandJamKe($r->jam_ke))));
    }

    /**
     * Normalize single or multi-select status input (e.g., ['Izin', 'Tugas'] -> 'Izin, Tugas').
     */
    public static function normalizeStatus(array|string|null $statusInput): string
    {
        if (is_array($statusInput)) {
            $items = array_values(array_unique(array_filter(array_map('trim', $statusInput), fn ($v) => $v !== '')));

            return ! empty($items) ? implode(', ', $items) : 'Hadir';
        }

        $str = trim((string) $statusInput);

        return $str !== '' ? $str : 'Hadir';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }
}
