<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ComplaintController;

// public
Route::post('/register', [AuthController::class, 'register']);

// protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);

    Route::get('/complaints/meta', [ComplaintController::class, 'meta']);

    Route::apiResource('complaints', ComplaintController::class)
        ->only(['index', 'store', 'show']);
});
