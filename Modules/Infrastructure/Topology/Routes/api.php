<?php
use Illuminate\Support\Facades\Route;
use Modules\Infrastructure\Topology\Http\Controllers\TopologyController;
Route::middleware('auth:sanctum')->group(function () {
    Route::get('topology/sites/{siteId}/graph', [TopologyController::class, 'graphForSite']);
    Route::get('topology/full', [TopologyController::class, 'fullGraph']);
    Route::post('topology/links', [TopologyController::class, 'storeLink']);
    Route::put('topology/links/{link}', [TopologyController::class, 'updateLink']);
    Route::delete('topology/links/{link}', [TopologyController::class, 'destroyLink']);
    Route::get('topology/devices/{deviceId}/impact', [TopologyController::class, 'deviceImpact']);
});
