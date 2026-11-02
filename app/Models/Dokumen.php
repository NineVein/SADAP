<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPanti;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dokumen extends Model
{
    use BelongsToPanti;

    public const JENIS = [
        'sk_pendirian' => 'SK Pendirian',
        'akta_pendirian' => 'Akta Pendirian',
        'akta_notaris' => 'Akta Notaris',
        'izin_operasional' => 'Izin Operasional',
        'akreditasi' => 'Akreditasi Lembaga',
        'npwp' => 'NPWP Lembaga',
        'sk_kemenkumham' => 'SK Kemenkumham',
        'struktur_organisasi' => 'Struktur Organisasi',
        'struktur_pengurus' => 'Struktur Pengurus',
        'foto_gedung' => 'Foto Gedung/Panti',
        'foto_panti' => 'Foto Panti',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = [
        'panti_id',
        'jenis',
        'judul',
        'nomor',
        'berlaku_sampai',
        'path',
        'nama_asli',
        'mime',
        'ukuran',
        'uploaded_by',
    ];

    protected $appends = ['kadaluarsa'];

    protected function casts(): array
    {
        return [
            'berlaku_sampai' => 'date:Y-m-d',
        ];
    }

    public function getKadaluarsaAttribute(): bool
    {
        return $this->berlaku_sampai !== null && $this->berlaku_sampai->isPast();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
