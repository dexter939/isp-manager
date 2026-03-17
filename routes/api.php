<?php

// Top-level API routes file.
// All module API routes are registered by their own ServiceProvider
// via nwidart/laravel-modules (see each Modules/*/Routes/api.php).
// This file only defines routes that don't belong to any module.

use Illuminate\Support\Facades\Route;

Route::get('/health', fn() => response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]))
    ->name('api.health');
