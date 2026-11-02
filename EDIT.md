# Patch: Pengurus + Dokumen Instansi + Jenis Bantuan pada profil panti
Salin folder `backend/*` dan `frontend/*` ke project (timpa). Lalu edit manual (sedikit):

## BACKEND
### 1. config/diskat.php — tambahkan sebelum baris `'kategori_anak' => [`
    'kategori_dokumen_instansi' => ['akta_pendirian', 'izin_operasional', 'akreditasi', 'npwp', 'sk_kemenkumham', 'struktur_organisasi', 'foto_gedung'],
    'dokumen_instansi_wajib' => ['akta_pendirian', 'izin_operasional'], // wajib ada & belum kedaluwarsa untuk mengajukan

### 2. routes/api.php
Tambah di atas: `use App\Http\Controllers\PantiProfilController;`
a) Di grup `auth:sanctum` (bagian "Dapat dibaca semua peran"), setelah route `panti/{panti}` GET:
    Route::get('panti/{panti}/pengurus', [PantiProfilController::class, 'pengurus']);
    Route::get('panti/{panti}/dokumen', [PantiProfilController::class, 'dokumen']);
    Route::get('panti/{panti}/jenis-bantuan', [PantiProfilController::class, 'jenisBantuan']);
b) HAPUS baris `Route::delete('dokumen/{dokumen}', ...)` dari grup `role:operator`.
c) Di grup `role:super_admin,operator` (yang berisi `put panti/{panti}`) tambahkan:
    Route::post('panti/{panti}/pengurus', [PantiProfilController::class, 'simpanPengurus']);
    Route::put('pengurus/{pengurus}', [PantiProfilController::class, 'ubahPengurus']);
    Route::delete('pengurus/{pengurus}', [PantiProfilController::class, 'hapusPengurus']);
    Route::post('panti/{panti}/dokumen', [PantiProfilController::class, 'unggahDokumen']);
    Route::put('panti/{panti}/jenis-bantuan', [PantiProfilController::class, 'simpanJenisBantuan']);
    Route::delete('dokumen/{dokumen}', [DokumenController::class, 'destroy']);

### 3. app/Http/Controllers/DokumenController.php — method destroy, ganti baris abort_unless menjadi:
    abort_unless($pemilik instanceof \App\Models\Panti || ($pemilik instanceof Pengajuan && $pemilik->status->bisaDiedit()), 422, 'Dokumen ini tidak dapat dihapus pada tahap sekarang.');

### 4. database/seeders/PantiSeeder.php — sisipkan tepat sebelum komentar `// Anak binaan sesuai komposisi kategori`
    foreach (['Ketua', 'Sekretaris', 'Bendahara'] as $jab) {
        $panti->pengurus()->create(['nama' => $faker->name(), 'jabatan' => $jab, 'telepon' => '0812'.$faker->numerify('########'), 'periode_mulai' => now()->subYears(2)->toDateString(), 'status' => 'aktif']);
    }
    $panti->jenisBantuan()->attach(JenisBantuan::inRandomOrder()->limit(2)->pluck('id')->all());

### 5. Jalankan: php artisan migrate:fresh --seed
(PENTING: seeder memakai validasi baru, jadi UserSeeder pengganti HARUS ikut disalin — ia membuat dokumen instansi dummy.)

## FRONTEND
### src/App.jsx — 3 edit
1) Import: `import PantiPage from './PantiPage';`
2) Menu: ganti `['dashboard', 'Dashboard'], ['pengajuan', 'Pengajuan'],` menjadi
   `['dashboard', 'Dashboard'], ['panti', din ? 'Data Panti' : 'Profil Panti'], ['pengajuan', 'Pengajuan'],`
3) Peta halaman: tambahkan baris `panti: <PantiPage u={u} id={v.id} go={go} />,` di atas `form: <Form ...`
### src/style.css — tambahkan
.tabs{display:flex;gap:4px;margin:10px 0}.tabs button{background:#e2e8f0;color:#1f2937}.tabs button.on{background:#14324a;color:#fff}label.mut{display:inline-block;margin-right:6px}
