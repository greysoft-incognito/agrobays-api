<?php

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/get/settings', function (Request $request) {
    return (new Controller())->buildResponse([
        'message' => 'OK',
        'status' => 'success',
        'response_code' => 200,
        'settings' => collect(config('settings'))->except(['permissions', 'messages']),
        'fruitbay_categories' => \App\Models\FruitBayCategory::all(),
        'foodbags' => \App\Models\FoodBag::all(),
        'plans' => \App\Models\Plan::all(),
        'csrf_token' => csrf_token(),
    ]);
});

Route::get('/get/config', function (Request $request) {
    return (new Controller())->buildResponse([
        'message' => 'OK',
        'status' => 'success',
        'response_code' => 200,
        'config' => collect(config('settings'))->except(['permissions', 'messages']),
    ]);
});

Route::get('/check/update/{version}', function (Request $request, $version) {
    $has_update = version_compare($version, env('APP_VERSION'), '<');

    return (new Controller())->buildResponse([
        'message' => $has_update ? 'New version available' : 'No update available',
        'link' => $has_update ? env('APP_UPDATE_URL') : null,
        'version' => env('APP_VERSION'),
        'status' => 'success',
        'response_code' => 200,
    ]);
});
