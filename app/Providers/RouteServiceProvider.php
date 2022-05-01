<?php

namespace App\Providers;

use App\Models\PasswordCodeResets;
use App\Http\Controllers\Controller;
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
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('password-requests', function (Request $request) {
            $check = PasswordCodeResets::whereEmail($request?->email)->first();
            return (!$check || $check->created_at->diffInMinutes(now()) >= 30)
                ? Limit::none()
                : (new Controller)->buildResponse([
                    'message' => __('We already sent a mail to help you reset your password, you can try again :0 minutes.', [30 - $check->created_at->diffInMinutes(now())]),
                    'status' => 'success',
                    'response_code' => 429,
                ]);
        });
    }
}