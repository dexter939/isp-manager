<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\PosteItaliane\Http\Controllers\PosteItalianeController;

Route::middleware(['auth:sanctum'])->prefix('poste')->group(function () {
    Route::get('bollettini', [PosteItalianeController::class, 'index']);
    Route::post('bollettini/generate', [PosteItalianeController::class, 'generate']);
    Route::get('bollettini/{id}/pdf', [PosteItalianeController::class, 'pdf']);
    Route::get('prisma/export', [PosteItalianeController::class, 'prismaExport']);
    Route::post('reconciliation/import', [PosteItalianeController::class, 'reconcile']);
});
