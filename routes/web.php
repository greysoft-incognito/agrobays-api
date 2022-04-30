<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
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

Route::middleware(['auth:sanctum', 'admin'])->group(function() {
    Route::get('downloads/secure/{filename?}', function($filename='') {
        if (file_exists(storage_path('app/backup/'.$filename))) {
            return response()->download(storage_path('app/secure/'.$filename));
        }
        return abort(404, 'File not found');
    })->name('secure.download');
});

Route::get('/web/user', [AuthenticatedSessionController::class, 'index'])
        ->middleware(['web', 'auth', 'admin'])
        ->name('web.user');

Route::get('/artisan/{command}/{params?}', function ($command, $params = null) {
    try {
        if ($params) {
            Artisan::call($command, $params ? explode(',', $params) : []);
        }
        Artisan::call(implode(' ', explode(',', $command)), []);
        dd (app()['Illuminate\Contracts\Console\Kernel']->output());
    } catch (CommandNotFoundException $e) {
        dd($e->getMessage());
    }
});

// Route::post('slacker/{action?}', [Slack::class, 'index']);

require __DIR__.'/auth.php';
