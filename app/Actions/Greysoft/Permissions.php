<?php
namespace App\Actions\Greysoft;

use App\Models\User;

class Permissions
{
    protected $allowed = [
        'admin' => [
            'users.user',
            'users.dispatch',
            'users.manager',
            'users.admin',
            'dashboard',
            'content',
            'fruitbay',
            'fruitbay_category',
            'foodbags',
            'foods',
            'savings',
            'savings_plans',
            'subscriptions',
            'transactions',
            'dispatch',
            'dispatch.pending',
            'dispatch.assigned',
            'dispatch.confirmed',
            'dispatch.dispatched',
            'dispatch.delivered',
        ],
        'manager' => [
            'users.user',
            'users.dispatch',
            'dashboard',
            'fruitbay',
            'fruitbay_category',
            'foodbags',
            'foods',
            'savings',
            'savings_plans',
            'subscriptions',
            'transactions',
            'dispatch.pending',
            'dispatch.assigned',
            'dispatch.confirmed',
            'dispatch.dispatched',
            'dispatch.delivered',
        ],
        'dispatch' => [
            'dispatch.pending',
            'dispatch.assigned',
            'dispatch.confirmed',
            'dispatch.dispatched',
            'dispatch.delivered',
        ],
        'user' => [
            //
        ]
    ];

    public function check(User $user, $permission)
    {dd($this->allowed[$user->role??'user']);
        if (in_array($permission, $this->allowed[$user->role??'user'], true)) {
            return true;
        }

        return 'You do not have permission to view or perform this action.';
    }

    public function getPermissions(User $user)
    {
        return $this->allowed[$user->role??'user'];
    }
}
