<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\PantiController;
use App\Http\Controllers\PantiProfilController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Endpoint RESTful yang kompatibel dengan dinas/ dan klien SPA/mobile.
*/

Route::post('/login', [ApiController::class, 'login']);
Route::post('/logout', [ApiController::class, 'logout']);
Route::get('/me', [ApiController::class, 'me']);
Route::get('/referensi', [ApiController::class, 'referensi']);
Route::get('/dashboard', [ApiController::class, 'dashboard']);

Route::get('/panti', [ApiController::class, 'panti']);
Route::get('/panti/{panti}', [ApiController::class, 'pantiDetail']);
Route::put('/panti/{panti}', [PantiController::class, 'update']);

// Profil Panti
Route::prefix('/panti/{panti}/profil')->group(function () {
    Route::get('/pengurus', [PantiProfilController::class, 'pengurus']);
    Route::post('/pengurus', [PantiProfilController::class, 'simpanPengurus']);
    Route::delete('/pengurus/{pengurus}', [PantiProfilController::class, 'hapusPengurus']);

    Route::get('/dokumen', [PantiProfilController::class, 'dokumen']);
    Route::post('/dokumen', [PantiProfilController::class, 'unggahDokumen']);
    Route::delete('/dokumen/{dokumen}', [DokumenController::class, 'destroy']);
    Route::get('/dokumen/{dokumen}/unduh', [DokumenController::class, 'download']);

    Route::get('/jenis-bantuan', [PantiProfilController::class, 'jenisBantuan']);
    Route::put('/jenis-bantuan', [PantiProfilController::class, 'simpanJenisBantuan']);

    Route::get('/aktivitas', [PantiProfilController::class, 'aktivitas']);
});

// Anak Binaan
Route::get('/anak-binaan', [ApiController::class, 'anakBinaan']);
Route::post('/anak-binaan', [ApiController::class, 'simpanAnak']);
Route::delete('/anak-binaan/{anak}', [ApiController::class, 'hapusAnak']);

// Riwayat & Monitoring
Route::get('/riwayat-bantuan', [ApiController::class, 'riwayatBantuan']);
Route::get('/monitoring', [ApiController::class, 'monitoring']);
Route::get('/laporan/{jenis}', [ApiController::class, 'laporan']);

