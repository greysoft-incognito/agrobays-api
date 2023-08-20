<?php

use App\Http\Controllers\Admin\Cooperative\CooperativeController as AdminCooperativeController;
use App\Http\Controllers\Admin\Cooperative\CooperativeMemberController as AdminCooperativeMemberController;
use App\Http\Controllers\Admin\Cooperative\CooperativeMgtController as AdminCooperativeMgtController;
use App\Http\Controllers\Cooperative\CooperativeController;
use App\Http\Controllers\Cooperative\CooperativeSubscriptionController;
use App\Http\Controllers\Cooperative\CooperativeMgtController;
use App\Http\Controllers\Cooperative\CooperativeMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('cooperatives')->name('cooperatives.')
->controller(CooperativeController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{cooperative}', 'show')->name('show');

    Route::controller(CooperativeMgtController::class)->group(function () {
        Route::get('/{cooperative}/wallet', 'wallet')->name('wallet');
        Route::post('/', 'store')->name('store');
        Route::post('/{cooperative}/photos', 'photos')->name('photos');
        Route::put('/{cooperative}', 'update')->name('update');
        Route::delete('/{cooperative}', 'destroy')->name('delete');
    });

    Route::name('subscriptions.')->prefix('{cooperative}/subscriptions')
        ->controller(CooperativeSubscriptionController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{subscription}/owners', 'owners')->name('owners');
            Route::put('/{subscription}/owners/{owner_bag_id}', 'approveFoodbag')->name('owners');
        });

    Route::name('members.')->prefix('{cooperative}')->controller(CooperativeMemberController::class)->group(function () {
        Route::get('members', 'index')->name('index');
        Route::put('members/{member}', 'update')->name('update');
        Route::delete('members/{member}', 'destroy')->name('destroy');
        Route::post('invitations', 'invitations')->name('invitations');
        Route::put('invitations/{status}', 'invitationsStatus')->name('invitations.status'); //Accept Or Reject
        Route::put('invitations/{member}/request/{status}', 'appprove')->name('appprove.request'); //Approve Or Decline
    });
});


Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin/cooperatives')
    ->name('admin.cooperatives')
    ->controller(AdminCooperativeMgtController::class)->group(function () {
        Route::get('/', [AdminCooperativeController::class, 'index'])->name('index');
        Route::get('/{cooperative}', [AdminCooperativeController::class, 'show'])->name('show');
        Route::put('/{cooperative}', 'update')->name('update');
        Route::delete('/{cooperative}', 'destroy')->name('destroy');
        Route::put('/{cooperative}/toggle/verification', 'toggleVerification')->name('toggle.verification');
        Route::put('/{cooperative}/wallet/topup', 'walletTopup')->name('wallet.topup');

        Route::controller(AdminCooperativeMemberController::class)->group(function () {
            Route::get('/{cooperative}/members', 'index')->name('index');
            Route::delete('/{cooperative}/members/{member}', 'destroy')->name('destroy');
        });
    });