<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AuthController;

Route::post('/register',  [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout']);

Route::post('/transfer', [TransferController::class, 'transfer']);

Route::get('/users/{email}/transactions', [TransactionController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/transfer', [TransferController::class, 'transfer']);
    Route::get('/users/{email}/transactions', [TransactionController::class, 'index']);
    Route::post('/logout', [AuthController::class, 'logout']);

});



