<?php
use Illuminate\Support\Facades\Route;
use Modules\Maintenance\FieldService\Http\Controllers\FieldServiceController;
use Modules\Maintenance\FieldService\Http\Controllers\TechnicianController;

Route::middleware(['auth:sanctum'])->prefix('field')->group(function () {
    Route::get('interventions', [FieldServiceController::class, 'index']);
    Route::post('interventions', [FieldServiceController::class, 'store']);
    Route::get('interventions/{uuid}', [FieldServiceController::class, 'show']);
    Route::put('interventions/{uuid}/start', [FieldServiceController::class, 'start']);
    Route::post('interventions/{uuid}/activities', [FieldServiceController::class, 'addActivity']);
    Route::post('interventions/{uuid}/materials', [FieldServiceController::class, 'addMaterial']);
    Route::post('interventions/{uuid}/photos', [FieldServiceController::class, 'uploadPhoto']);
    Route::post('interventions/{uuid}/complete', [FieldServiceController::class, 'complete']);
    Route::get('interventions/{uuid}/verbale', [FieldServiceController::class, 'verbale']);
    Route::post('interventions/{uuid}/sign/otp', [FieldServiceController::class, 'sendOtp']);
    Route::post('interventions/{uuid}/sign', [FieldServiceController::class, 'sign']);

    Route::post('technicians/position', [TechnicianController::class, 'updatePosition']);
    Route::get('technicians/positions', [TechnicianController::class, 'positions']);
});
