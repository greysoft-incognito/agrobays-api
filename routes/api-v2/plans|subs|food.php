<?php

use App\Http\Controllers\v2\SubscriptionFoodbagController;
use App\Http\Controllers\v2\FoodController;
use App\Http\Controllers\v2\PlanController;
use App\Http\Controllers\v2\PlanFoodbagController;
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

Route::apiResource('foods', FoodController::class)->only(['index', 'show']);
Route::middleware(['auth:sanctum'])->name('subscriptions.')->prefix('subscriptions')->group(function () {
});


Route::middleware(['auth:sanctum'])->group(function () {
    // Subscriptions Foodbags Routes
    Route::apiResource('subscriptions/{subscription}/foodbags', SubscriptionFoodbagController::class)
        ->only(['store'])
        ->names('subscriptions.foodbags');

    // Plans Route
    Route::apiResource('plans', PlanController::class)
        ->only(['index', 'show', 'store']);

    // Plan Foodbags Route
    Route::apiResource('plans/{plan}/foodbags', PlanFoodbagController::class)
        ->only(['index', 'show']);
});
