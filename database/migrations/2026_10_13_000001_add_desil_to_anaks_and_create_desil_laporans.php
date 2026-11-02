<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anaks', function (Blueprint $table) {
            // Desil kesejahteraan 1-10 yang sudah disetujui. NULL = belum ada desil.
            $table->unsignedTinyInteger('desil')->nullable()->after('kategori')->index();
        });

        Schema::create('desil_laporans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anak_id')->constrained('anaks')->cascadeOnDelete();
            $table->unsignedTinyInteger('desil');
            $table->string('sumber', 30);
            $table->date('tanggal_data')->nullable();
            $table->text('catatan')->nullable();
            $table->string('status', 15)->default('menunggu')->index(); // menunggu | disetujui | ditolak
            $table->foreignId('dilaporkan_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ditinjau_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditinjau_at')->nullable();
            $table->text('catatan_review')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desil_laporans');

        Schema::table('anaks', function (Blueprint $table) {
            $table->dropIndex(['desil']);
            $table->dropColumn('desil');
        });
    }
};
