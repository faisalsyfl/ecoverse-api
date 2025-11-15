<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController; 
use App\Http\Controllers\HistoryController; 
use App\Http\Controllers\WithdrawalController; 
use App\Http\Controllers\WasteTypeController; 

Route::group(['prefix' => 'users'], function () {
    // --- Rute Registrasi Terpisah ---
    Route::post('/register-warga', [UserController::class, 'registerWarga']);
    Route::post('/player-id', [UserController::class, 'updatePlayerId']);
    Route::post('/register-kurir', [UserController::class, 'registerKurir']);
    Route::post('/register-banksampah', [UserController::class, 'registerBankSampah']);
    Route::get('/bank-sampah', [UserController::class, 'searchBankSampah']);
    
    // --- Rute Login ---
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/logout', [UserController::class, 'logout']);

    // --- Rute Terproteksi ---
    Route::group(['middleware' => 'auth:api'], function() {
        Route::get('/profile', [UserController::class, 'getProfile']);
        Route::post('/profile/avatar', [UserController::class, 'uploadAvatar']);
    });
})->middleware('auth:sanctum');

Route::group(['middleware' => 'auth:api'], function() {
    Route::get('/warga/dashboard', [DashboardController::class, 'getWargaDashboard']);
    Route::get('/warga/profile', [UserController::class, 'getWargaProfile']); 
    Route::post('/warga/transactions', [TransactionController::class, 'store']);
    Route::get('/warga/history/transactions', [HistoryController::class, 'getTransactionHistory']);
    Route::get('/warga/history/rewards', [HistoryController::class, 'getRewardsHistory']);
    Route::get('/warga/deposit-info', [UserController::class, 'getWargaDepositInfo']);
    Route::get('/bank-sampah/dashboard', [DashboardController::class, 'getBankSampahDashboard']);
    Route::patch('/transactions/{id}/complete', [TransactionController::class, 'complete']);
    Route::get('/transactions/{id}/details', [TransactionController::class, 'getDetails']);
    Route::get('/bank-sampah/profile', [UserController::class, 'getBankSampahProfile']);
    Route::get('/bank-sampah/history', [TransactionController::class, 'getHistoryForBankSampah']);
    Route::patch('/bank-sampah/settings', [DashboardController::class, 'updateBankSampahSettings']);
    Route::post('/warga/withdrawals', [WithdrawalController::class, 'requestWithdrawal']);
    Route::patch('/warga/withdrawals/{id}/complete', [WithdrawalController::class, 'completeWithdrawal']);
    Route::delete('/warga/withdrawals/{id}/cancel', [WithdrawalController::class, 'cancelWithdrawal']);
    Route::get('/bank-sampah/waste-types', [WasteTypeController::class, 'index']);
    Route::post('/bank-sampah/waste-types', [WasteTypeController::class, 'store']);
    Route::get('/bank-sampah/waste-types/{id}', [WasteTypeController::class, 'show']); // <-- BARU
    Route::patch('/bank-sampah/waste-types/{id}', [WasteTypeController::class, 'update']); // <-- BARU
    Route::delete('/bank-sampah/waste-types/{id}', [WasteTypeController::class, 'destroy']); // <-- BARU
});



