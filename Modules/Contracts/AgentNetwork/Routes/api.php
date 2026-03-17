<?php

use Illuminate\Support\Facades\Route;
use Modules\Contracts\AgentNetwork\Http\Controllers\AgentAdminController;
use Modules\Contracts\AgentNetwork\Http\Controllers\AgentPortalController;

Route::middleware(['auth:sanctum'])->group(function () {
    // Agent portal (own data)
    Route::prefix('agents/me')->group(function () {
        Route::get('/', [AgentPortalController::class, 'me']);
        Route::get('commissions', [AgentPortalController::class, 'myCommissions']);
        Route::get('liquidations', [AgentPortalController::class, 'myLiquidations']);
        Route::get('contracts', [AgentPortalController::class, 'myContracts']);
    });

    // Admin
    Route::prefix('admin/agents')->group(function () {
        Route::get('/', [AgentAdminController::class, 'index']);
        Route::post('/', [AgentAdminController::class, 'store']);
        Route::get('{id}', [AgentAdminController::class, 'show']);
        Route::put('{id}', [AgentAdminController::class, 'update']);
    });

    Route::prefix('admin/liquidations')->group(function () {
        Route::get('/', [AgentAdminController::class, 'liquidations']);
        Route::post('{id}/approve', [AgentAdminController::class, 'approveLiquidation']);
        Route::post('{id}/mark-paid', [AgentAdminController::class, 'markLiquidationPaid']);
    });
});
