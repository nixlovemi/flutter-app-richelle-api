<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;

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

Route::middleware('api.key')->group(function () {
    Route::prefix('v1')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('/login', [AuthController::class, 'login']);

            // Protected routes (require authentication)
            Route::middleware('auth:sanctum')->group(function () {
                Route::post('/logout', [AuthController::class, 'logout']);
                Route::post('/logout-all', [AuthController::class, 'logoutAll']);
                Route::get('/me', [AuthController::class, 'me']);
            });
        });

        Route::prefix('user')->group(function () {
            Route::middleware('auth:sanctum')->group(function () {
                Route::post('/update-profile', [UserController::class, 'updateProfile']);
            });
        });

        Route::group([], function(){
            Route::fallback(function () {
                return response()->json(['message' => 'API endpoint not found'], 404);
            })->name('app.404');
        });
    });
});
