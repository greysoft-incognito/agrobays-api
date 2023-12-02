<?php

use App\Http\Controllers\v2\FruitBayCategoryController;
use App\Http\Controllers\v2\FruitBayController;
use App\Http\Controllers\v2\UserController;
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
Route::middleware(['auth:sanctum'])->apiResource('users', UserController::class)->only(['index', 'show']);

Route::middleware(['auth:sanctum'])->get('track/order/{reference?}', function ($id) {
    $dispatch = \App\Models\Dispatch::where(fn ($q) => $q->whereReference($id)->orWhere('id', $id))
        ->where('status', '!=', 'delivered')
        ->first();

    return (new \App\Http\Resources\DispatchResource($dispatch))->additional([
        'message' => \App\EnumsAndConsts\HttpStatus::message(\App\EnumsAndConsts\HttpStatus::OK),
        'status' => 'success',
        'response_code' =>  \App\EnumsAndConsts\HttpStatus::OK,
    ])->response()->setStatusCode(\App\EnumsAndConsts\HttpStatus::OK);
});
