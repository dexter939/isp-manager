<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\HealthController;
use Modules\Core\Http\Controllers\ApiQuotaController;

/*
|--------------------------------------------------------------------------
| Core API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/health', [HealthController::class, 'index'])->name('core.health');

    Route::middleware(['role:admin|super-admin'])->group(function () {
        Route::get('/quota/status', [ApiQuotaController::class, 'status'])->name('core.quota.status');
        Route::post('/quota/reset/{carrier}/{callType}', [ApiQuotaController::class, 'reset'])->name('core.quota.reset');
    });
});
