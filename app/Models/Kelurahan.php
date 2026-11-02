<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelurahan extends Model
{
    protected $fillable = ['nama', 'kecamatan', 'tipe'];

    public function pantis(): HasMany
    {
        return $this->hasMany(Panti::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isOperator()
            ? $query->where('id', $user->kelurahan_id ?? 0)
            : $query;
    }
}
