<?php

namespace Database\Seeders;

use App\Models\JenisBantuan;
use Illuminate\Database\Seeder;

class JenisBantuanSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'kode' => 'sembako',
                'nama' => 'Bahan Pokok & Sembako',
                'kategori' => 'Pangan',
                'deskripsi' => 'Beras, minyak goreng, gula, telur, dan susu untuk kebutuhan konsumsi harian anak panti.',
            ],
            [
                'kode' => 'seragam_sekolah',
                'nama' => 'Seragam & Perlengkapan Sekolah',
                'kategori' => 'Pendidikan',
                'deskripsi' => 'Pakaian seragam, tas sekolah, sepatu, buku, dan alat tulis untuk anak usia sekolah.',
            ],
            [
                'kode' => 'biaya_pendidikan',
                'nama' => 'Bantuan SPP & Operasional Belajar',
                'kategori' => 'Pendidikan',
                'deskripsi' => 'Bantuan pembiayaan SPP sekolah, iuran komite, buku paket, dan bimbingan belajar.',
            ],
            [
                'kode' => 'layanan_kesehatan',
                'nama' => 'Layanan Medis & Obat-obatan',
                'kategori' => 'Kesehatan',
                'deskripsi' => 'Pemeriksaan kesehatan rutin, obat-obatan, vitamin daya tahan tubuh, dan P3K.',
            ],
            [
                'kode' => 'sandang',
                'nama' => 'Pakaian & Perlengkapan Pribadi',
                'kategori' => 'Sandang',
                'deskripsi' => 'Pakaian harian, pakaian ibadah, handuk, selimut, dan perlengkapan sanitasi pribadi.',
            ],
            [
                'kode' => 'sarana_asrama',
                'nama' => 'Sarana & Prasarana Gedung Asrama',
                'kategori' => 'Sarana Prasarana',
                'deskripsi' => 'Perbaikan fasilitas tempat tidur, lemari, fasilitas MCK, dapur, dan meja belajar.',
            ],
            [
                'kode' => 'nutrisi_balita',
                'nama' => 'Nutrisi & Gizi Khusus Balita',
                'kategori' => 'Kesehatan',
                'deskripsi' => 'Susu pertumbuhan, makanan pendamping ASI, bubur gizi, dan multivitamin balita.',
            ],
            [
                'kode' => 'pelatihan_keterampilan',
                'nama' => 'Pelatihan Vokasi & Keterampilan Kerja',
                'kategori' => 'Pemberdayaan',
                'deskripsi' => 'Pelatihan komputer dasar, menjahit, tata boga, kerajinan, dan kewirausahaan remaja.',
            ],
        ];

        foreach ($items as $item) {
            JenisBantuan::updateOrCreate(['kode' => $item['kode']], $item);
        }
    }
}

