<?php

/**
 * Account Routes
 */

use App\Http\Controllers\v2\Vendor\VendorController;
use App\Http\Controllers\v2\Vendor\VerificationController;
use App\Http\Controllers\v2\Vendor\CatalogController;
use App\Http\Controllers\v2\Vendor\DispatchController;
use Illuminate\Support\Facades\Route;

Route::prefix('vendor')->name('vendor.')->middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('/', VendorController::class)->parameter('', 'vendor')->only(['index']);
    Route::apiResource('/catalog', CatalogController::class);
    Route::apiResource('/verify', VerificationController::class)->only(['store', 'update']);
    Route::apiResource('dispatched', DispatchController::class)->only(['index', 'show', 'update']);
});