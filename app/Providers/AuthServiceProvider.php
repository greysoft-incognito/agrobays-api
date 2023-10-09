<?php

namespace App\Providers;

use App\EnumsAndConsts\HttpStatus;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            return config('app.frontend_url') . "/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        Gate::define('usable', function (User $user, $permission) {
            return ($check = \Permission::check($user, $permission)) === true
                ? Response::allow()
                : Response::deny($check, HttpStatus::FORBIDDEN);
        });

        Gate::define('be-owner', function (
            User $user,
            $item_user_id,
            $message = 'You do not have permission to view or perform this action.'
        ) {
            return $user->id === $item_user_id
                ? Response::allow()
                : Response::deny($message, HttpStatus::FORBIDDEN);
        });

        Gate::define('manage', function (
            User $user,
            $item = null,
            $permission = 'all',
            $accepted = true,
            $message = 'You do not have permission to view or perform this action.'
        ) {
            $isManager = method_exists($item, 'members')
                ? $item->members()->isAccepted($accepted)->forUser($user)
                       ->where(function ($q) use ($permission) {
                        if ($permission === 'any' || ! $permission) {
                            // User should have at least one ability
                            $q->whereJsonLength('abilities', '>', 0);
                        } elseif ($permission === 'exists') {
                            // User should exist in the members table
                            $q->whereNotNull('id');
                        } else {
                            $q->whereJsonContains('abilities', $permission);
                            $q->orWhereJsonContains('abilities', 'all');
                        }
                       })->exists()
                : false;

            return $isManager
                ? Response::allow()
                : Response::deny($message, HttpStatus::FORBIDDEN);
        });
    }
}