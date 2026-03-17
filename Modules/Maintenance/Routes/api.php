<?php

use Illuminate\Support\Facades\Route;
use Modules\Maintenance\Http\Controllers\HardwareAssetController;
use Modules\Maintenance\Http\Controllers\InventoryController;
use Modules\Maintenance\Http\Controllers\TicketController;

Route::middleware(['auth:sanctum'])->prefix('v1')->name('maintenance.')->group(function () {

    // ── Trouble Tickets ───────────────────────────────────────────────────────
    Route::get('tickets',                              [TicketController::class, 'index'])->name('tickets.index');
    Route::post('tickets',                             [TicketController::class, 'store'])->name('tickets.store');
    Route::get('tickets/{ticket}',                     [TicketController::class, 'show'])->name('tickets.show');
    Route::post('tickets/{ticket}/assign',             [TicketController::class, 'assign'])->name('tickets.assign');
    Route::post('tickets/{ticket}/transition',         [TicketController::class, 'transition'])->name('tickets.transition');
    Route::post('tickets/{ticket}/resolve',            [TicketController::class, 'resolve'])->name('tickets.resolve');
    Route::post('tickets/{ticket}/notes',              [TicketController::class, 'addNote'])->name('tickets.notes.store');

    // ── Hardware Assets ───────────────────────────────────────────────────────
    Route::get('hardware',                             [HardwareAssetController::class, 'index'])->name('hardware.index');
    Route::post('hardware',                            [HardwareAssetController::class, 'register'])->name('hardware.register');
    Route::get('hardware/unreturned',                  [HardwareAssetController::class, 'unreturned'])->name('hardware.unreturned');
    Route::get('hardware/stock-summary',               [HardwareAssetController::class, 'stockSummary'])->name('hardware.stock-summary');
    Route::post('hardware/scan-qr',                    [HardwareAssetController::class, 'scanQr'])->name('hardware.scan-qr');
    Route::get('hardware/{asset}',                     [HardwareAssetController::class, 'show'])->name('hardware.show');
    Route::post('hardware/{asset}/assign',             [HardwareAssetController::class, 'assign'])->name('hardware.assign');
    Route::post('hardware/{asset}/return',             [HardwareAssetController::class, 'return'])->name('hardware.return');

    // ── Inventory ─────────────────────────────────────────────────────────────
    Route::get('inventory',                            [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory',                           [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('inventory/low-stock',                  [InventoryController::class, 'lowStock'])->name('inventory.low-stock');
    Route::get('inventory/{item}',                     [InventoryController::class, 'show'])->name('inventory.show');
    Route::post('inventory/{item}/receive',            [InventoryController::class, 'receive'])->name('inventory.receive');
    Route::post('inventory/{item}/consume',            [InventoryController::class, 'consume'])->name('inventory.consume');
    Route::post('inventory/{item}/adjust',             [InventoryController::class, 'adjust'])->name('inventory.adjust');
});
