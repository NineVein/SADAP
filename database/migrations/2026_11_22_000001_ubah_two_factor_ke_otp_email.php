<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2FA berpindah dari TOTP (aplikasi autentikator) ke OTP email.
 * Rahasia TOTP dan kode pemulihan tidak dipakai lagi. Kolom two_factor_confirmed_at
 * tetap menjadi penanda "2FA aktif", jadi pengguna yang sudah aktif otomatis
 * menerima kode lewat email pada login berikutnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
        });
    }
};
