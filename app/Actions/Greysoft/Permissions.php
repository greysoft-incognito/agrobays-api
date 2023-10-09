<?php

namespace App\Actions\Greysoft;

use App\Models\User;

class Permissions
{
    protected $allowed = [
        'admin' => [
            'users',
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
            'orders',
            'savings',
            'savings_plans',
            'subscriptions',
            'transactions',
            'dispatch',
            'dispatch.status',
            'dispatch.update',
            'dispatch.delete',
            'dispatch.pending',
            'dispatch.assigned',
            'dispatch.confirmed',
            'dispatch.dispatched',
            'dispatch.delivered',
            'feedback.manage',
            'mealplans.manage',
            'cooperatives.manage',
            'cooperatives.wallet',
        ],
        'manager' => [
            'users',
            'users.user',
            'users.dispatch',
            'dashboard',
            'fruitbay',
            'fruitbay_category',
            'foodbags',
            'foods',
            'orders',
            'savings',
            'savings_plans',
            'subscriptions',
            'transactions',
            'dispatch.status',
            'dispatch.update',
            'dispatch.pending',
            'dispatch.assigned',
            'dispatch.confirmed',
            'dispatch.dispatched',
            'dispatch.delivered',
            'feedback.manage',
            'cooperatives.manage',
        ],
        'dispatch' => [
            'dispatch.status',
            'dispatch.pending',
            'dispatch.assigned',
            'dispatch.confirmed',
            'dispatch.dispatched',
            'dispatch.delivered',
        ],
        'user' => [
            //
        ],
    ];

    public function check(User $user, $permission)
    {
        if (in_array($permission, $this->allowed[$user->role ?? 'user'], true)) {
            return true;
        }

        return 'You do not have permission to view or perform this action.';
    }

    public function getPermissions(User $user)
    {
        return $this->allowed[$user->role ?? 'user'];
    }
}