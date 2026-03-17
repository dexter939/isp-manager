<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Contracts\Http\Controllers\ContractController;
use Modules\Contracts\Http\Controllers\CustomerController;
use Modules\Contracts\Http\Controllers\SignatureController;

/*
|--------------------------------------------------------------------------
| Contracts Module — API Routes
|--------------------------------------------------------------------------
|
| Prefix: /api/v1  (applicato nel ContractsServiceProvider)
| Middleware: auth:sanctum (applicato nei singoli controller)
|
*/

// ---- Customers ----
Route::prefix('customers')->name('customers.')->group(function () {
    Route::get('/',                    [CustomerController::class, 'index'])->name('index');
    Route::post('/',                   [CustomerController::class, 'store'])->name('store');
    Route::get('/{customer}',          [CustomerController::class, 'show'])->name('show');
    Route::put('/{customer}',          [CustomerController::class, 'update'])->name('update');
    Route::delete('/{customer}',       [CustomerController::class, 'destroy'])->name('destroy');
    Route::post('/{customer}/suspend', [CustomerController::class, 'suspend'])->name('suspend');
});

// ---- Service Plans ----
Route::middleware('auth:sanctum')
    ->get('/service-plans', function () {
        return response()->json(
            \Modules\Contracts\Models\ServicePlan::active()->public()
                ->where('tenant_id', request()->user()->tenant_id)
                ->get()
        );
    })->name('service-plans.index');

// ---- Contracts ----
Route::prefix('contracts')->name('contracts.')->group(function () {
    Route::get('/',                                    [ContractController::class, 'index'])->name('index');
    Route::post('/',                                   [ContractController::class, 'store'])->name('store');
    Route::get('/{contract}',                          [ContractController::class, 'show'])->name('show');
    Route::get('/{contract}/preview',                  [ContractController::class, 'preview'])->name('preview');
    Route::get('/{contract}/pdf',                      [ContractController::class, 'downloadPdf'])->name('pdf');
    Route::post('/{contract}/send-for-signature',      [ContractController::class, 'sendForSignature'])->name('send-for-signature');
    Route::post('/{contract}/resend-otp',              [ContractController::class, 'resendOtp'])->name('resend-otp');
    Route::post('/{contract}/terminate',               [ContractController::class, 'terminate'])->name('terminate');
});

// ---- Firma FEA — endpoint pubblico (no Sanctum) ----
Route::prefix('sign')->name('sign.')->group(function () {
    Route::get('/{contract}',  [SignatureController::class, 'showSignaturePage'])->name('page');
    Route::post('/{contract}', [SignatureController::class, 'verify'])->name('verify');
});
