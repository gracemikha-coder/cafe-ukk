<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BahanController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\StokMasukController;
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
});

Route::middleware(['auth', 'role:kasir'])->prefix('kasir')->group(function () {
    Route::get('/dashboard', fn () => 'Dashboard Kasir');
});