<?php
use Illuminate\Support\Facades\Route;
use Modules\Infrastructure\NetworkSites\Http\Controllers\NetworkSiteController;
Route::middleware('auth:sanctum')->group(function () {
    Route::get('network-sites/map', [NetworkSiteController::class, 'map']);
    Route::get('network-sites', [NetworkSiteController::class, 'index']);
    Route::post('network-sites', [NetworkSiteController::class, 'store']);
    Route::get('network-sites/{networkSite}', [NetworkSiteController::class, 'show']);
    Route::put('network-sites/{networkSite}', [NetworkSiteController::class, 'update']);
    Route::delete('network-sites/{networkSite}', [NetworkSiteController::class, 'destroy']);
    Route::get('network-sites/{networkSite}/stats', [NetworkSiteController::class, 'stats']);
    Route::get('network-sites/{networkSite}/hardware', [NetworkSiteController::class, 'hardware']);
    Route::post('network-sites/{networkSite}/hardware', [NetworkSiteController::class, 'linkHardware']);
    Route::get('network-sites/{networkSite}/customer-services', [NetworkSiteController::class, 'customerServices']);
    Route::post('network-sites/{networkSite}/customer-services/bulk', [NetworkSiteController::class, 'bulkLinkCustomerServices']);
});
