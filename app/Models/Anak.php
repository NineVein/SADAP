<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPanti;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Anak extends Model
{
    use BelongsToPanti, SoftDeletes;

    public const KATEGORI = [
        'yatim' => 'Yatim',
        'piatu' => 'Piatu',
        'yatim_piatu' => 'Yatim piatu',
        'dhuafa' => 'Dhuafa',
        'terlantar' => 'Terlantar',
    ];

    public const PENDIDIKAN = [
        'belum_sekolah' => 'Belum sekolah',
        'paud' => 'PAUD/TK',
        'sd' => 'SD',
        'smp' => 'SMP',
        'sma' => 'SMA/SMK',
        'kuliah' => 'Perguruan tinggi',
        'putus_sekolah' => 'Putus sekolah',
    ];

    protected $fillable = [
        'panti_id', 'nik', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
        'kategori', 'pendidikan', 'nama_wali', 'tanggal_masuk', 'tanggal_keluar',
        'status', 'keterangan',
    ];

    protected $appends = ['umur'];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date:Y-m-d',
            'tanggal_masuk' => 'date:Y-m-d',
            'tanggal_keluar' => 'date:Y-m-d',
        ];
    }

    public function desilLaporans(): HasMany
    {
        return $this->hasMany(DesilLaporan::class);
    }

    public function laporanTerakhir(): HasOne
    {
        return $this->hasOne(DesilLaporan::class)->latestOfMany();
    }

    /** Ringkasan laporan desil terakhir untuk ditampilkan di daftar. */
    public function laporanRingkas(): ?array
    {
        $l = $this->laporanTerakhir;

        return $l ? ['status' => $l->status, 'desil' => $l->desil, 'catatan_review' => $l->catatan_review] : null;
    }

    protected function umur(): Attribute
    {
        return Attribute::get(fn () => $this->tanggal_lahir?->age);
    }
}
