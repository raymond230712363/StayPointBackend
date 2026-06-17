<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

Route::post('/profile/update', [AuthController::class, 'updateProfile']);
Route::post('/profile/change-password', [AuthController::class, 'changePassword']);
Route::post('/profile/change-email', [AuthController::class, 'changeEmail']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});