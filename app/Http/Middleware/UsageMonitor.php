<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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

        /** @var \App\Models\User $user */
        $user = $request->user();

        // If the user is authenticated.
        if ($user) {
            // If settings have been updated since the last time the user was refreshed, refresh the user
            $last_setting_time = Carbon::parse(config('settings.last_setting_time'));
            $canRefresh = $last_setting_time->isAfter($user->last_refreshed) || ! $user->last_refreshed;

            // If user has been away for longer than an hour refresh the user
            if (($user->last_seen?->diffInMinutes(now()) || 0) > 60 || $user->has_pending_updates || $canRefresh) {
                $user->refreshUi();
                $user->has_pending_updates = false;
                $user->last_refreshed = now();
            }

            // Update the last_seen field
            $user->last_seen = now();
            $user->saveQuietly();

            if ($request->isMethod('delete') && $request->route()->named('account.ping')) {
                // Delete the current token
                $request->user()->currentAccessToken()->delete();
            }
        }

        return $response;
    }
}
