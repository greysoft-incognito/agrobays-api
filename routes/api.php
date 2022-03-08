<?php

use App\Http\Controllers\Admin\AdminFruitBayCategoryController;
use App\Http\Controllers\Admin\AdminFruitBayController;
use App\Http\Controllers\FruitBayController;
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

Route::prefix('users')->name('users.')->middleware(['auth:sanctum'])->group(function() {

});

Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum'])->group(function() {
    Route::controller(AdminFruitBayController::class)
    ->prefix('fruitbay')
    ->name('fruitbay.')
    ->middleware(['auth:sanctum'])
    ->group(function() {
        Route::get('/', 'index');
        Route::get('/{item}', 'getItem');
        Route::post('/{item?}', 'store');
    });

    Route::controller(AdminFruitBayCategoryController::class)
    ->prefix('categories/fruitbay')
    ->name('categories.fruitbay.')
    ->middleware(['auth:sanctum'])
    ->group(function() {
        Route::get('/', 'index');
        Route::get('/{item}', 'getItem');
        Route::post('/{item?}', 'store');
    });
});

Route::controller(FruitBayController::class)->prefix('fruitbay')->name('fruitbay.')->middleware(['auth:sanctum'])->group(function() {
    Route::get('/', 'index');
    Route::get('/{item}', 'getItem');
});

require __DIR__.'/auth.php';