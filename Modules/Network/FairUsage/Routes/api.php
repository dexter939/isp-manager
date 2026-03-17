<?php
use Illuminate\Support\Facades\Route;
use Modules\Network\FairUsage\Http\Controllers\FupController;
Route::middleware('auth:sanctum')->group(function () {
    Route::get('fup/usage/{accountId}', [FupController::class, 'usage']);
    Route::get('fup/topup-products', [FupController::class, 'products']);
    Route::post('fup/topup', [FupController::class, 'topup']);
    Route::get('customer-portal/fup/usage', [FupController::class, 'usage']);
});
