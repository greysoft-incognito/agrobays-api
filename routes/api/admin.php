<?php

/**
 * Admin Routes
 */

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminFoodbagsController;
use App\Http\Controllers\Admin\AdminFoodsController;
use App\Http\Controllers\Admin\AdminFrontContentController;
use App\Http\Controllers\Admin\AdminFruitBayCategoryController;
use App\Http\Controllers\Admin\AdminFruitBayController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminPlansController;
use App\Http\Controllers\Admin\AdminSavingController;
use App\Http\Controllers\Admin\AdminSubscriptionController;
use App\Http\Controllers\Admin\AdminTransactionController;
use App\Http\Controllers\Admin\DispatchController;
use App\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Admin\MealPlanController as AdminMealPlanController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/charts/{type?}', [AdminController::class, 'charts'])->name('charts');
    Route::post('/config', [AdminController::class, 'saveSettings'])->name('config');

    Route::put('feedbacks/status', [AdminFeedbackController::class, 'status'])->name('feedbacks.status');
    Route::apiResource('feedbacks', AdminFeedbackController::class)->middleware('auth:sanctum');

    Route::controller(AdminController::class)
        ->prefix('users')
        ->name('users.')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/user/{id}', 'getItem');
        });

    // Admin Front Content
    Route::controller(AdminFrontContentController::class)
        ->prefix('front/content')
        ->name('front.content.')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/list/{limit?}/{type?}', 'index');
            Route::get('/{item}', 'getContent');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });

    // Admin Users
    Route::controller(UsersController::class)
        ->prefix('users')
        ->name('users.')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/list/{limit?}/{role?}', 'index');
            // Route::get('/{id}', 'getUser');
            Route::get('/{user}', 'show');
            Route::post('/{id?}', 'storeLegacy');
            Route::put('/{user?}', 'store');
            Route::put('/{user}/password', 'updatePassword');
            Route::delete('/{id?}', 'destroy');
        });

    // Admin Dispatch
    Route::controller(DispatchController::class)
        ->prefix('dispatch')
        ->name('dispatch.')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/list/{limit?}/{role?}', 'index');
            Route::get('/{id}', 'getDispatch');
            Route::post('/update-status', 'setStatus');
            Route::post('/{id?}', 'store');
            Route::delete('/{id?}', 'destroy');
        });

    // Load admin fruitbay
    Route::controller(AdminFruitBayController::class)
        ->prefix('fruitbay')
        ->name('fruitbay.')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/limit/{limit?}', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });

    // Admin food bay category
    Route::controller(AdminFruitBayCategoryController::class)
        ->prefix('categories/fruitbay')
        ->name('categories.fruitbay.')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/limit/{limit?}', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });

    // Admin Plans
    Route::controller(AdminPlansController::class)
        ->prefix('savings/plans')
        ->name('savings.plan.')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/limit/{limit?}', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });

    // Admin Foods
    Route::controller(AdminFoodsController::class)
        ->prefix('foodbags/foods')
        ->name('foodbags.foods')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/limit/{limit?}', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });

    // Admin Food Bags
    Route::controller(AdminFoodbagsController::class)
        ->prefix('foodbags')
        ->name('foodbags.')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/limit/{limit?}', 'index');
            Route::get('/{item}', 'getItem');
            Route::put('/{item}/foods', 'putFood');
            Route::delete('/{item}/foods/{food}', 'removeFood');
            Route::post('/{item?}', 'store');
            Route::delete('/{item}', 'destroy');
        });

    // Admin Transactions
    Route::controller(AdminTransactionController::class)
        ->prefix('transactions')
        ->name('transactions.')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/limit/{limit?}', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });

    // Admin Subscriptions
    Route::controller(AdminSubscriptionController::class)
        ->prefix('subscriptions')
        ->name('subscriptions.')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/limit/{limit?}', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });

    // Admin Orders
    Route::controller(AdminOrderController::class)
        ->prefix('orders')
        ->name('orders.')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/limit/{limit?}', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });

    // Admin Savings
    Route::controller(AdminSavingController::class)
        ->prefix('savings')
        ->name('savings.')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/limit/{limit?}', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });

    /**
     * Admin Meal Plan Routes
     */
    Route::controller(AdminMealPlanController::class)
        ->prefix('meal-plans')->name('meal.plans.')
        ->group(function () {
            Route::apiResource('/', AdminMealPlanController::class)->parameters(['' => 'meal_plan']);
        });
});
