<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Kolom tambahan pada pengurus panti (periode masa jabatan & status keaktifan)
        Schema::table('pengurus', function (Blueprint $table) {
            $table->date('periode_mulai')->nullable()->after('email');
            $table->date('periode_selesai')->nullable()->after('periode_mulai');
            $table->string('status', 10)->default('aktif')->after('periode_selesai'); // aktif | nonaktif
        });

        // 2. Kolom tambahan pada dokumen panti (nomor dokumen & masa berlaku dokumen)
        Schema::table('dokumens', function (Blueprint $table) {
            $table->string('nomor', 100)->nullable()->after('judul');
            $table->date('berlaku_sampai')->nullable()->after('nomor');
        });

        // 3. Tabel master jenis bantuan sosial
        Schema::create('jenis_bantuans', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama', 150);
            $table->string('kategori', 50); // Pangan, Pendidikan, Kesehatan, Sarana Prasarana, dll
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Tabel kebutuhan tetap jenis bantuan panti (pivot panti & jenis bantuan)
        Schema::create('panti_jenis_bantuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panti_id')->constrained('pantis')->cascadeOnDelete();
            $table->foreignId('jenis_bantuan_id')->constrained('jenis_bantuans')->cascadeOnDelete();
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->unique(['panti_id', 'jenis_bantuan_id']);
        });

        // 5. Tabel riwayat bantuan yang pernah diterima oleh panti
        Schema::create('riwayat_bantuans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panti_id')->constrained('pantis')->cascadeOnDelete();
            $table->foreignId('jenis_bantuan_id')->constrained('jenis_bantuans')->cascadeOnDelete();
            $table->string('sumber', 100)->nullable(); // APBD, Kemensos, Donatur Swasta, CSR, dll
            $table->decimal('nilai', 15, 2)->default(0);
            $table->integer('jumlah')->default(1);
            $table->date('tanggal');
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->index(['panti_id', 'jenis_bantuan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_bantuans');
        Schema::dropIfExists('panti_jenis_bantuan');
        Schema::dropIfExists('jenis_bantuans');

        Schema::table('dokumens', function (Blueprint $table) {
            $table->dropColumn(['nomor', 'berlaku_sampai']);
        });

        Schema::table('pengurus', function (Blueprint $table) {
            $table->dropColumn(['periode_mulai', 'periode_selesai', 'status']);
        });
    }
};

