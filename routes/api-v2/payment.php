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
        // New Authe endpoints
        Route::post('/authorize', [PaymentMethodAuthoriseController::class, 'store'])
            ->name('method.authorize');
        Route::put('/authorize', [PaymentMethodAuthoriseController::class, 'update'])
            ->name('method.verify');
        Route::delete('/authorize', [PaymentMethodAuthoriseController::class, 'delete'])
            ->name('method.delete');
    });
Route::post('/paystack/webhook', [PaymentController::class, 'paystackWebhook'])->name('paystack.webhook');
