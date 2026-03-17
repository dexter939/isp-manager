<?php
use Illuminate\Support\Facades\Route;
use Modules\Maintenance\OnCall\Http\Controllers\OnCallController;
Route::middleware('auth:sanctum')->group(function () {
    Route::get('oncall/current', [OnCallController::class, 'current']);
    Route::get('oncall/schedule/week/{date}', [OnCallController::class, 'weekSchedule']);
    Route::post('oncall/alerts/{alertId}/ack', [OnCallController::class, 'acknowledge']);
    Route::get('oncall/dispatches', [OnCallController::class, 'dispatches']);
    Route::get('oncall/schedules', [OnCallController::class, 'schedules']);
    Route::post('oncall/schedules', [OnCallController::class, 'storeSchedule']);
    Route::post('oncall/schedules/{schedule}/slots', [OnCallController::class, 'storeSlot']);
});
