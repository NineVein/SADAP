# Patch: DISKATmsa menjadi sistem LKS (Anak, Lanjut Usia, Disabilitas)

Berisi hanya file yang berubah atau baru, dengan struktur folder yang sama seperti proyek Anda.
Salin ke root proyek (timpa file yang sama), lalu:

```bash
php artisan migrate          # migrasi 2026_11_02_000001 (aman untuk data yang ada)
php artisan test             # tes lama + LksKategoriTest yang baru
npm run build                # atau npm run dev

# opsional, data demo lokal
php artisan db:seed --class=PantiSeeder
php artisan db:seed --class=KlienDemoSeeder
```

Kode ini belum pernah dijalankan (tidak ada PHP di lingkungan penyusunnya). Hanya keseimbangan kurung yang dicek.
Jalankan `php artisan test` dulu dan kirim galat jika ada.

## Yang berubah

**Penamaan tampilan**
- Menu **Panti Asuhan → LKS**, **Anak & Desil → Klien**. Semua judul, tombol, pesan, dan laporan ikut diganti.
- Alamat: `/lks` dan `/klien` (alamat lama `/panti` dan `/anak` otomatis diarahkan).
- Header: "Sistem Pendataan Lembaga Kesejahteraan Sosial (LKS)".

**LKS dipisah per kategori**
- Kolom baru `pantis.kategori_lks`: `anak` | `lanjut_usia` | `disabilitas`. Satu LKS melayani satu kategori.
- Halaman LKS punya tab pemisah (Semua | Anak | Lanjut Usia | Disabilitas) dengan jumlah, plus lencana kategori.
- Formulir LKS: pilih kategori di bagian atas. Kategori **terkunci** setelah LKS punya klien.

**Klien (pengganti "Tambah Anak")**
- Kolom baru `anaks.kategori_klien`, `jenis_disabilitas`, `tingkat_ketergantungan`.
- Kategori klien **selalu mengikuti kategori LKS** yang dipilih (diisi server, tidak bisa dimanipulasi dari form).
  Klien tidak bisa dipindah ke LKS berkategori lain.
- Formulir menyesuaikan kategori:

| Kategori | Isian khusus |
|---|---|
| Anak | latar belakang (yatim, piatu, yatim piatu, dhuafa, terlantar), pendidikan |
| Lanjut usia | latar belakang (terlantar, dhuafa, sebatang kara), tingkat ketergantungan |
| Disabilitas | latar belakang, **ragam disabilitas** (fisik, sensorik netra, sensorik rungu/wicara, intelektual, mental, ganda), tingkat ketergantungan |

- Validasi usia (`config/diskat.php` bagian `usia`): Anak maksimal 18 tahun, Lanjut Usia minimal 60 tahun,
  Disabilitas tanpa batas. **Nilai ini contoh, sesuaikan dengan klasifikasi Dinas Sosial.**
- Desil tetap berlaku untuk semua kategori klien.

**Dashboard, laporan, ekspor**
- Dashboard: panel "Rincian per Kategori Layanan" (jumlah LKS dan klien aktif per kategori, klik untuk menyaring).
- Laporan: kolom klien Anak/Lansia/Disabilitas per kelurahan, ringkasan per kategori, pendidikan hanya untuk anak.
- CSV: `/laporan/ekspor/lks` dan `/laporan/ekspor/klien`, ditambah kolom kategori, ragam disabilitas, dan tingkat ketergantungan.

## Keputusan desain yang perlu Anda ketahui

Nama internal **sengaja tidak diubah**: tabel `pantis`/`anaks`, kelas `Panti`/`Anak`, kolom `panti_id`, nama route
(`panti.*`, `anak.*`), komponen `Panti/*`, dan `/api/panti`. Alasannya: data produksi Anda, tes yang ada, dan API tidak
rusak, dan yang dilihat pengguna sudah sepenuhnya LKS dan Klien. Mengganti nama internal juga bisa dilakukan
(tabel, kelas, kolom, ±1.200 kemunculan kata "panti" di 68 file) tetapi sebaiknya dikerjakan di komputer yang bisa
menjalankan tes, satu langkah per langkah.

Data lama aman: semua LKS dan klien yang sudah ada otomatis berkategori **Anak** (nilai bawaan kolom).
Admin cukup mengubah kategori LKS yang bukan anak sebelum menambahkan klien ke LKS itu.

## Perbaikan kecil yang ikut dikerjakan

- Tombol "Ajukan ke Dinas Sosial" selalu nonaktif: halaman membaca `panti.kelengkapan.lengkap`, padahal server mengirim angka persen.
  Sekarang kedua bentuk didukung.
- Kartu "Klien Terdaftar" dan "Pengurus" di tab profil selalu 0: `anaks_count`/`pengurus_count` tidak ikut dikirim.
- NIK klien tidak tampil di tab klien detail LKS: kolom `nik` tidak ikut dikirim.

## Catatan

- `PantiRequest` dan `AnakRequest` tidak dipakai controller (validasi ada di controller). Teksnya sudah disesuaikan, tetapi
  aturan di sana tidak mengenal kategori. Gunakan salah satu, jangan keduanya.
- `ApiController::simpanAnak` kini mengisi `kategori_klien` dari LKS dan `referensi` menambah `kategori_lks`
  dan `kategori_klien` (hanya penambahan, tidak mengubah bentuk lama).
- Saran lanjutan: jenis bantuan (`jenis_bantuans`) mungkin perlu dibedakan per kategori (misalnya alat bantu untuk disabilitas,
  perawatan harian untuk lansia). Belum dikerjakan karena membutuhkan daftar resmi dari Dinas Sosial.
