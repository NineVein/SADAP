<?php

use App\Http\Controllers\AnakController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\ImporController;
use App\Http\Controllers\KelurahanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\PantiController;
use App\Http\Controllers\PengurusController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ValidasiController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profil.edit');
    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profil.password');

    /*
     * LKS = pusat kerja. Klien, pengurus, dokumen, dan validasi dikerjakan
     * dari halaman detail LKS, tanpa pindah-pindah menu.
     *
     * Alamat yang tampil ke pengguna adalah /lks dan /klien. Nama route
     * (panti.*, anak.*) dan nama kelas sengaja dipertahankan agar tes,
     * API, dan data yang sudah ada tidak berubah.
     */
    Route::resource('lks', PantiController::class)->names('panti')->parameters(['lks' => 'panti']);
    Route::get('/lks/{panti}/profil/aktivitas', [\App\Http\Controllers\PantiProfilController::class, 'aktivitas'])->name('panti.aktivitas');
    Route::get('/panti/{panti}/profil/aktivitas', [\App\Http\Controllers\PantiProfilController::class, 'aktivitas']);
    Route::post('/lks/{panti}/ajukan', [ValidasiController::class, 'ajukan'])->name('panti.ajukan')->middleware('throttle:60,1');
    Route::post('/lks/{panti}/setujui', [ValidasiController::class, 'setujui'])->name('panti.setujui')->middleware('throttle:60,1');
    Route::post('/lks/{panti}/tolak', [ValidasiController::class, 'tolak'])->name('panti.tolak')->middleware('throttle:60,1');
    Route::put('/lks/{panti}/jenis-bantuan', [PantiController::class, 'simpanJenisBantuan'])->name('panti.jenis-bantuan')->middleware('throttle:60,1');

    Route::resource('pengurus', PengurusController::class)->only(['store', 'update', 'destroy'])
        ->parameters(['pengurus' => 'pengurus']);

    Route::post('/dokumen', [DokumenController::class, 'store'])->name('dokumen.store')->middleware('throttle:30,1');
    Route::get('/dokumen/{dokumen}/unduh', [DokumenController::class, 'download'])->name('dokumen.download');
    Route::delete('/dokumen/{dokumen}', [DokumenController::class, 'destroy'])->name('dokumen.destroy');

    /* KLIEN = satu halaman (kategori: Anak, Lanjut Usia, Disabilitas) */
    Route::resource('klien', AnakController::class)->except('show')->names('anak')->parameters(['klien' => 'anak']);

    // Impor data dari CSV (admin dan operator; operator hanya untuk wilayahnya).
    Route::middleware('role:admin,operator')->group(function () {
        Route::get('/impor', [ImporController::class, 'index'])->name('impor.index');
        Route::get('/impor/templat/{jenis}', [ImporController::class, 'templat'])->whereIn('jenis', ['lks', 'klien'])->name('impor.templat');
        Route::post('/impor/pratinjau', [ImporController::class, 'pratinjau'])->name('impor.pratinjau');
        Route::post('/impor/simpan', [ImporController::class, 'simpan'])->name('impor.simpan');
    });

    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/ekspor/{jenis}', [LaporanController::class, 'export'])
        ->whereIn('jenis', ['lks', 'klien', 'panti', 'anak'])
        ->name('laporan.export');

    // Alamat lama diarahkan ke halaman gabungan agar bookmark tidak rusak.
    Route::get('/pengurus', fn () => redirect('/lks'));
    Route::get('/dokumen', fn () => redirect('/lks'));
    Route::get('/panti', fn (\Illuminate\Http\Request $r) => redirect()->route('panti.index', $r->query()));
    Route::get('/panti/{id}', fn (string $id) => redirect('/lks/'.$id))->whereNumber('id');
    Route::get('/anak', fn (\Illuminate\Http\Request $r) => redirect()->route('anak.index', $r->query()));
    Route::get('/validasi', fn () => redirect()->route('panti.index', ['validasi' => 'diajukan']));

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::patch('/users/{user}/aktif', [UserController::class, 'toggle'])->name('users.toggle');
        Route::post('/users/{user}/buka-blokir', [UserController::class, 'bukaBlokir'])->name('users.buka-blokir');

        Route::resource('kelurahan', KelurahanController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('/log-aktivitas', [LogController::class, 'index'])->name('log.index');
    });
});
