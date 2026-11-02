<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** nama lama => [nama baru, label baru] */
    private array $roleMap = [
        'admin_dinsos' => ['admin', 'Administrator Dinas Sosial'],
        'petugas_dinsos' => ['supervisor', 'Supervisor Dinas Sosial'],
        'pengelola_panti' => ['operator', 'Operator Kelurahan/Desa'],
    ];

    public function up(): void
    {
        // Ganti nama role di tempat, sehingga user yang sudah ada tetap valid.
        foreach ($this->roleMap as $old => [$name, $label]) {
            DB::table('roles')->where('name', $old)->update([
                'name' => $name,
                'label' => $label,
                'updated_at' => now(),
            ]);
        }

        Schema::create('kelurahans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kecamatan');
            $table->string('tipe', 20)->default('kelurahan'); // kelurahan | desa
            $table->timestamps();

            $table->unique(['nama', 'kecamatan']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('kelurahan_id')
                ->nullable()
                ->after('role_id')
                ->constrained('kelurahans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelurahan_id');
        });

        Schema::dropIfExists('kelurahans');

        foreach ($this->roleMap as $old => [$name]) {
            DB::table('roles')->where('name', $name)->update(['name' => $old]);
        }
    }
};
