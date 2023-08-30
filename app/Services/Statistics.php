<?php

namespace App\Services;

use App\Models\v1\User;
use App\Models\v1\Wallet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class Statistics extends AdminStatistics
{
    protected $type;

    protected $user_id;

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $interval
     * @param  \App\Models\v1\User  $owner (User)
     * @return \Illuminate\Support\Collection
     */
    public function build(Request $request, $interval = null, User $owner = null)
    {
        $this->user_id = $owner->id;

        // Join the orders() and transactions() results into one collection
        return collect([
            'transactions' => $this->transactions($request, $interval),
        ]);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function userData(Request $request, $interval, User $user)
    {
        $earnings = $this->builder(
            $interval,
            ['completed' => function (Builder $query) {
                $query->where('status', 'complete');
                $query->where('type', 'credit');
                $query->where(function (Builder $query) {
                    $query->where('source_type', 'task');
                    $query->orWhere('type', 'Task');
                });
            }],
            Wallet::class,
        );

        $tasks = $this->builder(
            $interval,
            ['completed' => function (Builder $query) use ($user) {
                $query->where('concierge_id', $user->id)->completed();
            }],
            Task::class,
        );

        // Join the orders() and transactions() results into one collection
        return collect([
            'earnings' => $earnings,
            'tasks' => $tasks,
        ]);
    }
}
