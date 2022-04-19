<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Console\Exception\CommandNotFoundException;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/artisan/{command}/{params?}', function ($command, $params = null) {
    try {
        echo (String)Artisan::call($command, $params ? explode(',', $params) : []);
    } catch (CommandNotFoundException $e) {
        echo $e->getMessage();
    }
});

// Route::post('slacker/{action?}', [Slack::class, 'index']);

require __DIR__.'/auth.php';
