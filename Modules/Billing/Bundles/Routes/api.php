<?php
use Illuminate\Support\Facades\Route;
use Modules\Billing\Bundles\Http\Controllers\BundleController;
Route::middleware('auth:sanctum')->group(function () {
    Route::get('bundles', [BundleController::class, 'index']);
    Route::post('bundles', [BundleController::class, 'store']);
    Route::get('bundles/{plan}', [BundleController::class, 'show']);
    Route::put('bundles/{plan}', [BundleController::class, 'update']);
    Route::delete('bundles/{plan}', [BundleController::class, 'destroy']);
    Route::get('bundles/{plan}/discount', [BundleController::class, 'discount']);
    Route::post('bundles/subscriptions', [BundleController::class, 'subscribe']);
    Route::get('bundles/subscriptions/{subscription}', [BundleController::class, 'subscriptionShow']);
    Route::delete('bundles/subscriptions/{subscription}', [BundleController::class, 'terminateSubscription']);
});
