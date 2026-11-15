<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BahanController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout']);

Route::middleware(['auth', 'role:manajer'])->prefix('manajer')->group(function () {
    Route::get('/dashboard', fn () => 'Dashboard Manajer');

    Route::get('/bahan', [BahanController::class, 'index']);
    Route::get('/bahan/create', [BahanController::class, 'create']);
    Route::post('/bahan', [BahanController::class, 'store']);
    Route::get('/bahan/{bahan}/edit', [BahanController::class, 'edit']);
    Route::put('/bahan/{bahan}', [BahanController::class, 'update']);
    Route::delete('/bahan/{bahan}', [BahanController::class, 'destroy']);
});

Route::middleware(['auth', 'role:kasir'])->prefix('kasir')->group(function () {
    Route::get('/dashboard', fn () => 'Dashboard Kasir');
});