<?php

namespace App\Models\Concerns;

use App\Models\Panti;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToPanti
{
    public function panti(): BelongsTo
    {
        return $this->belongsTo(Panti::class)->withDefault();
    }

    /** Batasi data sesuai wilayah user (operator hanya melihat panti di kelurahan/desanya). */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('panti', fn ($panti) => $panti->visibleTo($user));
    }
}
