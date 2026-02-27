<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\QueueController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
});

// Queue processing for shared hosting (no middleware for cron access)
Route::post('/queue/process', [QueueController::class, 'processJobs'])
    ->name('queue.process');

Route::middleware('api.key')->group(function () {
    Route::prefix('v1')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('/login', [AuthController::class, 'login'])
                ->middleware(['throttle:6,1']);
            Route::post('/register', [AuthController::class, 'register'])
                ->middleware(['throttle:6,1']);

            // Email verification routes (auth optional via middleware configuration)
            Route::get('/email/verification-notice', [EmailVerificationController::class, 'notice'])
                ->middleware(['auth:sanctum'])->name('verification.notice');
            Route::post('/email/verification-resend', [EmailVerificationController::class, 'resend'])
                ->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.resend');

            // Email verification routes for non-authenticated users
            Route::get('/email/check-status', [EmailVerificationController::class, 'notice'])
                ->middleware(['throttle:6,1'])->name('verification.check-status');
            Route::post('/email/request-verification', [EmailVerificationController::class, 'resend'])
                ->middleware(['throttle:6,1'])->name('verification.request');

            // Protected routes (require authentication)
            Route::middleware('auth:sanctum')->group(function () {
                Route::post('/logout', [AuthController::class, 'logout']);
                Route::post('/logout-all', [AuthController::class, 'logoutAll']);
                Route::get('/me', [AuthController::class, 'me']);
            });
        });

        Route::prefix('user')->group(function () {
            Route::middleware('auth:sanctum')->group(function () {
                Route::post('/update-profile', [UserController::class, 'updateProfile'])
                    ->middleware(['throttle:6,1']);
            });
        });

        Route::group([], function(){
            Route::fallback(function () {
                return response()->json(['message' => 'API endpoint not found'], 404);
            })->name('app.404');
        });
    });
});
