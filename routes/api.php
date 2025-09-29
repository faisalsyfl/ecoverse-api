<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::group(['prefix' => 'users'], function () {
    // --- Rute Registrasi Terpisah ---
    Route::post('/register-warga', [UserController::class, 'registerWarga']);
    Route::post('/register-kurir', [UserController::class, 'registerKurir']);
    Route::post('/register-banksampah', [UserController::class, 'registerBankSampah']);
    
    // --- Rute Login ---
    Route::post('/login', [UserController::class, 'login']);

    // --- Rute Terproteksi ---
    Route::group(['middleware' => 'auth:api'], function() {
        Route::get('/profile', [UserController::class, 'getProfile']);
        Route::post('/profile/avatar', [UserController::class, 'uploadAvatar']);
    });
})->middleware('auth:sanctum');