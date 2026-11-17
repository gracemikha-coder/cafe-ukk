<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BahanController;
use App\Http\Controllers\KasirPesananController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\StokMasukController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ResepController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout']);

Route::middleware(['auth', 'role:manajer'])->prefix('manajer')->group(function () {
    Route::get('/bahan', [BahanController::class, 'index']);
    Route::get('/bahan/create', [BahanController::class, 'create']);
    Route::post('/bahan', [BahanController::class, 'store']);
    Route::get('/bahan/{bahan}/edit', [BahanController::class, 'edit']);
    Route::put('/bahan/{bahan}', [BahanController::class, 'update']);

    Route::get('/menu', [MenuController::class, 'index']);
    Route::get('/menu/create', [MenuController::class, 'create']);
    Route::post('/menu', [MenuController::class, 'store']);
    Route::get('/menu/{menu}/edit', [MenuController::class, 'edit']);
    Route::put('/menu/{menu}', [MenuController::class, 'update']);
    Route::patch('/menu/{menu}/toggle', [MenuController::class, 'toggle']);

    Route::get('/stok-masuk', [StokMasukController::class, 'index']);
    Route::post('/stok-masuk', [StokMasukController::class, 'store']);
    Route::get('/laporan', [LaporanController::class, 'index']);
    Route::post('/supplier/perbarui-harga', [SupplierController::class, 'perbarui']);

    Route::get('/resep', [ResepController::class, 'index']);
    Route::get('/resep/{menu}', [ResepController::class, 'show']);
    Route::post('/resep/{menu}', [ResepController::class, 'store']);
    Route::delete('/resep/{menu}/{item}', [ResepController::class, 'destroy']);
});

Route::middleware(['auth', 'role:kasir'])->prefix('kasir')->group(function () {
    Route::get('/pesanan', [KasirPesananController::class, 'index']);
    Route::get('/pesanan/create', [KasirPesananController::class, 'create']);
    Route::post('/pesanan', [KasirPesananController::class, 'store']);
    Route::get('/pesanan/{pesanan}', [KasirPesananController::class, 'show']);
    Route::patch('/pesanan/{pesanan}/status', [KasirPesananController::class, 'ubahStatus']);
    Route::post('/pesanan/{pesanan}/batal', [KasirPesananController::class, 'batalkan']);
});