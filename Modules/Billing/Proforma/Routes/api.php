<?php
use Illuminate\Support\Facades\Route;
use Modules\Billing\Proforma\Http\Controllers\ProformaController;
Route::middleware('auth:sanctum')->group(function () {
    Route::get('invoices/proformas', [ProformaController::class, 'index']);
    Route::post('invoices/{id}/convert-to-invoice', [ProformaController::class, 'convert']);
});
