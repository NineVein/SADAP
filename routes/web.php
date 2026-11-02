<?php

use App\Http\Controllers\AnakController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesilController;
use App\Http\Controllers\DokumenController;
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
     * PANTI = pusat kerja. Anak, pengurus, dokumen, dan validasi dikerjakan
     * dari halaman detail panti, tanpa pindah-pindah menu.
     */
    Route::resource('panti', PantiController::class);
    Route::get('/panti/{panti}/profil/aktivitas', [\App\Http\Controllers\PantiProfilController::class, 'aktivitas'])->name('panti.aktivitas');
    Route::post('/panti/{panti}/ajukan', [ValidasiController::class, 'ajukan'])->name('panti.ajukan')->middleware('throttle:60,1');
    Route::post('/panti/{panti}/setujui', [ValidasiController::class, 'setujui'])->name('panti.setujui')->middleware('throttle:60,1');
    Route::post('/panti/{panti}/tolak', [ValidasiController::class, 'tolak'])->name('panti.tolak')->middleware('throttle:60,1');
    Route::put('/panti/{panti}/jenis-bantuan', [PantiController::class, 'simpanJenisBantuan'])->name('panti.jenis-bantuan')->middleware('throttle:60,1');

    Route::resource('pengurus', PengurusController::class)->only(['store', 'update', 'destroy'])
        ->parameters(['pengurus' => 'pengurus']);

    Route::post('/dokumen', [DokumenController::class, 'store'])->name('dokumen.store')->middleware('throttle:30,1');
    Route::get('/dokumen/{dokumen}/unduh', [DokumenController::class, 'download'])->name('dokumen.download');
    Route::delete('/dokumen/{dokumen}', [DokumenController::class, 'destroy'])->name('dokumen.destroy');

    /* ANAK & DESIL = satu halaman */
    Route::resource('anak', AnakController::class)->except('show');
    Route::post('/anak/{anak}/desil', [DesilController::class, 'store'])->name('desil.store');
    Route::post('/desil/{desilLaporan}/setujui', [DesilController::class, 'setujui'])->name('desil.setujui');
    Route::post('/desil/{desilLaporan}/tolak', [DesilController::class, 'tolak'])->name('desil.tolak');

    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/ekspor/{jenis}', [LaporanController::class, 'export'])
        ->whereIn('jenis', ['panti', 'anak'])
        ->name('laporan.export');

    // Alamat lama diarahkan ke halaman gabungan agar bookmark tidak rusak.
    Route::get('/pengurus', fn () => redirect('/panti'));
    Route::get('/dokumen', fn () => redirect('/panti'));
    Route::get('/validasi', fn () => redirect()->route('panti.index', ['validasi' => 'diajukan']));
    Route::get('/desil', fn () => redirect()->route('anak.index', ['desil' => 'belum']));

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::patch('/users/{user}/aktif', [UserController::class, 'toggle'])->name('users.toggle');

        Route::resource('kelurahan', KelurahanController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('/log-aktivitas', [LogController::class, 'index'])->name('log.index');
    });
});
