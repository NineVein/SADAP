# Panduan Produksi DISKATmsa

Untuk sistem yang memuat data pribadi anak, lanjut usia, dan penyandang disabilitas. Ikuti berurutan.
Panduan ini belum diuji di server Anda; sesuaikan perintah dengan sistem operasi yang dipakai.

## 1. Server
- PHP 8.3 atau lebih baru dengan ekstensi: `mbstring`, `xml` (termasuk `xmlwriter`), `ctype`, `json`, `openssl`, `pdo_mysql`, `fileinfo`, `zip`, `curl`, `bcmath`.
- MySQL 8 atau MariaDB 10.6+, Nginx (atau Apache), Composer. Node.js hanya untuk membangun aset (bisa di komputer lain).
- HTTPS wajib (Let's Encrypt/certbot). Tutup semua port kecuali 80, 443, dan SSH (dengan kunci, bukan password).
- Pengguna database khusus aplikasi dengan hak pada satu database saja, bukan `root`.

## 2. Pemasangan
```bash
git clone <repo> /var/www/diskatmsa && cd /var/www/diskatmsa
composer install --no-dev --optimize-autoloader
cp .env.production.example .env        # isi semua nilai GANTI
php artisan key:generate
npm ci && npm run build                 # atau bangun di komputer lain lalu salin public/build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 750 storage
```
**Jangan** menjalankan `DemoSeeder`, `PantiSeeder`, atau `KlienDemoSeeder` di produksi (berisi data/akun contoh).

## 3. Nginx (inti)
```nginx
server {
    listen 443 ssl http2;
    server_name GANTI.domain.anda.go.id;
    root /var/www/diskatmsa/public;          # HARUS folder public, bukan root proyek
    index index.php;
    client_max_body_size 6M;                 # dokumen maksimal 5 MB

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ { include snippets/fastcgi-php.conf; fastcgi_pass unix:/run/php/php8.3-fpm.sock; }
    location ~ /\.(?!well-known) { deny all; }   # blokir .env, .git, dll
}
server { listen 80; server_name GANTI.domain.anda.go.id; return 301 https://$host$request_uri; }
```
Dokumen unggahan tersimpan di `storage/app/private` (di luar `public`) dan hanya bisa diunduh lewat aplikasi yang memeriksa hak akses.

## 4. Scheduler (wajib untuk backup otomatis)
Tambahkan satu baris di `crontab -e` (user web server):
```
* * * * * cd /var/www/diskatmsa && php artisan schedule:run >> /dev/null 2>&1
```

## 5. Akun admin pertama
```bash
php artisan tinker
>>> \App\Models\User::create(['name'=>'Administrator','email'=>'admin@GANTI.go.id','password'=>'GANTI_password_panjang','role_id'=>\App\Models\Role::where('name','admin')->value('id'),'is_active'=>true]);
```
Jalankan `RoleSeeder` lebih dulu bila tabel `roles` masih kosong. **Password admin minimal 12 karakter** (huruf besar, huruf kecil, angka) dan unik, karena akun admin tidak pernah diblokir.
Buat akun supervisor dan operator lewat menu Pengaturan > Pengguna. Satu orang satu akun, jangan berbagi.

## 6. Periksa kesiapan
```bash
php artisan sistem:cek-produksi
```
Semua baris harus **OK** sebelum data nyata dimasukkan. Perintah ini memeriksa mode debug, HTTPS, cookie, blokir akun,
akun demo, password bawaan, folder dokumen, dan umur backup terakhir. Jalankan lagi setiap setelah pembaruan.

## 7. Pemeliharaan rutin
| Kapan | Apa |
|---|---|
| Harian (otomatis) | Backup, pembersihan backup lama, pemantauan backup |
| Mingguan | Lihat log (`storage/logs`) dan menu Log Aktivitas; pastikan email notifikasi backup tidak berisi galat |
| Bulanan | Perbarui dependensi keamanan: `composer update --no-dev`, uji, lalu pasang. Tinjau daftar pengguna aktif, nonaktifkan yang sudah tidak bertugas |
| Triwulan | **Uji pulihkan backup** (lihat `docs/BACKUP-DAN-PEMULIHAN.md`) |

## 8. Bila terjadi masalah
- **Lupa password:** admin membuat password baru lewat Pengaturan > Pengguna > Edit.
- **Akun terblokir** (salah password 5 kali): pengguna menghubungi administrator (kantor, WhatsApp, atau telepon yang tampil di halaman login). Admin memastikan identitas penelepon, lalu menekan **Buka Blokir** di Pengaturan > Pengguna. Bila pengguna lupa password, buat password baru lewat **Edit**.
- **Akun dicurigai diretas:** nonaktifkan akun, reset password, lalu periksa Log Aktivitas (cari aksi `auth.login_gagal` dan `auth.diblokir`).
- **APP_KEY:** menggantinya membuat data terenkripsi (NIK klien, sesi) tidak terbaca. Jangan diganti kecuali terpaksa, dan simpan salinannya di pengelola sandi.
- Catat setiap insiden: waktu, akun terkait, dan tindakan.

## 9. Aspek hukum dan tata kelola (bukan nasihat hukum)
Data klien termasuk data pribadi, sebagian bersifat sensitif (anak, kesehatan/disabilitas). Pastikan sebelum operasional:
1. Ada **dasar dan izin resmi** dari Dinas Sosial untuk mengelola data ini, serta penunjukan siapa penanggung jawab data.
2. Ada kebijakan **berapa lama** data disimpan dan cara menghapusnya.
3. Petugas kelurahan/desa memahami bahwa akses hanya untuk tugasnya dan tidak boleh membagikan akun.
4. Prosedur bila terjadi kebocoran data (siapa dihubungi, dalam berapa lama).
Konsultasikan dengan bagian hukum atau pejabat pengelola data di instansi Anda, termasuk kewajiban berdasarkan UU Perlindungan Data Pribadi.
