<?php
use Illuminate\Support\Facades\Route;
use Modules\Billing\CustomerBalance\Http\Controllers\BalanceController;
Route::middleware('auth:sanctum')->group(function () {
    Route::get('customers/{customerId}/balance', [BalanceController::class, 'show']);
    Route::get('customers/{customerId}/balance/statement', [BalanceController::class, 'statement']);
    Route::post('customers/{customerId}/balance/opening', [BalanceController::class, 'setOpening']);
    Route::post('customers/{customerId}/balance/adjust', [BalanceController::class, 'adjust']);
});
