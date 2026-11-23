# Backup dan Uji Pulihkan

Backup yang belum pernah dicoba dipulihkan belum bisa disebut backup. Yang harus aman: **database** dan **folder dokumen** (`storage/app/private`).

## 1. Pasang paket backup
```bash
composer require spatie/laravel-backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider" --tag=backup-config
```
Jadwal harian sudah ada di `routes/console.php` dan aktif otomatis begitu paket terpasang (butuh cron `schedule:run`, lihat `docs/PRODUKSI.md`).

## 2. Ubah `config/backup.php` (hanya bagian ini)
| Kunci | Nilai |
|---|---|
| `backup.name` | `'diskatmsa'` |
| `backup.source.files.include` | `[storage_path('app/private')]` |
| `backup.source.files.exclude` | `[]` |
| `backup.source.databases` | `['mysql']` (nama koneksi di `config/database.php`) |
| `backup.destination.disks` | `['backup']` |
| `backup.password` | `env('BACKUP_ARCHIVE_PASSWORD')` |
| `backup.encryption` | `'default'` (AES-256 bila PHP mendukung) |
| `notifications.mail.to` | `env('BACKUP_NOTIFY_EMAIL')` |
| `cleanup.default_strategy` | simpan semua 7 hari, harian 16 hari, mingguan 8 minggu, bulanan 4 bulan (bawaan sudah mendekati ini) |

Tambahkan disk `backup` di `config/filesystems.php`:
```php
'backup' => ['driver' => 'local', 'root' => env('BACKUP_DISK_PATH', storage_path('app/backups')), 'throw' => true],
```
Dan di `config/database.php` pada koneksi `mysql` (agar `mysqldump` ditemukan):
```php
'dump' => ['dump_binary_path' => env('DB_DUMP_PATH')],
```
Coba sekarang: `php artisan backup:run` lalu `php artisan backup:list`.

## 3. Aturan 3-2-1
Minimal **3** salinan, di **2** media berbeda, **1** di luar lokasi server. Folder `BACKUP_DISK_PATH` sebaiknya disk lain atau
dipasang dari penyimpanan eksternal, lalu salin berkala ke lokasi lain (mis. `rclone`/`rsync` ke server kantor atau cloud).
Backup di disk yang sama dengan aplikasi tidak melindungi dari kerusakan disk.
Simpan `BACKUP_ARCHIVE_PASSWORD` dan `APP_KEY` di pengelola sandi: tanpa keduanya backup tidak bisa dibuka atau dibaca penuh.

## 4. Uji pulihkan (wajib, tiap triwulan dan setelah mengubah konfigurasi backup)
Pakai skrip `scripts/uji-pulihkan.sh`. Skrip ini memulihkan ke database sementara bernama `diskat_uji_pulih`,
tidak menyentuh database produksi, lalu menghapusnya.
```bash
export DB_HOST=127.0.0.1 DB_USERNAME=root DB_PASSWORD=... BACKUP_ARCHIVE_PASSWORD=...
bash scripts/uji-pulihkan.sh /var/backups/diskatmsa/diskatmsa/2026-01-01-01-30-00.zip
```
Yang diperiksa: berkas dapat dibuka, dump SQL dapat diimpor, jumlah baris tabel utama (LKS, klien, pengguna), dan jumlah berkas dokumen.
Bandingkan angkanya dengan produksi. Catat tanggal uji dan hasilnya.

## 5. Pemulihan sungguhan (bila terjadi bencana)
1. Siapkan server dengan langkah `docs/PRODUKSI.md` sampai sebelum `migrate`.
2. Buka zip backup terbaru, impor `db-dumps/mysql-*.sql` ke database kosong.
3. Salin isi `storage/app/private` dari backup ke server baru, atur kepemilikan ke user web server.
4. Pasang `.env` lama (termasuk **APP_KEY yang sama**), lalu `php artisan sistem:cek-produksi`.
5. Masuk dan periksa data terbaru. Catat berapa jam data yang hilang (selisih backup terakhir dan kejadian).
