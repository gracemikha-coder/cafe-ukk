<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\MenuApiController;
use App\Http\Controllers\Api\PesananApiController;
use Illuminate\Support\Facades\Route;

// Alamat otomatis berawalan /api, misalnya POST /api/login

Route::post('/daftar', [AuthApiController::class, 'daftar']);
Route::post('/login', [AuthApiController::class, 'login']);

// boleh diakses walau pelanggan masih wajib ganti password sementara
Route::middleware('api.token:bebas')->group(function () {
    Route::post('/ganti-password', [AuthApiController::class, 'gantiPassword']);
    Route::post('/logout', [AuthApiController::class, 'logout']);
});

Route::middleware('api.token')->group(function () {
    Route::get('/menu', [MenuApiController::class, 'index']);

    Route::get('/pesanan', [PesananApiController::class, 'index']);
    Route::post('/pesanan', [PesananApiController::class, 'store']);
    Route::get('/pesanan/{id}', [PesananApiController::class, 'show'])->whereNumber('id');
});