<?php
use Illuminate\Support\Facades\Route;
use Modules\Contracts\DuplicateChecker\Http\Controllers\DuplicateCheckerController;
Route::middleware('auth:sanctum')->group(function () {
    Route::get('customers/check-duplicate', [DuplicateCheckerController::class, 'check']);
});
