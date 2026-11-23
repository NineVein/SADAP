<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mengubah sistem menjadi sistem LKS untuk tiga kategori klien:
 * anak, lanjut usia, dan disabilitas.
 *
 * Aman untuk data yang sudah ada: seluruh panti dan anak lama otomatis
 * menjadi kategori "anak" lewat nilai bawaan kolom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pantis', function (Blueprint $table) {
            // anak | lanjut_usia | disabilitas
            $table->string('kategori_lks', 20)->default('anak')->after('nama')->index();
        });

        Schema::table('anaks', function (Blueprint $table) {
            // Selalu sama dengan kategori LKS tempat klien tinggal (diisi server).
            $table->string('kategori_klien', 20)->default('anak')->after('panti_id')->index();
            $table->string('jenis_disabilitas', 30)->nullable()->after('kategori');
            $table->string('tingkat_ketergantungan', 30)->nullable()->after('jenis_disabilitas');
        });
    }

    public function down(): void
    {
        Schema::table('anaks', function (Blueprint $table) {
            $table->dropIndex(['kategori_klien']);
            $table->dropColumn(['kategori_klien', 'jenis_disabilitas', 'tingkat_ketergantungan']);
        });

        Schema::table('pantis', function (Blueprint $table) {
            $table->dropIndex(['kategori_lks']);
            $table->dropColumn('kategori_lks');
        });
    }
};
