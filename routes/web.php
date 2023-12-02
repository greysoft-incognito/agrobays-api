<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response as FacadesResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Exception\InvalidArgumentException;

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
    return [
        'Welcome to Agrobays API v1' => [
            'name' => 'Agrobays',
            'version' => config('api.api_version'),
            'app_version' => config('api.app_version'),
            'author' => 'Greysoft Limited',
            'updated' => now(),
        ],
    ];
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('downloads/secure/{filename?}', function ($filename = '') {
        if (file_exists(storage_path('app/backup/' . $filename))) {
            return response()->download(storage_path('app/secure/' . $filename));
        }

        return abort(404, 'File not found');
    })->name('secure.download');

    Route::get('/artisan/backup/action/{action?}', function ($action = 'choose') {
        $errors = $code = $messages = null;
        $user = Auth::user();

        return view('web-user', compact('user', 'errors', 'code', 'action'));
    });

    Route::get('/artisan/{command}/{params?}', function (Response $response, $command, $params = null) {
        $errors = $code = $messages = $action = null;
        $user = Auth::user();
        try {
            if ($params) {
                Artisan::call($command, $params ? explode(',', $params) : []);
            }
            Artisan::call(implode(' ', explode(',', $command)), []);
            $code = collect(nl2br(app()['Illuminate\Contracts\Console\Kernel']->output()));
        } catch (CommandNotFoundException | InvalidArgumentException $e) {
            $errors = collect([$e->getMessage()]);
        }

        return view('web-user', compact('user', 'errors', 'code', 'action'));
    });
});

Route::get('face-models/{filename?}', function ($filename = '') {
    if (Storage::disk('local')->exists('face-models/' . $filename)) {
        // Set the mime type and content length
        $file = Storage::disk('local')->get('face-models/' . $filename);
        $type = Storage::disk('local')->mimeType('face-models/' . $filename);
        $size = Storage::disk('local')->size('face-models/' . $filename);

        $response = FacadesResponse::make($file, 200);
        $response->header('Content-Type', $type);
        $response->header('Content-Length', $size);

        // Set cors headers
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Methods', 'GET');

        return $response;
    }

    return abort(404, 'File not found');
});

Route::get('/web/user', [AuthenticatedSessionController::class, 'index'])
        ->middleware(['web', 'auth', 'admin'])
        ->name('web.user');

// Route::post('slacker/{action?}', [Slack::class, 'index']);
