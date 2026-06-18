<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AddonController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\HotelController;
use App\Http\Controllers\Api\ReviewController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login-google', [AuthController::class, 'loginGoogle']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);


Route::post('/profile/update', [AuthController::class, 'updateProfile']);
Route::post('/profile/change-password', [AuthController::class, 'changePassword']);
Route::post('/profile/change-email', [AuthController::class, 'changeEmail']);

Route::get('/hotels', [HotelController::class, 'index']);
Route::get('/hotels/{hotel}', [HotelController::class, 'show']);
Route::get('/rooms/{room}', [HotelController::class, 'room']);
Route::get('/addons', [AddonController::class, 'index']);

Route::get('/bookings', [BookingController::class, 'index']);
Route::post('/bookings', [BookingController::class, 'store']);
Route::get('/bookings/{booking}', [BookingController::class, 'show']);
Route::put('/bookings/{booking}', [BookingController::class, 'update']);
Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

Route::get('/reviews', [ReviewController::class, 'index']);
Route::post('/reviews', [ReviewController::class, 'store']);
Route::post('/reviews/{review}', [ReviewController::class, 'update']);
Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
Route::get('/rooms/{room}/rating', [ReviewController::class, 'summary']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});
