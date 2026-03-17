<?php

use Illuminate\Support\Facades\Route;
use Modules\Monitoring\Http\Controllers\LineTestController;
use Modules\Monitoring\Http\Controllers\NetworkAlertController;
use Modules\Monitoring\Http\Controllers\TR069Controller;

// GenieACS webhook (no auth)
Route::post('monitoring/tr069/inform', [TR069Controller::class, 'genieacsWebhook'])
    ->name('monitoring.tr069.inform')
    ->withoutMiddleware(['auth:sanctum']);

Route::middleware(['auth:sanctum'])->prefix('v1')->name('monitoring.')->group(function () {

    // ── Line Testing ──────────────────────────────────────────────────────────
    Route::get('line-tests',                           [LineTestController::class, 'index'])->name('line-tests.index');
    Route::post('contracts/{contract}/line-test',      [LineTestController::class, 'run'])->name('line-tests.run');
    Route::get('contracts/{contract}/line-tests',      [LineTestController::class, 'history'])->name('line-tests.history');

    // ── Network Alerts ────────────────────────────────────────────────────────
    Route::get('network-alerts',                                    [NetworkAlertController::class, 'index'])->name('alerts.index');
    Route::get('network-alerts/{alert}',                            [NetworkAlertController::class, 'show'])->name('alerts.show');
    Route::post('network-alerts/{alert}/acknowledge',               [NetworkAlertController::class, 'acknowledge'])->name('alerts.acknowledge');
    Route::post('network-alerts/{alert}/resolve',                   [NetworkAlertController::class, 'resolve'])->name('alerts.resolve');

    // ── TR-069 / GenieACS ─────────────────────────────────────────────────────
    Route::get('cpe/{device}/tr069/parameters',       [TR069Controller::class, 'parameters'])->name('tr069.parameters');
    Route::post('cpe/{device}/tr069/refresh',         [TR069Controller::class, 'refresh'])->name('tr069.refresh');
    Route::post('cpe/{device}/tr069/set',             [TR069Controller::class, 'setParameters'])->name('tr069.set');
    Route::post('cpe/{device}/tr069/reboot',          [TR069Controller::class, 'reboot'])->name('tr069.reboot');

});
