<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UsageMonitor
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
        $response = $next($request);

        $user = $request->user();

        // If user has been away for longer than an hour refresh the user
        if ($user && ($user->last_seen?->diffInMinutes(now()) || 0) > 60) {
            $user->refresh();
        }

        if ($user) {
            $user->last_seen = now();
            $user->save();

            if ($request->isMethod('delete') && $request->route()->named('account.ping')) {
                // Delete the current token
                $request->user()->currentAccessToken()->delete();
            }
        }

        return $response;
    }
}