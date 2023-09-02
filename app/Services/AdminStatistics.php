<?php

namespace App\Services;

use App\Models\v1\Comment;
use App\Models\v1\Feed;
use App\Models\v1\Organization;
use App\Models\v1\Transaction;
use App\Models\v1\User;
use App\Models\v1\Wallet;
use Flowframe\Trend\Trend;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Overtrue\LaravelLike\Like;

class AdminStatistics
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
        if ($owner) {
            $this->user_id = $owner->id;
        }

        // Join the orders() and transactions() results into one collection
        return collect([
            'users' => $this->users($request, $interval),
            'posts' => $this->posts($request, $interval),
            'likes' => $this->likes($request, $interval),
            'comments' => $this->comments($request, $interval),
            'organizations' => $this->organizations($request, $interval),
            'transactions' => $this->transactions($request, $interval),
        ]);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function users(Request $request, $interval = null)
    {
        return $this->builder(
            $interval,
            [
                null => 'id',
                'admin' => function (Builder $query) {
                    $query->whereHas('roles', function (Builder $query) {
                        $query->whereIn('name', config('permission.admin_roles', []));
                    });
                }, ,
                'user' => function (Builder $query) {
                    $query->whereDoesntHave('roles', function (Builder $query) {
                        $query->whereIn('name', config('permission.admin_roles', []));
                    });
                },
                'mentor' => function (Builder $query) {
                    $query->where('type', 'mentor');
                },
                'mentee' => function (Builder $query) {
                    $query->where('type', 'mentee');
                },
            ],
            User::class,
            null,
            $request->input('duration', 12)
        );
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function posts(Request $request, $interval = null)
    {
        return $this->builder(
            $interval,
            [
                null => 'id',
                'feeds' => function (Builder $query) {
                    $query->where('meta->type', 'feeds');
                },
                'talks' => function (Builder $query) {
                    $query->where('meta->type', 'talks');
                },
            ],
            Feed::class,
            null,
            $request->input('duration', 12)
        );
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function comments(Request $request, $interval = null)
    {
        return $this->builder(
            $interval,
            [
                null => 'id',
            ],
            Comment::class,
            null,
            $request->input('duration', 12)
        );
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function organizations(Request $request, $interval = null)
    {
        return $this->builder(
            $interval,
            [
                null => 'id',
            ],
            Organization::class,
            null,
            $request->input('duration', 12)
        );
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function likes(Request $request, $interval = null)
    {
        return $this->builder(
            $interval,
            [
                null => 'id',
            ],
            Like::class,
            null,
            $request->input('duration', 12)
        );
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function transactions(Request $request, $interval = null)
    {
        return $this->builder(
            $interval,
            [null => 'status', 'pending' => 'status', 'completed' => 'status'],
            Transaction::class,
            null,
            $request->input('duration', 12)
        );
    }

    /**
     * @param  string  $interval (day, week, month, year)
     * @param  array  $scops (null, pending, completed) - null = all
     * @param  string  $model (Transaction::class)
     * @param  string  $intermidiate    If the model has a polymorphic relation
     * @param  int  $dur (duration in $interval)
     * @return \Illuminate\Support\Collection
     */
    protected function builder(
        string $interval = null,
        array $scopes = [null => 'status', 'pending' => 'status', 'completed' => 'status'],
        string $model = Transaction::class,
        string $intermidiate = null,
        int $dur = null
    ): \Illuminate\Support\Collection {

        // Set the scopes values and key to limit the query

        return collect($scopes)->mapWithKeys(
            function ($scope, $scopeValue) use ($interval, $intermidiate, $model, $dur) {

                // Set the query based on the intermidiate type
                if ($intermidiate) {
                    $query = $scopeValue
                    ? $model::whereTransactableType($intermidiate)->where($scope, is_callable($scope) ? null : $scopeValue)
                    : $model::whereTransactableType($intermidiate);
                } else {
                    $query = $scopeValue || is_callable($scope)
                    ? $model::where($scope, is_callable($scope) ? null : $scopeValue)
                    : $model::query();
                }

                if (isset($this->user_id)) {
                    // Filter by user
                    $query->whereUserId($this->user_id);
                }

                // Build the data array
                $useMetrics = in_array($model, [Transaction::class, Wallet::class]);

                if ($useMetrics) {
                    $data = [
                        'total'.($scopeValue ? '_' : '').$scopeValue => $query->sum('amount'),
                        'count'.($scopeValue ? '_' : '').$scopeValue => $query->count(),
                        'count_'.$interval.($scopeValue ? '_' : '').$scopeValue => $query->{'where'.ucfirst($interval)}('created_at', now()->{$interval})->count(),
                        $interval.($scopeValue ? '_' : '').$scopeValue => $query->{'where'.ucfirst($interval)}('created_at', now()->{$interval})->sum('amount'),
                    ];
                } else {
                    $data = [
                        'count'.($scopeValue ? '_' : '').$scopeValue => $query->count(),
                    ];
                }

                // Add the trend data if duration is set
                if ($dur && $useMetrics) {
                    if ($intermidiate) {
                        $query2 = $scopeValue
                        ? $model::whereTransactableType($intermidiate)->where($scope, is_callable($scope) ? null : $scopeValue)
                        : $model::whereTransactableType($intermidiate);
                    } else {
                        $query2 = $scopeValue
                        ? $model::where($scope, is_callable($scope) ? null : $scopeValue)
                        : $model::query();
                    }

                    // Merge the trend data into the data array
                    $data = array_merge($data, [
                        'trend'.($scopeValue ? '_' : '').$scopeValue => Trend::query($query2)->between(
                            start: now()->{'startOf'.$interval}()->subMonth($dur - 1),
                            end: now()->{'endOf'.$interval}()
                        )->{'per'.$interval}()->sum('amount')->mapWithKeys((fn ($v) => [$v->date => $v->aggregate])),
                    ]);
                }

                // Return the data
                return $data;
            }
        )->sortKeys();
    }
}
