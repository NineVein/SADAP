<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pantis', function (Blueprint $table) {
            $table->foreignId('kelurahan_id')->nullable()->after('alamat')
                ->constrained('kelurahans')->nullOnDelete();
            $table->string('nama_pengelola')->nullable()->after('email');
            $table->unsignedSmallInteger('kapasitas')->nullable()->after('nama_pengelola');

            // draft -> diajukan -> tervalidasi | ditolak
            $table->string('validation_status', 20)->default('draft')->after('status');
            $table->text('catatan_validasi')->nullable();
            $table->timestamp('diajukan_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->index('status');
            $table->index('validation_status');
        });
    }

    public function down(): void
    {
        Schema::table('pantis', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['validation_status']);
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('validated_by');
            $table->dropConstrainedForeignId('kelurahan_id');
            $table->dropSoftDeletes();
            $table->dropColumn([
                'nama_pengelola', 'kapasitas', 'validation_status',
                'catatan_validasi', 'diajukan_at', 'validated_at',
            ]);
        });
    }
};
