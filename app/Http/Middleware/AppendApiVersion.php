<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AppendApiVersion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $request->version = 1;

        // Get the API version from the url (e.g. /api/v1/...)
        if (str($request->path())->match("/api\/v\d+/")) {
            $request->version = str($request->path())->match("/api\/v\d+/")->after('api/v')->toInteger();
        }

        return $next($request);
    }
}
