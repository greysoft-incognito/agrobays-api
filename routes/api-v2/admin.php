<?php

use App\Http\Controllers\v2\Admin\AdminController;
use App\Http\Controllers\v2\Admin\FruitBayCategoryController;
use App\Http\Controllers\v2\Admin\FruitBayController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Fruitbay Route
    Route::apiResource('fruitbay/categories', FruitBayCategoryController::class);
    Route::apiResource('fruitbay', FruitBayController::class);
    Route::get('charts', [AdminController::class, 'charts']);
    Route::post('settings', [AdminController::class, 'saveSettings']);
});