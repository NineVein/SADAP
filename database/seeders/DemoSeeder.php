<?php

namespace Database\Seeders;

use App\Models\Kelurahan;
use App\Models\Panti;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Hanya untuk lingkungan lokal. Nama kelurahan di sini CONTOH,
 * ganti dengan data wilayah asli lewat menu Wilayah.
 * Semua akun demo memakai password "password".
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $contohA = Kelurahan::firstOrCreate(
            ['nama' => 'Kelurahan Contoh A', 'kecamatan' => 'Tondano Barat'],
            ['tipe' => 'kelurahan']
        );
        Kelurahan::firstOrCreate(
            ['nama' => 'Desa Contoh B', 'kecamatan' => 'Tondano Timur'],
            ['tipe' => 'desa']
        );

        User::firstOrCreate(['email' => 'supervisor@admin.test'], [
            'name' => 'Supervisor Dinsos',
            'password' => 'password',
            'role_id' => Role::where('name', Role::SUPERVISOR)->value('id'),
            'is_active' => true,
        ]);

        User::firstOrCreate(['email' => 'operator@admin.test'], [
            'name' => 'Operator Kelurahan Contoh A',
            'password' => 'password',
            'role_id' => Role::where('name', Role::OPERATOR)->value('id'),
            'kelurahan_id' => $contohA->id,
            'is_active' => true,
        ]);

        // Panti lama yang belum punya wilayah dikaitkan ke kelurahan contoh.
        Panti::whereNull('kelurahan_id')->update(['kelurahan_id' => $contohA->id]);
    }
}
