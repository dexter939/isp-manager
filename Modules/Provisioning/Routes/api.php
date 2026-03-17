<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Provisioning\Http\Controllers\FiberCopWebhookController;
use Modules\Provisioning\Http\Controllers\OpenFiberWebhookController;
use Modules\Provisioning\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| Provisioning Module — API Routes
|--------------------------------------------------------------------------
*/

// ---- Ordini (richiede autenticazione) ----
Route::middleware('auth:sanctum')->prefix('orders')->name('orders.')->group(function () {
    Route::get('/',                    [OrderController::class, 'index'])->name('index');
    Route::post('/',                   [OrderController::class, 'store'])->name('store');
    Route::get('/{order}',             [OrderController::class, 'show'])->name('show');
    Route::post('/{order}/send',       [OrderController::class, 'send'])->name('send');
    Route::post('/{order}/reschedule', [OrderController::class, 'reschedule'])->name('reschedule');
    Route::post('/{order}/cancel',     [OrderController::class, 'cancel'])->name('cancel');
    Route::post('/{order}/unsuspend',  [OrderController::class, 'unsuspend'])->name('unsuspend');
    Route::get('/{order}/events',      [OrderController::class, 'events'])->name('events');
});

// ---- Webhook inbound carrier (NO Sanctum, IP whitelist via middleware) ----
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/openfiber', [OpenFiberWebhookController::class, 'handle'])
        ->middleware('carrier.whitelist:openfiber')
        ->name('openfiber');

    Route::post('/fibercop', [FiberCopWebhookController::class, 'handle'])
        ->middleware('carrier.whitelist:fibercop')
        ->name('fibercop');
});
