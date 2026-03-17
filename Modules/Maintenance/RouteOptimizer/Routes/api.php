<?php
use Illuminate\Support\Facades\Route;
use Modules\Maintenance\RouteOptimizer\Http\Controllers\RouteOptimizerController;
Route::middleware('auth:sanctum')->group(function () {
    Route::post('routes/optimize', [RouteOptimizerController::class, 'optimize']);
    Route::get('routes/plans/{date}/{userId}', [RouteOptimizerController::class, 'plan']);
    Route::put('routes/plans/{plan}/reorder', [RouteOptimizerController::class, 'reorder']);
    Route::get('routes/plans/{plan}/directions', [RouteOptimizerController::class, 'directions']);
});
