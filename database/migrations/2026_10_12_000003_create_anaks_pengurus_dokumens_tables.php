<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panti_id')->constrained('pantis')->cascadeOnDelete();
            $table->string('nik', 16)->nullable()->index();
            $table->string('nama');
            $table->string('jenis_kelamin', 1); // L | P
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir');
            $table->string('kategori', 20)->default('dhuafa');
            $table->string('pendidikan', 20)->nullable();
            $table->string('nama_wali')->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->date('tanggal_keluar')->nullable();
            $table->string('status', 10)->default('aktif'); // aktif | keluar
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['panti_id', 'status']);
        });

        Schema::create('pengurus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panti_id')->constrained('pantis')->cascadeOnDelete();
            $table->string('nama');
            $table->string('jabatan');
            $table->string('telepon', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('alamat', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('dokumens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panti_id')->constrained('pantis')->cascadeOnDelete();
            $table->string('jenis', 30);
            $table->string('judul');
            $table->string('path');
            $table->string('nama_asli');
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('ukuran')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumens');
        Schema::dropIfExists('pengurus');
        Schema::dropIfExists('anaks');
    }
};
