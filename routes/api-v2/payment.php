<?php

/**
 * Payment Routes
 */

use App\Http\Controllers\v2\PaymentController;
use App\Http\Controllers\v2\User\PaymentMethodAuthoriseController;
use Illuminate\Support\Facades\Route;

Route::controller(PaymentController::class)
    ->prefix('payment')->name('payment.')->middleware(['auth:sanctum'])
    ->group(function () {
        Route::post('/authorize/{method?}', [PaymentMethodAuthoriseController::class, 'store'])
            ->name('autorize.method');
        Route::put('/authorize/{method}/verify', [PaymentMethodAuthoriseController::class, 'update'])
            ->name('autorize.method.verify');
    });
Route::post('/paystack/webhook', [PaymentController::class, 'paystackWebhook'])->name('paystack.webhook');
