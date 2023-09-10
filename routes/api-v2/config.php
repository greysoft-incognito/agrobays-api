<?php

use App\Http\Resources\FoodBagCollection;
use App\Http\Resources\FruitbayCategoryCollection;
use App\Http\Resources\PlanCollection;
use App\Models\FruitBayCategory;
use App\Models\Plan;
use Illuminate\Support\Facades\Route;

Route::get('/check/update/{version}/{platform?}', function ($version, $platform = null) {
    $prefix = $platform == 'ios' ? 'ios' : 'app';
    $has_update = version_compare($version, config("api.{$prefix}_version"), '<');

    return (new \App\Http\Controllers\Controller())->responseBuilder([
        'link' => $has_update ? config("api.{$prefix}_update_url") : null,
        'status' => 'success',
        'message' => $has_update ? 'New version available' : 'No update available',
        'version' => config("api.{$prefix}_version"),
        'response_code' => 200,
    ]);
});

Route::get('/init', function () {
    return (new \App\Http\Controllers\Controller())->responseBuilder([
        'message' => 'OK',
        'status' => 'success',
        'response_code' => 200,
        'plans' => new PlanCollection(Plan::get()),
        'settings' => collect(config('settings'))->except(['permissions', 'messages']),
        'csrf_token' => csrf_token(),
        'fruitbay_categories' => new FruitbayCategoryCollection(FruitBayCategory::get()),
    ]);
});

Route::get('/config', function () {
    return (new \App\Http\Controllers\Controller())->responseBuilder([
        'message' => 'OK',
        'status' => 'success',
        'response_code' => 200,
        'data' => collect(config('settings'))->except(['permissions', 'messages']),
    ]);
});