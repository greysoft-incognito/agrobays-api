<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserSlimResource;
use Closure;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $redirectToRoute
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|null
     */
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if (! $request->user() || $request->user()->role === 'user') {
            if ($request->version > 1) {
                return (new Controller())->responseBuilder(
                    [
                        'data' => UserSlimResource::make($request->user()),
                        'status' => 'error',
                        'message' => 'You do not have permision to view this page.',
                        'response_code' => 403,
                    ],
                    [
                        'response' => [],
                    ]
                );
            } else {
                return response()->json(['message' => 'You do not have permision to view this page.', 'user' => $request->user()], 403);
            }
        }

        return $next($request);
    }
}
