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
        // dd($request->route());
        // Get the API version from the url (e.g. /api/v1/...)
        if (str($request->path())->match("/api\/v\d+/")) {
            $version = str($request->path())->match("/api\/v\d+/")->after('api/v')->toInteger();
            $request->version = $version;
        } else {
            $request->version = 1;
        }

        return $next($request);
    }
}
