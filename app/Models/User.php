<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role_id', 'kelurahan_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class);
    }

    public function hasRole(string ...$names): bool
    {
        return $this->role !== null && in_array($this->role->name, $names, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    public function isSupervisor(): bool
    {
        return $this->hasRole(Role::SUPERVISOR);
    }

    public function isOperator(): bool
    {
        return $this->hasRole(Role::OPERATOR);
    }

    /** True bila verifikasi dua langkah (OTP email) sudah diaktifkan dan dikonfirmasi. */
    public function hasTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    /** Peran yang wajib memakai verifikasi dua langkah (diatur di config/diskat.php). */
    public function mustUseTwoFactor(): bool
    {
        return (bool) config('diskat.two_factor.wajib')
            && $this->hasRole(...(array) config('diskat.two_factor.peran', []));
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
