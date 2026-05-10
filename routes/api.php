<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['ok' => true]));

Route::prefix('v1')->group(function () {
    Route::apiResource('meat-scans', \App\Http\Controllers\Api\V1\MeatScanController::class)
        ->only(['index', 'store', 'show', 'destroy']);
});

