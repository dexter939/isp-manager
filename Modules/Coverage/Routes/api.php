<?php

use IlluminateSupportFacadesRoute;
use ModulesCoverageHttpControllersFeasibilityController;
use ModulesCoverageHttpControllersImportStatusController;

Route::prefix('coverage')->middleware(['auth:sanctum', 'active'])->group(function () {

    Route::get('/feasibility', [FeasibilityController::class, 'check'])
        ->name('coverage.feasibility');

    Route::post('/normalize', [FeasibilityController::class, 'normalize'])
        ->name('coverage.normalize');

    Route::get('/map', [FeasibilityController::class, 'map'])
        ->name('coverage.map');

    Route::get('/import-status', [ImportStatusController::class, 'index'])
        ->name('coverage.import.status');

    Route::post('/import', [ImportStatusController::class, 'trigger'])
        ->name('coverage.import.trigger')
        ->middleware('role:admin|super_admin');
});
