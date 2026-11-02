# SADAP – pembaruan sistem (Laravel + Inertia React)

Salin isi folder ini ke root proyek Laravel Anda (timpa file yang sama), lalu jalankan:

```bash
php artisan migrate        # 4 migrasi baru; role lama diganti nama di tempat
php artisan db:seed        # RoleSeeder; di lokal juga DemoSeeder
npm run dev
```

Tidak ada paket Composer baru yang wajib.

## 1. Edit manual `bootstrap/app.php`

Daftarkan alias middleware dan halaman error (gabungkan dengan isi file Anda):

```php
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [HandleInertiaRequests::class]);
    $middleware->alias([
        'role'   => EnsureUserHasRole::class,
        'active' => EnsureUserIsActive::class,
    ]);
})
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
        $status = $response->getStatusCode();

        if (! app()->environment(['local', 'testing']) && in_array($status, [403, 404, 419, 500, 503])) {
            return Inertia::render('Error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        }

        if ($status === 419) {
            return back()->with('error', 'Sesi berakhir, silakan coba lagi.');
        }

        return $response;
    });
})
```

## 2. Peran pengguna

| Peran | Kode | Hak akses |
|---|---|---|
| Administrator Dinas Sosial | `admin` | Semua data, validasi, laporan, pengguna, wilayah, log |
| Supervisor Dinas Sosial | `supervisor` | Melihat semua data, menyetujui/menolak validasi, laporan |
| Operator Kelurahan/Desa | `operator` | Hanya panti di kelurahan/desanya: tambah/ubah panti, anak, pengurus, dokumen, lalu mengajukan validasi |

Migrasi mengubah `admin_dinsos`→`admin`, `petugas_dinsos`→`supervisor`, `pengelola_panti`→`operator`
sehingga akun admin yang sudah ada tetap berfungsi.

## 3. Alur validasi

`draft` → operator melengkapi 9 poin kelengkapan → `diajukan` → supervisor/admin
`tervalidasi` atau `ditolak` (dengan catatan) → operator memperbaiki dan mengajukan ulang.
Selama `diajukan` atau `tervalidasi`, operator tidak bisa mengubah profil panti (admin bisa).

## 4. Setelah migrasi

- Buka menu **Wilayah** dan isi kelurahan/desa yang benar. Nama di `DemoSeeder` hanya contoh.
- Buka **Pengguna**, buat akun supervisor dan operator (operator wajib dipilihkan wilayah).
- Panti lama belum punya wilayah: admin perlu membuka tiap panti dan memilih kelurahan/desa.
- Hapus data uji (`aaa`, `aaaaaa`, `DUMMY-001`, dst.) dari menu Panti.
- Akun demo lokal (`supervisor@admin.test`, `operator@admin.test`) memakai password `password`.
  Jangan dipakai di server produksi.

## 5. Catatan keamanan

- File `.sql` yang Anda kirim berisi hash password dan isi tabel `sessions`. Jangan dibagikan publik;
  ganti password admin bila file itu sempat tersebar.
- Dokumen tersimpan di `storage/app/private/dokumen` (tidak bisa dibuka lewat URL), diunduh lewat
  controller yang memeriksa hak akses. Masukkan folder ini ke backup.
- Produksi: `APP_DEBUG=false`, `APP_ENV=production`, HTTPS, dan `SESSION_SECURE_COOKIE=true`.
- Ekspor CSV tidak memuat NIK anak. Sel yang diawali `=`, `+`, `-`, `@` diberi awalan `'` agar tidak jadi rumus Excel.

## 6. Opsional

- Pesan validasi berbahasa Indonesia: `composer require laravel-lang/common`, `php artisan lang:add id`,
  lalu `APP_LOCALE=id` di `.env`.
- Ekspor `.xlsx`: tambahkan `maatwebsite/excel`. Saat ini CSV (pemisah titik koma) sudah terbuka rapi di Excel.
- Zona waktu: tanggal tampil dalam WITA (Asia/Makassar) di sisi React; database tetap UTC.

## 7. Fitur desil (tambahan)

Migrasi baru: `2026_10_13_000001_add_desil_to_anaks_and_create_desil_laporans.php` (jalankan `php artisan migrate`).

- Menu **Desil** punya empat tab: Belum ada desil, Sudah ada desil, Menunggu persetujuan, Riwayat.
- Operator melaporkan desil (1-10, sumber data, tanggal, catatan) untuk anak aktif di wilayahnya, juga bisa mengubah desil yang sudah ada.
- Laporan operator berstatus *menunggu*; supervisor/admin menyetujui (desil anak otomatis terisi) atau menolak dengan alasan.
- Laporan dari admin/supervisor langsung berlaku.
- Satu anak hanya bisa punya satu laporan yang menunggu. Riwayat semua laporan tersimpan.
- Kolom Desil muncul di daftar Anak, dan rekap desil ada di halaman Laporan.

## 8. Penyederhanaan menu (versi terpadu)

Menu sekarang hanya: **Dashboard**, **Panti Asuhan**, **Anak & Desil**, **Laporan**, dan **Pengaturan** (admin).

| Dulu | Sekarang |
|---|---|
| Pengurus, Dokumen | Tab di halaman detail panti, formulir langsung di tab (tanpa pindah halaman) |
| Validasi | Tab status di daftar Panti + panel validasi di detail panti. Setelah setuju/tolak, langsung dibuka pengajuan berikutnya |
| Desil | Digabung ke Anak & Desil: filter "Belum ada desil", tombol lapor/ubah di tiap baris, antrean persetujuan di atas daftar |
| Pengguna, Wilayah, Log | Satu menu Pengaturan dengan tiga tab |

- **Pusat tugas** di Dashboard menggabungkan semua yang menunggu tindakan (validasi, desil, data belum lengkap, panti tanpa wilayah). Klik satu tugas, langsung sampai ke tempat mengerjakannya.
- Daftar kelengkapan di detail panti punya tombol di tiap poin yang kurang (Lengkapi / Tambah / Unggah).
- Setelah tambah/ubah anak dari detail panti, user dikembalikan ke detail panti.
- Alamat lama (`/validasi`, `/desil`, `/pengurus`, `/dokumen`) otomatis diarahkan ke halaman baru.

**Hapus file lama bila sebelumnya sudah disalin:**
`resources/js/Pages/Pengurus/`, `Pages/Dokumen/`, `Pages/Validasi/`, `Pages/Desil/`.
