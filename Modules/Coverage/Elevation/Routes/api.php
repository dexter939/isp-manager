<?php
use Illuminate\Support\Facades\Route;
use Modules\Coverage\Elevation\Http\Controllers\ElevationController;
Route::middleware('auth:sanctum')->group(function () {
    Route::post('elevation/calculate', [ElevationController::class, 'calculate']);
    Route::get('elevation/profiles/{profile}', [ElevationController::class, 'show']);
    Route::get('contracts/{contractId}/elevation', [ElevationController::class, 'forContract']);
});
