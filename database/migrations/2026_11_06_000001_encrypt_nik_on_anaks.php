<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mengenkripsi NIK klien di database.
 *
 * - Kolom nik menjadi TEXT (isi terenkripsi, panjangnya jauh melebihi 16 karakter).
 * - nik_hash (HMAC-SHA256 dengan APP_KEY) dipakai untuk pencarian persis dan cek duplikat,
 *   karena nilai terenkripsi tidak bisa dicari atau dibandingkan langsung.
 * - Data lama dienkripsi di tempat. Baris yang sudah terenkripsi dilewati, jadi aman dijalankan ulang.
 *
 * PENTING: cadangkan database sebelum menjalankan. Setelah ini APP_KEY TIDAK boleh diganti,
 * karena NIK tidak akan bisa didekripsi dan hash tidak akan cocok lagi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anaks', function (Blueprint $table) {
            $table->char('nik_hash', 64)->nullable()->after('nik')->index();
        });

        // Indeks lama pada nik (string 16) tidak bisa dipertahankan setelah kolom menjadi TEXT.
        if (Schema::hasIndex('anaks', 'anaks_nik_index')) {
            Schema::table('anaks', fn (Blueprint $table) => $table->dropIndex('anaks_nik_index'));
        }

        Schema::table('anaks', function (Blueprint $table) {
            $table->text('nik')->nullable()->change();
        });

        DB::table('anaks')->whereNotNull('nik')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $nik = (string) $row->nik;

                // Hanya NIK polos (16 digit) yang dienkripsi; yang sudah terenkripsi dilewati.
                if (! preg_match('/^\d{16}$/', $nik)) {
                    continue;
                }

                DB::table('anaks')->where('id', $row->id)->update([
                    'nik' => Crypt::encryptString($nik),
                    'nik_hash' => hash_hmac('sha256', $nik, (string) config('app.key')),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('anaks')->whereNotNull('nik')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                try {
                    $polos = Crypt::decryptString((string) $row->nik);
                } catch (\Throwable) {
                    continue; // sudah polos
                }

                DB::table('anaks')->where('id', $row->id)->update(['nik' => $polos]);
            }
        });

        Schema::table('anaks', function (Blueprint $table) {
            $table->dropIndex(['nik_hash']);
            $table->dropColumn('nik_hash');
        });

        Schema::table('anaks', function (Blueprint $table) {
            $table->string('nik', 16)->nullable()->change();
            $table->index('nik');
        });
    }
};
