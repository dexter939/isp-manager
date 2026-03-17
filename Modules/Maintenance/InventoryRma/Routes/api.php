<?php

use Illuminate\Support\Facades\Route;
use Modules\Maintenance\InventoryRma\Http\Controllers\InventoryRmaController;

Route::prefix('inventory')->group(function () {
    Route::get('models', [InventoryRmaController::class, 'models']);
    Route::post('models', [InventoryRmaController::class, 'createModel']);
    Route::post('items/{item}/deploy', [InventoryRmaController::class, 'deploy']);
    Route::post('items/{item}/rma', [InventoryRmaController::class, 'openRma']);
    Route::post('rma/{rma}/resolve', [InventoryRmaController::class, 'resolveRma']);
    Route::get('reports/defect-rate', [InventoryRmaController::class, 'defectRate']);
    Route::get('reports/rma-status', [InventoryRmaController::class, 'rmaStatus']);
    Route::get('reports/stock-levels', [InventoryRmaController::class, 'stockLevels']);
});
