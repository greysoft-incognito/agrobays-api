<?php

namespace App\Providers;

use App\Http\Controllers\Controller;
use App\Models\PasswordCodeResets;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/web/user';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('code-requests', function (Request $request) {
            if ($request->route()->named('verification.send')) {
                $check = $request->user();
                $datetime = $check->last_attempt;
                $action = 'activate your account';
            } else {
                $check = PasswordCodeResets::whereEmail($request?->email)->first();
                $datetime = $check->created_at ?? null;
                $action = 'reset your password';
            }

            return (! $datetime || $datetime->diffInMinutes(now()) >= config('settings.token_lifespan', 30))
                ? Limit::none()
                : (new Controller)->buildResponse([
                    'message' => __("We already sent a message to help you {$action}, you can try again :0 minutes.", [config('settings.token_lifespan', 30) - $datetime->diffInMinutes(now())]),
                    'status' => 'success',
                    'response_code' => 429,
                ]);
        });
    }
}