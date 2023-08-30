<?php

use App\Http\Controllers\Controller;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FrontContentController;
use App\Http\Controllers\FruitBayController;
use App\Http\Controllers\MealPlanController;
use App\Http\Controllers\MealTimetableController;
use App\Http\Controllers\UserController;
use App\Models\Dispatch;
use Illuminate\Support\Facades\File;
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

// Load Extra Routes
if (file_exists(base_path('routes/api'))) {
    array_filter(File::files(base_path('routes/api')), function (Symfony\Component\Finder\SplFileInfo $file) {
        if ($file->getExtension() === 'php') {
            Route::middleware('api')->group($file->getPathName());
        }
    });
}

Route::apiResource('feedbacks', FeedbackController::class)
    ->middleware('auth:sanctum')->except(['destroy']);

Route::get('/track/order/{reference?}', function ($reference = null) {
    $order = Dispatch::whereReference($reference)->where('status', '!=', 'delivered')->first();

    return (new Controller())->buildResponse([
        'message' => $order ? 'OK' : 'Invalid tracking code',
        'status' => $order ? 'success' : 'info',
        'response_code' => $order ? 200 : 404,
        'order' => $order ? $order->only(['id', 'last_location', 'status']) : [],
    ]);
});

// Front Content
Route::controller(FrontContentController::class)
    ->prefix('front/content')
    ->name('front.content.')
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/list/{limit?}/{type?}', 'index');
        Route::get('/type/{type?}', 'index');
        Route::get('/type/{type?}/limit/{limit?}', 'index');
        Route::get('/{item}/{type?}', 'getContent');
    });

Route::middleware(['auth:sanctum'])->group(function () {

    /**
     * Fruitbay Routes
     */
    Route::controller(FruitBayController::class)
        ->prefix('fruitbay')->name('fruitbay.')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/search', 'search');
            Route::get('/category/{category?}', 'index');
            Route::get('/categories/{item?}', 'categories');
            Route::get('/{item}', 'getItem');
            Route::post('/{item}/buy', 'buyItem');
        });

    /**
     * Meal Plan Routes
     */
    Route::controller(MealPlanController::class)
        ->prefix('meal-plans')->name('meal.plans.')
        ->group(function () {
            Route::post('/favorite/{plan}', 'toggleFavorite')->name('toggle.favorite')->middleware('auth:sanctum');
            Route::apiResource('/', MealPlanController::class)->parameters(['' => 'meal_plan']);
        });

    /**
     * Meal Timetable Routes
     */
    Route::controller(MealTimetableController::class)
        ->prefix('meal-timetable')->name('meal.timetable.')
        ->group(function () {
            Route::apiResource('/', MealTimetableController::class)->parameters(['' => 'meal_timetable']);
        });

    /**
     * User Routes
     */
    Route::apiResource('users', UserController::class)->only(['index', 'show']);
});
