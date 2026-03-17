<?php

use Illuminate\Support\Facades\Route;
use Modules\Contracts\WizardMobile\Http\Controllers\WizardMobileController;

Route::middleware(['auth:sanctum'])->prefix('wizard')->group(function () {
    Route::post('sessions', [WizardMobileController::class, 'create']);
    Route::get('sessions/{uuid}', [WizardMobileController::class, 'show']);
    Route::put('sessions/{uuid}/steps/{step}', [WizardMobileController::class, 'saveStep']);
    Route::post('sessions/{uuid}/otp/send', [WizardMobileController::class, 'sendOtp']);
    Route::post('sessions/{uuid}/otp/verify', [WizardMobileController::class, 'verifyOtp']);
    Route::post('sessions/{uuid}/finalize', [WizardMobileController::class, 'finalize']);
    Route::delete('sessions/{uuid}', [WizardMobileController::class, 'abandon']);
});
