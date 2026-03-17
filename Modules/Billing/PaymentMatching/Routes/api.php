<?php
use Illuminate\Support\Facades\Route;
use Modules\Billing\PaymentMatching\Http\Controllers\PaymentMatchingController;
Route::middleware('auth:sanctum')->group(function () {
    Route::get('billing/matching-rules', [PaymentMatchingController::class, 'index']);
    Route::post('billing/matching-rules', [PaymentMatchingController::class, 'store']);
    Route::put('billing/matching-rules/{rule}', [PaymentMatchingController::class, 'update']);
    Route::delete('billing/matching-rules/{rule}', [PaymentMatchingController::class, 'destroy']);
    Route::post('billing/matching-rules/reorder', [PaymentMatchingController::class, 'reorder']);
    Route::post('billing/matching-rules/simulate', [PaymentMatchingController::class, 'simulate']);
    Route::patch('billing/matching-rules/{rule}/toggle', [PaymentMatchingController::class, 'toggle']);
});
