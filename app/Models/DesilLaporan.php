<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesilLaporan extends Model
{
    public const MENUNGGU = 'menunggu';
    public const DISETUJUI = 'disetujui';
    public const DITOLAK = 'ditolak';

    public const SUMBER = [
        'dtsen' => 'DTSEN',
        'dtks' => 'DTKS',
        'musyawarah' => 'Musyawarah desa/kelurahan',
        'survei' => 'Survei lapangan',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = [
        'anak_id', 'desil', 'sumber', 'tanggal_data', 'catatan', 'status',
        'dilaporkan_by', 'ditinjau_by', 'ditinjau_at', 'catatan_review',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_data' => 'date:Y-m-d',
            'ditinjau_at' => 'datetime',
        ];
    }

    public function anak(): BelongsTo
    {
        return $this->belongsTo(Anak::class)->withTrashed();
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dilaporkan_by');
    }

    public function peninjau(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditinjau_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('anak', fn ($anak) => $anak->visibleTo($user));
    }
}
