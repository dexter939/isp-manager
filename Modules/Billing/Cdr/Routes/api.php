<?php
use Illuminate\Support\Facades\Route;
use Modules\Billing\Cdr\Http\Controllers\CdrController;
Route::middleware(['auth:sanctum'])->prefix('cdr')->group(function () {
    Route::get('records', [CdrController::class, 'index']);
    Route::post('import', [CdrController::class, 'importFile']);
    Route::get('import/{id}', [CdrController::class, 'importStatus']);
    Route::get('tariff-plans', [CdrController::class, 'tariffPlans']);
    Route::post('anagrafe/export', [CdrController::class, 'exportAnagrafe']);
});
