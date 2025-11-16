<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ComplaintController;

// public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
Route::post('/password/reset', [AuthController::class, 'resetPassword']);

// protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);

    Route::get('/complaints/meta', [ComplaintController::class, 'meta']);

    Route::apiResource('complaints', ComplaintController::class)
        ->only(['index', 'store', 'show']);
});
