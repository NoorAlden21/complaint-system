<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\EmployeeController;

// public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
Route::post('/password/reset', [AuthController::class, 'resetPassword']);

// protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/email/resend-verification', [AuthController::class, 'resendVerificationEmail']);

    Route::get('/complaints/meta', [ComplaintController::class, 'meta']);

    Route::apiResource('complaints', ComplaintController::class)
        ->only(['index', 'store', 'show', 'update']);

    Route::post('/complaints/{complaint}/reassign', [ComplaintController::class, 'reassign']);

    Route::post('/complaints/{complaint}/request-info', [ComplaintController::class, 'requestMoreInfo']);
});

//super_admin

Route::middleware(['auth:sanctum', 'role:super_admin'])->group(function () {
    Route::post('/employees', [EmployeeController::class, 'store']);
});
