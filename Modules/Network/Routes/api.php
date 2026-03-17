<?php

use Illuminate\Support\Facades\Route;
use Modules\Network\Http\Controllers\FloatingIpController;
use Modules\Network\Http\Controllers\ParentalControlController;
use Modules\Network\Http\Controllers\RadiusAccountingController;
use Modules\Network\Http\Controllers\RadiusUserController;

// ── RADIUS Accounting (FreeRADIUS rlm_rest — IP whitelist) ──────────────────
Route::post('network/radius/accounting', [RadiusAccountingController::class, 'handle'])
    ->name('network.radius.accounting')
    ->withoutMiddleware(['auth:sanctum'])
    ->middleware(['throttle:1000,1']);

Route::middleware(['auth:sanctum'])->prefix('v1')->name('network.')->group(function () {

    // ── Utenti RADIUS ─────────────────────────────────────────────────────────
    Route::get('radius/users',                        [RadiusUserController::class, 'index'])->name('radius.users.index');
    Route::get('radius/users/{radiusUser}',           [RadiusUserController::class, 'show'])->name('radius.users.show');
    Route::post('radius/users/{radiusUser}/suspend',  [RadiusUserController::class, 'suspend'])->name('radius.users.suspend');
    Route::post('radius/users/{radiusUser}/restore',  [RadiusUserController::class, 'restore'])->name('radius.users.restore');
    Route::get('radius/users/{radiusUser}/sessions',  [RadiusUserController::class, 'sessions'])->name('radius.users.sessions');

    // ── Floating IP ───────────────────────────────────────────────────────────
    Route::prefix('floating-ip')->name('floating-ip.')->group(function (): void {
        Route::get('/',                       [FloatingIpController::class, 'index'])->name('index');
        Route::post('/',                      [FloatingIpController::class, 'store'])->name('store');
        Route::get('/{id}',                   [FloatingIpController::class, 'show'])->name('show');
        Route::put('/{id}',                   [FloatingIpController::class, 'update'])->name('update');
        Route::delete('/{id}',                [FloatingIpController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/force-failover',   [FloatingIpController::class, 'forceFailover'])->name('force-failover');
        Route::post('/{id}/force-recovery',   [FloatingIpController::class, 'forceRecovery'])->name('force-recovery');
    });

    // ── Parental Control ──────────────────────────────────────────────────────
    Route::prefix('parental-control')->name('parental-control.')->group(function () {
        Route::get('/profiles',                 [ParentalControlController::class, 'profiles'])->name('profiles');
        Route::get('/subscriptions',            [ParentalControlController::class, 'subscriptions'])->name('subscriptions.index');
        Route::post('/subscriptions',           [ParentalControlController::class, 'subscribe'])->name('subscribe');
        Route::put('/subscriptions/{id}',       [ParentalControlController::class, 'update'])->name('update');
        Route::delete('/subscriptions/{id}',    [ParentalControlController::class, 'unsubscribe'])->name('unsubscribe');
        Route::get('/subscriptions/{id}/stats', [ParentalControlController::class, 'stats'])->name('stats');
    });

});
