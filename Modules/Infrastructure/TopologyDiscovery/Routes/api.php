<?php
use Illuminate\Support\Facades\Route;
use Modules\Infrastructure\TopologyDiscovery\Http\Controllers\DiscoveryController;
Route::middleware('auth:sanctum')->group(function () {
    Route::post('topology/discovery/run', [DiscoveryController::class, 'run']);
    Route::get('topology/discovery/runs', [DiscoveryController::class, 'runs']);
    Route::get('topology/discovery/candidates', [DiscoveryController::class, 'candidates']);
    Route::post('topology/discovery/candidates/{candidate}/confirm', [DiscoveryController::class, 'confirm']);
    Route::post('topology/discovery/candidates/{candidate}/reject', [DiscoveryController::class, 'reject']);
    Route::post('topology/discovery/candidates/bulk-confirm', [DiscoveryController::class, 'bulkConfirm']);
});
