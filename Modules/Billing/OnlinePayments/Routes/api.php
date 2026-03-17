<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\OnlinePayments\Http\Controllers\NexiCallbackController;
use Modules\Billing\OnlinePayments\Http\Controllers\OnlinePaymentsController;
use Modules\Billing\OnlinePayments\Http\Controllers\StripeWebhookController;

Route::middleware(['auth:sanctum'])->prefix('payments')->group(function () {
    Route::get('methods', [OnlinePaymentsController::class, 'methods']);
    Route::post('link/{invoice}', [OnlinePaymentsController::class, 'createLink']);
    Route::post('setup', [OnlinePaymentsController::class, 'setup']);
    Route::post('charge/{method}', [OnlinePaymentsController::class, 'charge']);
    Route::delete('methods/{method}', [OnlinePaymentsController::class, 'deactivateMethod']);
});

// Webhooks — no auth
Route::post('payments/stripe/webhook', [StripeWebhookController::class, 'handle']);
Route::post('payments/nexi/callback', [NexiCallbackController::class, 'handle']);
