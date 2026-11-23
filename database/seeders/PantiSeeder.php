<?php

namespace Database\Seeders;

use App\Models\Panti;
use Illuminate\Database\Seeder;

class PantiSeeder extends Seeder
{
    public function run(): void
    {
        $pantis = [
            [
                'nama' => 'Panti Asuhan Kasih Sayang',
                'alamat' => 'Minahasa, Sulawesi Utara',
                'no_sk' => 'DUMMY-001',
                'tanggal_berdiri' => '2020-01-15',
                'telepon' => '081234567890',
                'email' => 'dummy@example.com',
                'status' => 'aktif',
            ],
            [
                'nama' => 'Panti Asuhan Harapan Baru',
                'alamat' => 'Tondano, Sulawesi Utara',
                'no_sk' => 'DUMMY-002',
                'tanggal_berdiri' => '2018-06-10',
                'telepon' => '081234567891',
                'email' => 'harapan@example.com',
                'status' => 'aktif',
            ],
            [
                'nama' => 'Panti Asuhan Pelita Kasih',
                'alamat' => 'Langowan, Sulawesi Utara',
                'no_sk' => null,
                'tanggal_berdiri' => null,
                'telepon' => null,
                'email' => null,
                'status' => 'aktif',
            ],
            // Contoh LKS kategori lain (data demo; ganti dengan data resmi lewat menu LKS).
            [
                'nama' => 'LKS Lanjut Usia Contoh',
                'kategori_lks' => 'lanjut_usia',
                'alamat' => 'Tondano, Sulawesi Utara',
                'no_sk' => 'DUMMY-003',
                'tanggal_berdiri' => '2015-03-20',
                'telepon' => '081234567892',
                'email' => 'lansia@example.com',
                'status' => 'aktif',
            ],
            [
                'nama' => 'LKS Disabilitas Contoh',
                'kategori_lks' => 'disabilitas',
                'alamat' => 'Tomohon, Sulawesi Utara',
                'no_sk' => 'DUMMY-004',
                'tanggal_berdiri' => '2017-09-05',
                'telepon' => '081234567893',
                'email' => 'disabilitas@example.com',
                'status' => 'aktif',
            ],
        ];

        foreach ($pantis as $panti) {
            Panti::updateOrCreate(
                ['no_sk' => $panti['no_sk'] ?? null,
                 'nama' => $panti['nama']],
                $panti
            );
        }
    }
}