<?php

use App\Http\Controllers\v2\Admin\AdminController;
use App\Http\Controllers\v2\Admin\DeliverableNotificationController;
use App\Http\Controllers\v2\Admin\DispatchController;
use App\Http\Controllers\v2\Admin\FoodbagController;
use App\Http\Controllers\v2\Admin\FoodController;
use App\Http\Controllers\v2\Admin\FruitBayCategoryController;
use App\Http\Controllers\v2\Admin\FruitBayController;
use App\Http\Controllers\v2\Admin\OrderController;
use App\Http\Controllers\v2\Admin\Users\UsersController;
use App\Http\Controllers\v2\Admin\Users\SubscriptionController;
use App\Http\Controllers\v2\Admin\Users\SavingController;
use App\Http\Controllers\v2\Admin\Users\TransactionsController;
use App\Http\Controllers\v2\Admin\Users\OrderController as UserOrderController;
use App\Http\Controllers\v2\Admin\Users\DispatchController as UserDispatchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Fruitbay Route
    Route::apiResource('fruitbay/categories', FruitBayCategoryController::class);
    Route::apiResource('fruitbay', FruitBayController::class);
    Route::apiResource('foodbags', FoodbagController::class);
    Route::apiResource('foods', FoodController::class);
    Route::get('charts', [AdminController::class, 'charts']);
    Route::post('settings', [AdminController::class, 'saveSettings']);

    // Orders Route
    Route::apiResource('orders', OrderController::class)->only(['index', 'show']);
    Route::apiResource('dispatched', DispatchController::class)->only(['index', 'show', 'update']);

    Route::apiResource('deliverables', DeliverableNotificationController::class);

    // Users Routes
    Route::apiResource('users', UsersController::class);
    Route::prefix('users/{user}')->name('users.')->group(function () {
        Route::put('wallet', [UsersController::class, 'fund']);

        // Orders Route
        Route::apiResource('orders', UserOrderController::class)->only(['index', 'show']);
        Route::apiResource('dispatched', UserDispatchController::class)->only(['index', 'show']);

        // Transactions Route
        Route::apiResource('transactions', TransactionsController::class)->only(['index', 'show']);

        // Subscription And Savings Routes
        Route::apiResource('subscriptions', SubscriptionController::class);
        Route::apiResource('subscriptions/{subscription}/savings', SavingController::class);
    });
});
