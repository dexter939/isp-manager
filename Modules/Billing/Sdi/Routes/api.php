<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\Sdi\Http\Controllers\SdiController;
use Modules\Billing\Sdi\Http\Controllers\SdiWebhookController;

Route::middleware(['auth:sanctum'])->prefix('sdi')->group(function () {
    Route::get('transmissions', [SdiController::class, 'index']);
    Route::post('transmit/{invoice}', [SdiController::class, 'transmit']);
    Route::get('transmissions/{id}', [SdiController::class, 'show']);
    Route::post('transmissions/{id}/retry', [SdiController::class, 'retry']);
});

// Webhook — no auth, HMAC validated internally
Route::post('sdi/webhook/aruba', [SdiWebhookController::class, 'handle']);
