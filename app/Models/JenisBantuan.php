<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisBantuan extends Model
{
    protected $table = 'jenis_bantuans';

    protected $fillable = [
        'kode',
        'nama',
        'kategori',
        'deskripsi',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function pantis(): BelongsToMany
    {
        return $this->belongsToMany(Panti::class, 'panti_jenis_bantuan')
            ->withPivot('catatan')
            ->withTimestamps();
    }

    public function riwayatBantuan(): HasMany
    {
        return $this->hasMany(RiwayatBantuan::class);
    }
}

