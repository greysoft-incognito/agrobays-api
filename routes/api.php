<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AdminFruitBayCategoryController;
use App\Http\Controllers\Admin\AdminFruitBayController;
use App\Http\Controllers\Admin\AdminPlansController;
use App\Http\Controllers\FruitBayController;
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
            "settings" => collect(config("settings"))->except(['permissions', 'messages'])
        ],
    ]);
});

Route::middleware(['auth:sanctum'])->group(function() {
    Route::prefix('admin')->name('admin.')
    ->group(function() {
        Route::controller(AdminFruitBayController::class)
        ->prefix('fruitbay')
        ->name('fruitbay.')
        ->group(function() {
            Route::get('/', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });

        Route::controller(AdminFruitBayCategoryController::class)
        ->prefix('categories/fruitbay')
        ->name('categories.fruitbay.')
        ->group(function() {
            Route::get('/', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });

        Route::controller(AdminPlansController::class)
        ->prefix('savings/plans')
        ->name('savings.plan.')
        ->group(function() {
            Route::get('/', 'index');
            Route::get('/{item}', 'getItem');
            Route::post('/{item?}', 'store');
            Route::delete('/{item?}', 'destroy');
        });
    });

    Route::controller(FruitBayController::class)
    ->prefix('fruitbay')->name('fruitbay.')
    ->group(function() {
        Route::get('/', 'index');
        Route::get('/category/{category?}', 'index');
        Route::get('/categories/{item?}', 'categories');
        Route::get('/{item}', 'getItem');
        Route::post('/{item}/buy', 'buyItem');
    });

    Route::controller(AccountController::class)
    ->prefix('account')->name('account.')
    ->group(function() {
        Route::get('/', 'index')->name('index');
        Route::get('/transactions', 'transactions')->name('transactions');
        Route::get('/savings ', 'savings')->name('index');
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

});

require __DIR__.'/auth.php';