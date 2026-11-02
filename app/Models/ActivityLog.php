<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'aksi', 'deskripsi', 'subject_type', 'subject_id', 'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Catat satu aktivitas ke jejak audit. */
    public static function record(string $aksi, string $deskripsi, ?Model $subject = null, ?int $userId = null): void
    {
        static::create([
            'user_id' => $userId ?? Auth::id(),
            'aksi' => $aksi,
            'deskripsi' => Str::limit($deskripsi, 490),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => request()?->ip(),
        ]);
    }
}
