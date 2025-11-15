<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// public
Route::prefix('/')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
});

// protected
Route::middleware('auth:sanctum')->prefix('/')->group(function () {
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
});
