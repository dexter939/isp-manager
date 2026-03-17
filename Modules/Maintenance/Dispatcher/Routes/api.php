<?php
use Illuminate\Support\Facades\Route;
use Modules\Maintenance\Dispatcher\Http\Controllers\DispatcherController;
Route::middleware('auth:sanctum')->group(function () {
    Route::get('dispatcher/timeline/{date}', [DispatcherController::class, 'timeline']);
    Route::get('dispatcher/timeline/{date}/{userId}', [DispatcherController::class, 'technicianTimeline']);
    Route::post('dispatcher/assignments', [DispatcherController::class, 'store']);
    Route::put('dispatcher/assignments/{assignment}', [DispatcherController::class, 'update']);
    Route::delete('dispatcher/assignments/{assignment}', [DispatcherController::class, 'destroy']);
    Route::get('dispatcher/unassigned', [DispatcherController::class, 'unassigned']);
});
