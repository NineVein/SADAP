<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('login_gagal')->default(0);
            $table->timestamp('terakhir_gagal_at')->nullable();
            $table->timestamp('terblokir_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['terblokir_at']);
            $table->dropColumn(['login_gagal', 'terakhir_gagal_at', 'terblokir_at']);
        });
    }
};
