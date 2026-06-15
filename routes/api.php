<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController; // Pastikan namespace controller-mu bener

// ==================== ROUTE PUBLIC (Bisa diakses tanpa token) ====================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// TAMBAHKAN BARIS INI BIAR FLUTTER GA DAPET EROR 404 HTML LAGI, GAES!
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);


// ==================== ROUTE PROTECTED (Wajib pakai token Sanctum) ====================
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});