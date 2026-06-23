<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\MeatScanController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['ok' => true]));

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [RegisterController::class, 'store']);
    Route::post('auth/login', [LoginController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile', [ProfileController::class, 'show']);
        Route::match(['put', 'patch'], 'profile', [ProfileController::class, 'update']);

        Route::post('scans/upload', [MeatScanController::class, 'upload']);
        Route::post('scans/{meatScan}/analyze', [MeatScanController::class, 'analyze']);
        Route::get('scans/history', [MeatScanController::class, 'history']);
        Route::get('scans/{meatScan}', [MeatScanController::class, 'show']);
        Route::delete('scans/{meatScan}', [MeatScanController::class, 'destroy']);
    });
});
