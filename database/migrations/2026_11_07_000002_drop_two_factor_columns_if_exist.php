<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2FA tidak jadi dipakai (diganti blokir akun). Migrasi ini membuang kolom 2FA bila ada,
 * dan tidak melakukan apa-apa bila versi lama patch 4 belum pernah dimigrasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'] as $kolom) {
            if (Schema::hasColumn('users', $kolom)) {
                Schema::table('users', fn (Blueprint $table) => $table->dropColumn($kolom));
            }
        }
    }

    public function down(): void
    {
        // Sengaja kosong: fitur 2FA sudah dihapus.
    }
};
