<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/login', [AuthController::class, 'showlogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout']);
Route::middleware(['auth', 'role:manajer'])->prefix('manajer')->group(function () {
    Route::get('/dashboard', fn() => 'Dashboard Manjer');
});

Route::middleware(['auth', 'role:kasir'])->prefix('kasir')->group(function (){
    Route::get('/dashboard', fn() => 'Dashboard Kasir');
});
