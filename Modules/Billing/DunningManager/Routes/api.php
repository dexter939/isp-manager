<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\DunningManager\Http\Controllers\DunningController;

Route::middleware(['auth:sanctum'])->prefix('dunning')->group(function () {
    Route::get('cases', [DunningController::class, 'index']);
    Route::get('cases/{id}', [DunningController::class, 'show']);
    Route::post('cases/{id}/resolve', [DunningController::class, 'resolve']);
    Route::get('policies', [DunningController::class, 'policies']);
    Route::post('policies', [DunningController::class, 'storePolicy']);
    Route::put('policies/{id}', [DunningController::class, 'updatePolicy']);
    Route::get('whitelist', [DunningController::class, 'whitelist']);
    Route::post('whitelist', [DunningController::class, 'addToWhitelist']);
});
