<?php

/**
 * Payment Routes
 */

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentMethodAuthoriseController;
use Illuminate\Support\Facades\Route;

Route::controller(PaymentController::class)
    ->prefix('payment')->name('payment.')->middleware(['auth:sanctum'])
    ->group(function () {
        Route::post('/authorize/{method?}', [PaymentMethodAuthoriseController::class, 'store'])
            ->name('autorize.method');
        Route::get('/authorize/{method}/verify', [PaymentMethodAuthoriseController::class, 'show'])
            ->name('autorize.method.verify');
        Route::post('/initialize/fruit-bay/{method?}', 'initializeFruitBay')->name('initialize.fruit.bay');
        Route::post('/initialize/savings/{method?}', 'initializeSaving')->name('initialize.savings');
        Route::post('/paystack/webhook', 'paystackWebhook')->name('paystack.webhook');
        Route::delete('/terminate/transaction', 'terminateTransaction')->name('terminate.transaction');
    });

Route::get('/payment/verify/{type?}', [PaymentController::class, 'paystackVerify'])
    ->middleware(['auth:sanctum'])
    ->name('payment.verify');

Route::get('/payment/paystack/verify/{type?}', [PaymentController::class, 'paystackVerify'])
    ->name('payment.paystack.verify');
