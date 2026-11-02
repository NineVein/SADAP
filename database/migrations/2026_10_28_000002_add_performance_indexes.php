<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengurus', function (Blueprint $table) {
            $table->index(['panti_id', 'status'], 'idx_pengurus_panti_status');
            $table->index(['panti_id', 'jabatan'], 'idx_pengurus_panti_jabatan');
        });

        Schema::table('dokumens', function (Blueprint $table) {
            $table->index(['panti_id', 'jenis'], 'idx_dokumens_panti_jenis');
            $table->index(['panti_id', 'berlaku_sampai'], 'idx_dokumens_panti_berlaku');
        });

        Schema::table('desil_laporans', function (Blueprint $table) {
            $table->index(['anak_id', 'status'], 'idx_desil_anak_status');
        });

        Schema::table('anaks', function (Blueprint $table) {
            $table->index(['status', 'desil'], 'idx_anaks_status_desil');
        });
    }

    public function down(): void
    {
        Schema::table('anaks', function (Blueprint $table) {
            $table->dropIndex('idx_anaks_status_desil');
        });

        Schema::table('desil_laporans', function (Blueprint $table) {
            $table->dropIndex('idx_desil_anak_status');
        });

        Schema::table('dokumens', function (Blueprint $table) {
            $table->dropIndex('idx_dokumens_panti_jenis');
            $table->dropIndex('idx_dokumens_panti_berlaku');
        });

        Schema::table('pengurus', function (Blueprint $table) {
            $table->dropIndex('idx_pengurus_panti_status');
            $table->dropIndex('idx_pengurus_panti_jabatan');
        });
    }
};

