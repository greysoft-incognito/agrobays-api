<?php

/**
 * Account Routes
 */

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SavingsController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\User\AccountController;
use App\Http\Controllers\User\WalletController;
use Illuminate\Support\Facades\Route;

Route::controller(AccountController::class)
    ->prefix('account')->name('account.')->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/update', 'store')->name('update');
        Route::post('/update/field/{identifier}', 'updateField')->name('update.field');
        Route::get('/savings/get/{id?}/{planned?}', 'savings')->name('savings');
        Route::get('/wallet', 'wallet')->name('wallet');
        Route::get('/charts/{type?}', 'charts')->name('charts');

        Route::match(['get', 'delete'], '/ping', 'ping')->name('ping');

        // Transactions Controller Routes
        Route::prefix('transactions')->name('transactions.')
            ->controller(TransactionController::class)
            ->group(function () {
                Route::get('/invoice/{transaction_id?}', 'invoice')->name('invoice');
                Route::get('/limit/{limit?}/{status?}', 'transactions')->name('limited');
            });

        // Wallet Controller Routes
        Route::prefix('wallet')->name('wallet.')
            ->controller(WalletController::class)
            ->group(function () {
                Route::apiResource('/', WalletController::class)
                    ->only(['index', 'store'])->parameters(['' => 'wallet']);
                Route::post('/send', 'send')->name('send');
            });

        // Notifications Controller Routes
        Route::prefix('notifications')->name('notifications.')
            ->controller(NotificationController::class)
            ->group(function () {
                Route::get('/{type?}', 'index')->name('index');
                Route::post('/mark/read', 'markAsRead')->name('mark.read');
            });

        // Orders Controller Routes
        Route::prefix('orders')->name('orders.')
            ->controller(OrderController::class)
            ->group(function () {
                Route::get('/dispatch/limit/{limit?}', 'dispatches');
                Route::get('/dispatch/{id?}', 'getDispatch');
                Route::get('/dispatch', 'dispatches');
                Route::get('/limit/{limit?}', 'index');
                Route::get('/{id}', 'getOrder');
                Route::get('/', 'index');
            });

        // Savings Routes
        Route::prefix('savings')->name('savings.')->group(function () {

            // Savings Controller Routes
            Route::controller(SubscriptionController::class)
                ->group(function () {
                    Route::get('/subscriptions/data/{plan_id?}', 'dataTable')->name('data');
                    Route::match(['GET', 'POST'], '/subscriptions', 'index');
                    Route::post('/update-bag/subscription/{subscription_id}/bag/{id}', 'updateBag');
                    Route::get('/subscription/{subscription_id?}', 'subscription');
                    Route::post('/subscription/{subscription}/automate', 'automate');
                });

            // Savings Controller Routes
            Route::controller(SavingsController::class)
                ->group(function () {
                    Route::get('/get-plans', 'plans');
                    Route::get('/get-plans/{plan}', 'getPlan');
                    Route::get('/get-plans/{plan}/foodbags/{id?}', 'getBags');
                    Route::get('{subscription?}', 'index')->name('index');
                    Route::post('/activate-plan/{id}', 'store');
                    Route::post('/terminate-plan', 'terminate');
                    Route::post('/cancel-plan', 'cancel');
                });
        });
    });
