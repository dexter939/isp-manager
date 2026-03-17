<?php

use Illuminate\Support\Facades\Route;
use Modules\Maintenance\PurchaseOrders\Http\Controllers\PurchaseOrderController;

Route::prefix('purchase-orders')->group(function () {
    Route::get('/', [PurchaseOrderController::class, 'index']);
    Route::post('/', [PurchaseOrderController::class, 'store']);
    Route::get('{purchaseOrder}', [PurchaseOrderController::class, 'show']);
    Route::post('{purchaseOrder}/send', [PurchaseOrderController::class, 'send']);
    Route::post('{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);
    Route::post('{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']);
    Route::get('reorder-rules', [PurchaseOrderController::class, 'reorderRules']);
    Route::post('reorder-rules', [PurchaseOrderController::class, 'storeReorderRule']);
    Route::get('suppliers', [PurchaseOrderController::class, 'suppliers']);
});
