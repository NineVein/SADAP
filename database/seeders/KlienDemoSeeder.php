<?php

namespace Database\Seeders;

use App\Models\Anak;
use App\Models\Panti;
use Illuminate\Database\Seeder;

/**
 * DATA DEMO klien lanjut usia dan disabilitas (lokal saja).
 * Menjalankan: php artisan db:seed --class=KlienDemoSeeder
 */
class KlienDemoSeeder extends Seeder
{
    public function run(): void
    {
        $lansia = Panti::where('nama', 'LKS Lanjut Usia Contoh')->first();
        $disabilitas = Panti::where('nama', 'LKS Disabilitas Contoh')->first();

        if ($lansia) {
            foreach ([
                ['nama' => 'Klien Lansia Contoh 1', 'jk' => 'P', 'lahir' => '1950-04-12', 'latar' => 'sebatang_kara', 'tingkat' => 'sebagian'],
                ['nama' => 'Klien Lansia Contoh 2', 'jk' => 'L', 'lahir' => '1948-11-03', 'latar' => 'terlantar', 'tingkat' => 'total'],
            ] as $k) {
                Anak::firstOrCreate(
                    ['panti_id' => $lansia->id, 'nama' => $k['nama']],
                    [
                        'kategori_klien' => 'lanjut_usia',
                        'jenis_kelamin' => $k['jk'],
                        'tanggal_lahir' => $k['lahir'],
                        'kategori' => $k['latar'],
                        'tingkat_ketergantungan' => $k['tingkat'],
                        'status' => 'aktif',
                    ]
                );
            }
        }

        if ($disabilitas) {
            foreach ([
                ['nama' => 'Klien Disabilitas Contoh 1', 'jk' => 'L', 'lahir' => '1990-02-20', 'ragam' => 'fisik', 'tingkat' => 'mandiri'],
                ['nama' => 'Klien Disabilitas Contoh 2', 'jk' => 'P', 'lahir' => '1985-08-09', 'ragam' => 'intelektual', 'tingkat' => 'sebagian'],
            ] as $k) {
                Anak::firstOrCreate(
                    ['panti_id' => $disabilitas->id, 'nama' => $k['nama']],
                    [
                        'kategori_klien' => 'disabilitas',
                        'jenis_kelamin' => $k['jk'],
                        'tanggal_lahir' => $k['lahir'],
                        'kategori' => 'dhuafa',
                        'jenis_disabilitas' => $k['ragam'],
                        'tingkat_ketergantungan' => $k['tingkat'],
                        'status' => 'aktif',
                    ]
                );
            }
        }
    }
}
