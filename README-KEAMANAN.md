# Patch 4: Keamanan dan Kesiapan Produksi (2FA lewat OTP email)

Pasang SETELAH patch 1, 2, dan 3 (beberapa berkas yang sama ikut berubah).

```bash
# salin isi folder lks-patch4 ke root proyek (timpa file yang sama)
php artisan migrate
npm run build
php artisan test
```

**Tidak perlu lagi** `composer require pragmarx/google2fa bacon/qr-code`. Bila sudah terpasang dari versi sebelumnya,
boleh dihapus: `composer remove pragmarx/google2fa bacon/qr-code`.

**Wajib:** email aplikasi harus benar-benar terkirim. Isi `MAIL_*` di `.env` (SMTP) dan uji sebelum mewajibkan 2FA.
Saat pengembangan, `MAIL_MAILER=log` menulis kode ke `storage/logs/laravel.log`.

## Cara kerja 2FA (OTP email)
1. **Aktivasi** di menu Keamanan: klik *Kirim Kode ke Email*, masukkan 6 digit dari email, selesai.
2. **Login**: setelah password benar, kode 6 digit dikirim ke email akun; pengguna memasukkannya di halaman verifikasi.
3. Kode berlaku **10 menit**, **sekali pakai**, dan hanya HMAC-nya yang disimpan di cache (bukan kode asli).
4. Setiap kode **hangus setelah 5 kali salah**; tambahan batas 5 percobaan/menit per akun+IP.
5. **Kirim ulang**: jeda 60 detik, maksimal 5 kali per 15 menit. Kode baru membatalkan kode lama.
6. Tidak ada lagi kode pemulihan. Jika pengguna tidak bisa membuka emailnya, admin memperbaiki email lalu menekan **Reset 2FA**.

Pengguna yang sebelumnya sudah mengaktifkan 2FA (TOTP) **otomatis beralih ke OTP email** pada login berikutnya;
rahasia TOTP dan kode pemulihan lama dihapus oleh migrasi `2026_11_22_000001_ubah_two_factor_ke_otp_email`.
Beri tahu mereka sebelum pembaruan dipasang.

## Isi
| Bagian | Berkas utama |
|---|---|
| **Verifikasi dua langkah (2FA) OTP email**: aktivasi, tantangan saat login, kirim ulang, batas percobaan, kode sekali pakai | `TwoFactorService`, `TwoFactorController`, `Mail/KodeOtpMail`, `views/emails/kode-otp.blade.php`, migrasi `..._ubah_two_factor_ke_otp_email`, halaman `Auth/TwoFactorChallenge` dan `Keamanan/Index` |
| **Pemaksaan 2FA** untuk admin dan supervisor (`TWO_FACTOR_WAJIB=true`); sebelum aktif hanya halaman Keamanan, Profil, dan Keluar yang bisa dibuka | `EnsureTwoFactorEnabled`, `config/diskat.php`, `bootstrap/app.php`, `routes/web.php` |
| **Reset 2FA oleh admin** untuk pengguna yang tidak bisa membuka emailnya | tombol "Reset 2FA" di Pengaturan > Pengguna |
| **Login API**: panggilan pertama mengirim OTP ke email (respons 422 `butuh_kode`), panggilan kedua menyertakan field `kode`; batas 5 percobaan/menit | `ApiController`, `routes/api.php` |
| **Header keamanan**: HSTS, Permissions-Policy, COOP, dan larangan cache halaman berisi data pribadi | `SecurityHeaders` |
| **Proxy tepercaya** (`TRUSTED_PROXIES`) | `bootstrap/app.php` |
| **`php artisan sistem:cek-produksi`**: debug, HTTPS, cookie, sesi, 2FA, **pengiriman email**, akun demo, password bawaan, folder dokumen, umur backup | `CekProduksi` |
| **Backup terjadwal** dan **uji pulihkan** | `routes/console.php`, `docs/BACKUP-DAN-PEMULIHAN.md`, `scripts/uji-pulihkan.sh` |
| **Contoh `.env` produksi** dan panduan pemasangan | `.env.production.example`, `docs/PRODUKSI.md` |

Tes: `TwoFactorTest` (OTP email: kirim, benar, salah, kedaluwarsa, hangus, sekali pakai, kirim ulang, email gagal, API) dan `CekProduksiTest`.

## Catatan keamanan OTP email
- Keamanan 2FA kini sebanding dengan keamanan **email** pengguna. Sarankan admin/supervisor memakai email resmi instansi
  yang dilindungi password kuat (idealnya juga 2FA di penyedia email).
- Cache harus driver yang bertahan antar permintaan (`database`, `file`, `redis`), bukan `array`. `sistem:cek-produksi` memeriksanya.
- Email sampai dalam hitungan detik pada SMTP yang sehat. Bila sering terlambat, pertimbangkan layanan email transaksional.
- Pertimbangkan menambah SPF/DKIM/DMARC pada domain pengirim agar email OTP tidak masuk spam.

## Hal yang sengaja belum dikerjakan
- **Enkripsi NIK klien** di database (perlu kolom hash terpisah untuk pencarian dan cek duplikat; kerjakan terpisah dan uji dengan data salinan).
- **Content-Security-Policy (CSP)**: bisa merusak Vite/Inertia bila salah atur; tambahkan setelah diuji.
- **API** (`/api/*`) memakai cookie sesi, bukan token, dan nilai `token` hanyalah ID sesi. Jika tidak ada aplikasi yang memakainya, nonaktifkan route API di produksi.

Kode ini belum dijalankan (tidak ada PHP di lingkungan penyusunnya). Jalankan `php artisan test` dan kirim galatnya bila ada.
