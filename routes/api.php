<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AdminFoodbagsController;
use App\Http\Controllers\Admin\AdminFoodsController;
use App\Http\Controllers\Admin\AdminFruitBayCategoryController;
use App\Http\Controllers\Admin\AdminFruitBayController;
use App\Http\Controllers\Admin\AdminPlansController;
use App\Http\Controllers\Admin\AdminSavingController;
use App\Http\Controllers\Admin\AdminSubscriptionController;
use App\Http\Controllers\Admin\AdminTransactionController;
use App\Http\Controllers\FruitBayController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SavingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('users')->name('users.')
->middleware(['auth:sanctum'])
->group(function() {

});

Route::get('/get/settings', function() {
    return response([
        'message' => "OK",
        'status' => "success",
        'response_code' => 200,
        'response' => [
            "settings" => collect(config("settings"))->except(['permissions', 'messages']),
            "fruitbay_categories" => \App\Models\FruitBayCategory::all(),
            "foodbags" => \App\Models\FoodBag::all(),
            "plans" => \App\Models\Plan::all()
        ],
    ]);
});

Route::middleware(['auth:sanctum'])->group(function() {
    /**
     * Admin Routes
     */
    Route::middleware(['admin'])
    ->prefix('admin')->name('admin.')
    ->group(function() {
        // Load admin food bay
        Route::controller(AdminFruitBayController::class)
        ->prefix('fruitbay')
        ->name('fruitbay.')
        ->group(function() {
            Route::get('/', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });

        // Admin food bay category
        Route::controller(AdminFruitBayCategoryController::class)
        ->prefix('categories/fruitbay')
        ->name('categories.fruitbay.')
        ->group(function() {
            Route::get('/', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });

        // Admin Plans
        Route::controller(AdminPlansController::class)
        ->prefix('savings/plans')
        ->name('savings.plan.')
        ->group(function() {
            Route::get('/', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });

        // Admin Foods
        Route::controller(AdminFoodsController::class)
        ->prefix('foodbags/foods')
        ->name('foodbags.foods')
        ->group(function() {
            Route::get('/', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });

        // Admin Food Bags
        Route::controller(AdminFoodbagsController::class)
        ->prefix('foodbags')
        ->name('foodbags.')
        ->group(function() {
            Route::get('/', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });

        // Admin Transactions
        Route::controller(AdminTransactionController::class)
        ->prefix('transactions')
        ->name('transactions.')
        ->group(function() {
            Route::get('/', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });

        // Admin Subscriptions
        Route::controller(AdminSubscriptionController::class)
        ->prefix('subscriptions')
        ->name('subscriptions.')
        ->group(function() {
            Route::get('/', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });

        // Admin Savings
        Route::controller(AdminSavingController::class)
        ->prefix('savings')
        ->name('savings.')
        ->group(function() {
            Route::get('/', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });
    });

    /**
     * Fruitbay Routes
     */
    Route::controller(FruitBayController::class)
    ->prefix('fruitbay')->name('fruitbay.')
    ->group(function() {
        Route::get('/', 'index');
        Route::get('/category/{category?}', 'index');
        Route::get('/categories/{item?}', 'categories');
        Route::get('/{item}', 'getItem');
        Route::post('/{item}/buy', 'buyItem');
    });

    /**
     * Account Routes
     */
    Route::controller(AccountController::class)
    ->prefix('account')->name('account.')
    ->group(function() {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'update')->name('store');
        Route::get('/transactions', 'transactions')->name('transactions');
        Route::get('/savings ', 'savings')->name('index');

        // Savings Route
        Route::controller(SavingsController::class)
        ->prefix('savings')->name('savings.')
        ->group(function() {
            Route::get('/get-plans', 'plans');
            Route::get('/get-plans/{plan}', 'getPlan');
            Route::get('/get-plans/{plan}/foodbags/{id?}', 'getBags');
            Route::match(['GET', 'POST'], '/subscription/{action?}', 'subscription');
            Route::post('/activate-plan/{id}', 'store');
            Route::post('/update-bag/{id}', 'updateBag');
        });
    });

    /**
     * Payment Routes
     */
    Route::controller(PaymentController::class)
    ->prefix('payment')->name('payment.')
    ->group(function() {
        Route::post('/initialize/{type?}', 'initialize')->name('initialize');
        Route::post('/paystack/webhook', 'paystackWebhook')->name('paystack.webhook');
    });
});

Route::get('/payment/paystack/verify', [PaymentController::class, 'paystackVerify'])->name('payment.paystack.verify');

require __DIR__.'/auth.php';