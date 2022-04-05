<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AdminController;
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
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TransactionController;
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
            "plans" => \App\Models\Plan::all(),
            "csrf_token" => csrf_token()
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

        Route::get('/charts/{type?}', [AdminController::class, 'charts'])->name('charts');

        // Load admin fruitbay
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
        Route::post('/update', 'store')->name('update');
        Route::get('/savings/get/{id?}/{planned?}', 'savings')->name('savings');
        Route::get('/charts/{type?}', 'charts')->name('charts');

        // Transactions Controller Routes
        Route::prefix('transactions')->name('transactions.')
        ->controller(TransactionController::class)
        ->group(function() {
            Route::get('/{transaction_id?}', 'index')->name('index');
            Route::get('/invoice/{transaction_id?}', 'invoice')->name('invoice');
            Route::get('/limit/{limit?}/{status?}', 'transactions')->name('limited');
        });

        // Savings Routes
        Route::prefix('savings')->name('savings.')->group(function() {
            // Savings Controller Routes
            Route::controller(SavingsController::class)
            ->group(function() {
                Route::get('/get-plans', 'plans');
                Route::get('/get-plans/{plan}', 'getPlan');
                Route::get('/get-plans/{plan}/foodbags/{id?}', 'getBags');
                Route::post('/activate-plan/{id}', 'store'); 
            });

            // Savings Controller Routes
            Route::controller(SubscriptionController::class)
            ->group(function() {
                Route::get('/subscriptions/data/{plan_id?}', 'dataTable')->name('data');
                Route::get('/subscriptions/{limit?}/{status?}', 'index');
                Route::post('/subscriptions/{limit?}/{status?}', 'index');
                Route::post('/update-bag/subscription/{subscription_id}/bag/{id}', 'updateBag');
                Route::get('/subscription/{subscription_id?}', 'subscription');
            });
        });
    });

    /**
     * Payment Routes
     */
    Route::controller(PaymentController::class)
    ->prefix('payment')->name('payment.')
    ->group(function() {
        Route::post('/initialize/fruit-bay', 'initializeFruitBay')->name('initialize.fruit.bay');
        Route::post('/initialize/savings', 'initializeSaving')->name('initialize.savings');
        Route::post('/paystack/webhook', 'paystackWebhook')->name('paystack.webhook');
    });
});

Route::get('/payment/paystack/verify/{type?}', [PaymentController::class, 'paystackVerify'])->name('payment.paystack.verify');

require __DIR__.'/auth.php';