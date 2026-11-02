<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPanti;
use Illuminate\Database\Eloquent\Model;

class Pengurus extends Model
{
    use BelongsToPanti;

    protected $table = 'pengurus';

    protected $fillable = [
        'panti_id',
        'nama',
        'jabatan',
        'telepon',
        'email',
        'alamat',
        'periode_mulai',
        'periode_selesai',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'periode_mulai' => 'date:Y-m-d',
            'periode_selesai' => 'date:Y-m-d',
        ];
    }
}
