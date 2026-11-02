<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Panti extends Model
{
    use SoftDeletes;

    public const DRAFT = 'draft';
    public const DIAJUKAN = 'diajukan';
    public const TERVALIDASI = 'tervalidasi';
    public const DITOLAK = 'ditolak';

    public const VALIDASI = [self::DRAFT, self::DIAJUKAN, self::TERVALIDASI, self::DITOLAK];

    /**
     * Kolom validasi (validation_status, validated_by, dst.) sengaja TIDAK
     * ada di sini agar tidak bisa diubah lewat form.
     */
    protected $fillable = [
        'nama',
        'alamat',
        'kelurahan_id',
        'no_sk',
        'tanggal_berdiri',
        'telepon',
        'email',
        'nama_pengelola',
        'kapasitas',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_berdiri' => 'date:Y-m-d',
            'diajukan_at' => 'datetime',
            'validated_at' => 'datetime',
        ];
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function anaks(): HasMany
    {
        return $this->hasMany(Anak::class);
    }

    public function pengurus(): HasMany
    {
        return $this->hasMany(Pengurus::class);
    }

    public function dokumens(): HasMany
    {
        return $this->hasMany(Dokumen::class);
    }

    public function jenisBantuan(): BelongsToMany
    {
        return $this->belongsToMany(JenisBantuan::class, 'panti_jenis_bantuan')
            ->withPivot('catatan')
            ->withTimestamps();
    }

    public function riwayatBantuan(): HasMany
    {
        return $this->hasMany(RiwayatBantuan::class);
    }

    public function riwayatBantuans(): HasMany
    {
        return $this->riwayatBantuan();
    }

    /** Operator hanya boleh melihat panti di kelurahan/desanya sendiri. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isOperator()
            ? $query->where('pantis.kelurahan_id', $user->kelurahan_id ?? 0)
            : $query;
    }

    /** Daftar kelengkapan data yang diperiksa sebelum panti boleh diajukan. */
    public function checklist(): array
    {
        $hasActivePengurus = $this->pengurus()
            ->where(fn ($q) => $q->where('status', 'aktif')->orWhereNull('status'))
            ->exists();

        $dokumens = $this->relationLoaded('dokumens') ? $this->dokumens : $this->dokumens()->get();
        $hasIzin = $dokumens->contains(fn ($d) => $d->jenis === 'izin_operasional' && ! $d->kadaluarsa);
        $hasAkta = $dokumens->contains(fn ($d) => in_array($d->jenis, ['akta_pendirian', 'sk_pendirian', 'akta_notaris'], true) && ! $d->kadaluarsa);

        return [
            ['key' => 'profil', 'label' => 'Nama dan alamat panti', 'done' => filled($this->nama) && filled($this->alamat)],
            ['key' => 'wilayah', 'label' => 'Kelurahan/desa ditentukan', 'done' => filled($this->kelurahan_id)],
            ['key' => 'no_sk', 'label' => 'Nomor SK pendirian', 'done' => filled($this->no_sk)],
            ['key' => 'tanggal_berdiri', 'label' => 'Tanggal berdiri', 'done' => filled($this->tanggal_berdiri)],
            ['key' => 'kontak', 'label' => 'Telepon atau email', 'done' => filled($this->telepon) || filled($this->email)],
            ['key' => 'pengelola', 'label' => 'Nama pengelola', 'done' => filled($this->nama_pengelola)],
            ['key' => 'pengurus', 'label' => 'Minimal 1 pengurus aktif', 'done' => $hasActivePengurus],
            ['key' => 'anak', 'label' => 'Minimal 1 anak terdata', 'done' => $this->countOf('anaks') > 0],
            ['key' => 'dokumen', 'label' => 'Dokumen instansi wajib aktif (Akta/SK & Izin Operasional)', 'done' => ($hasIzin && $hasAkta) || $this->countOf('dokumens') > 0],
            ['key' => 'jenis_bantuan', 'label' => 'Kebutuhan jenis bantuan terdata', 'done' => $this->countOf('jenisBantuan') > 0],
        ];
    }

    public function kelengkapan(): int
    {
        $items = collect($this->checklist());

        return (int) round($items->where('done', true)->count() / $items->count() * 100);
    }

    public function isLengkap(): bool
    {
        return $this->kelengkapan() === 100;
    }

    /** Pakai hasil withCount() bila ada, supaya tidak terjadi N+1 di daftar. */
    private function countOf(string $relation): int
    {
        $key = $relation.'_count';

        return array_key_exists($key, $this->attributes)
            ? (int) $this->attributes[$key]
            : $this->{$relation}()->count();
    }
}
