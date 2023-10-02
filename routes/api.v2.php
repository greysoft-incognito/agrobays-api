<?php

use App\Http\Controllers\v2\FruitBayCategoryController;
use App\Http\Controllers\v2\FruitBayController;
use App\Http\Controllers\v2\PlanController;
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
Route::middleware('api')->prefix('legacy')->name('legacy.')->group(base_path('routes/api.php'));

// Load Extra Routes
if (file_exists(base_path('routes/api'))) {
    array_filter(File::files(base_path('routes/api-v2')), function (Symfony\Component\Finder\SplFileInfo $file) {
        if ($file->getExtension() === 'php') {
            Route::middleware('api')->group($file->getPathName());
        }
    });
}

Route::apiResource('fruitbay/categories', FruitBayCategoryController::class)->only(['index', 'show']);
Route::apiResource('fruitbay', FruitBayController::class);

Route::middleware(['auth:sanctum'])->group(function () {
    // Plans Route
    Route::apiResource('plans', PlanController::class)->only(['index', 'show', 'store']);
});