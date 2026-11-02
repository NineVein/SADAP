<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatBantuan extends Model
{
    protected $table = 'riwayat_bantuans';

    protected $fillable = [
        'panti_id',
        'jenis_bantuan_id',
        'sumber',
        'nilai',
        'jumlah',
        'tanggal',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
            'nilai' => 'float',
            'jumlah' => 'integer',
        ];
    }

    public function panti(): BelongsTo
    {
        return $this->belongsTo(Panti::class);
    }

    public function jenisBantuan(): BelongsTo
    {
        return $this->belongsTo(JenisBantuan::class);
    }
}

