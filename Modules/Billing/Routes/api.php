<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\Http\Controllers\InvoiceController;
use Modules\Billing\Http\Controllers\PaymentController;
use Modules\Billing\Http\Controllers\PrepaidController;
use Modules\Billing\Http\Controllers\SepaMandateController;

// Stripe webhook (NO auth — firma HMAC Stripe)
Route::post('billing/stripe/webhook', [PaymentController::class, 'stripeWebhook'])
    ->name('billing.stripe.webhook')
    ->withoutMiddleware(['auth:sanctum']);

Route::middleware(['auth:sanctum'])->prefix('v1')->name('billing.')->group(function () {

    // ── Fatture ──────────────────────────────────────────────────────────────
    Route::apiResource('invoices', InvoiceController::class)
        ->only(['index', 'show', 'store']);

    Route::post('invoices/{invoice}/issue',     [InvoiceController::class, 'issue'])->name('invoices.issue');
    Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
    Route::post('invoices/{invoice}/cancel',    [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::get('invoices/{invoice}/pdf',        [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');

    // ── Pagamenti ─────────────────────────────────────────────────────────────
    Route::get('invoices/{invoice}/payments',         [PaymentController::class, 'index'])->name('invoices.payments.index');
    Route::post('invoices/{invoice}/payments/stripe', [PaymentController::class, 'initiateStripe'])->name('invoices.payments.stripe');

    // ── Mandati SEPA SDD ──────────────────────────────────────────────────────
    Route::get('customers/{customer}/sepa-mandates',          [SepaMandateController::class, 'index'])->name('customers.sepa-mandates.index');
    Route::post('customers/{customer}/sepa-mandates',         [SepaMandateController::class, 'store'])->name('customers.sepa-mandates.store');
    Route::post('sepa-mandates/{mandate}/revoke',             [SepaMandateController::class, 'revoke'])->name('sepa-mandates.revoke');

    // ── Prepaid ───────────────────────────────────────────────────────────────
    Route::prefix('prepaid')->name('prepaid.')->group(function () {
        Route::get('/wallets', [PrepaidController::class, 'wallets'])->name('wallets.index');
        Route::get('/wallets/{id}', [PrepaidController::class, 'walletShow'])->name('wallets.show');
        Route::get('/wallets/{id}/transactions', [PrepaidController::class, 'transactions'])->name('wallets.transactions');
        Route::get('/products', [PrepaidController::class, 'products'])->name('products.index');
        Route::post('/topup/initiate', [PrepaidController::class, 'initiateTopup'])->name('topup.initiate');
        Route::post('/topup/confirm', [PrepaidController::class, 'confirmTopup'])->name('topup.confirm');
        Route::get('/resellers/{id}/statement', [PrepaidController::class, 'resellerStatement'])->name('resellers.statement');
    });

});
