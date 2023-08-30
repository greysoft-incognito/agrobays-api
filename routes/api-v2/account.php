<?php

/**
 * Account Routes
 */

use App\Http\Controllers\v2\User\AccountController;
use App\Http\Controllers\v2\User\SavingController;
use App\Http\Controllers\v2\User\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('account')->name('account.')->middleware(['auth:sanctum'])
->group(function () {
        // Account Routes
        Route::apiResource('/', AccountController::class)->only(['index', 'update', 'store'])->parameter('', 'user');
        Route::get('ping', [AccountController::class, 'ping'])->name('ping');
        Route::get('dashboard', [AccountController::class, 'dashboard'])->name('dashboard');

        // Subscription Route
        Route::apiResource('subscriptions', SubscriptionController::class);
        Route::apiResource('subscriptions/{subscription}/savings', SavingController::class);
});
