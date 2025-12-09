<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\Admin\BackupController;


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

    Route::post('/complaints/{complaint}/lock', [ComplaintController::class, 'lock']);
    Route::post('/complaints/{complaint}/unlock', [ComplaintController::class, 'unlock']);

    //Route::post('/complaints/{complaint}/restore-version/{version}', [ComplaintController::class, 'restoreVersion']);
});

//super_admin

Route::middleware(['auth:sanctum', 'role:super_admin'])->group(function () {
    Route::post('/employees', [EmployeeController::class, 'store']);

    Route::prefix('backups')->group(function () {
        Route::get('', [BackupController::class, 'index']);
        Route::get('/last-successful', [BackupController::class, 'lastSuccessful']);
        Route::get('/{backupLog}/download', [BackupController::class, 'download']);
        Route::post('', [BackupController::class, 'store']);
    });
});
