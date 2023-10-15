<?php

/**
 * Account Routes
 */

use App\Http\Controllers\v2\Auth\AuthenticatedSessionController;
use App\Http\Controllers\v2\User\AccountController;
use App\Http\Controllers\v2\User\AffiliateController;
use App\Http\Controllers\v2\User\DispatchController;
use App\Http\Controllers\v2\User\OrderController;
use App\Http\Controllers\v2\User\SavingController;
use App\Http\Controllers\v2\User\SubscriptionController;
use App\Http\Controllers\v2\User\TransactionsController;
use App\Http\Controllers\v2\User\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('account')->name('account.')->middleware(['auth:sanctum'])
->group(function () {
    Route::get('ping', [AccountController::class, 'ping'])->name('ping');
    Route::get('dashboard', [AccountController::class, 'dashboard'])->name('dashboard');

    // Device Routes
    Route::get('devices', [AuthenticatedSessionController::class, 'getTokens'])->name('get.tokens');
    Route::delete('devices', [AuthenticatedSessionController::class, 'destroyTokens'])->name('destroy.tokens');

    // Subscription And Savings Routes
    Route::apiResource('subscriptions', SubscriptionController::class);
    Route::apiResource('subscriptions/{subscription}/savings', SavingController::class);

    // Transactions Route
    Route::apiResource('transactions', TransactionsController::class)->only(['index', 'show']);

    // Affiliates Route
    Route::apiResource('affiliates', AffiliateController::class)->only(['index', 'store']);

    // Wallet Routes
    Route::get('wallet/entries', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('wallet/transfer', [WalletController::class, 'send'])->name('wallet.send');

    // Orders Route
    Route::apiResource('orders', OrderController::class)->only(['index', 'show']);
    Route::apiResource('dispatched', DispatchController::class)->only(['index', 'show']);

    // Account Routes
    Route::apiResource('/', AccountController::class)
        ->only(['index', 'update', 'store', 'destroy'])
        ->parameter('', 'user');
});
