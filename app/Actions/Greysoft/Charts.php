<?php

namespace App\Actions\Greysoft;

use App\Models\Order;
use App\Models\Saving;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Charts
{
    public function pie(array $data)
    {
        if (empty($data['legend']) || empty($data['data'])) {
            return [];
        }
        // $cs = config('settings.currency_symbol');

        return [
            'tooltip' => [
                'trigger' => 'item',
                // "formatter" => "{a} <br/>{b}: {$cs}{c} ({d}%)",
            ],
            'legend' => [
                'bottom' => '10',
                'left' => 'center',
                'data' => collect($data['legend'])->values(),
            ],
            'series' => [
                [
                    'name' => 'Transactions',
                    'type' => 'pie',
                    'radius' => ['50%', '73%'],
                    'roseType' => 'radius',
                    'avoidLabelOverlap' => false,
                    'label' => [
                        'show' => false,
                        'position' => 'center',
                    ],
                    'emphasis' => [
                        'label' => [
                            'show' => false,
                            'fontSize' => '30',
                            'fontWeight' => 'bold',
                        ],
                    ],
                    'labelLine' => [
                        'show' => false,
                    ],
                    'data' => collect($data['data'])->map(function ($get) use ($data) {
                        return [
                            'value' => $get['value'],
                            'name' => $data['legend'][$get['key']],
                            'itemStyle' => [
                                'color' => $get['color'],
                            ],
                        ];
                    }),
                ],
            ],
        ];
    }

    protected function bar(array $data)
    {
        // $cs = config('settings.currency_symbol');

        return [
            'tooltip' => [
                'trigger' => 'axis',
                // "formatter" => "{a} <br/>{b}: {$cs}{c} ({d}%)",
                'axisPointer' => [
                    'type' => 'shadow', // The default is a straight line, optional:'line' |'shadow'
                ],
            ],
            'grid' => [
                'left' => '2%',
                'right' => '2%',
                'top' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'data' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => false,
                    ],
                ],
            ],
            'series' => [
                [
                    'name' => 'Transactions',
                    'type' => 'bar',
                    'data' => $data['transactions'],
                    'color' => '#2a945b',
                ], [
                    'name' => 'Subscriptions',
                    'type' => 'bar',
                    'color' => '#f44336',
                    'data' => $data['subscriptions'],
                ], [
                    'name' => 'Food Orders',
                    'type' => 'bar',
                    'data' => $data['fruit_orders'],
                    'color' => '#02a9f4',
                ], [
                    'name' => 'Savings',
                    'type' => 'bar',
                    'data' => $data['savings'],
                    'color' => '#f88c2b',
                ],
            ],
        ];
    }

    public function totalTransactions($for = 'user', $period = 'year')
    {
        if ($period === 'year') {
            $start = Carbon::now()->startOfYear();
            $end = Carbon::now()->endOfYear();
        } elseif ($period === 'month') {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
        } else {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
        }

        return (float) (($for === 'user') ? Auth::user()->transactions() : Transaction::query())
            ->where('status', 'complete')
            ->when($period !== 'all', function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end]);
            })->sum('amount');
    }

    public function sales($for = 'user', $period = 'year')
    {
        if ($period === 'year') {
            $start = Carbon::now()->startOfYear();
            $end = Carbon::now()->endOfYear();
        } elseif ($period === 'month') {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
        } else {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
        }

        return (float) (($for === 'user') ? Auth::user()->orders() : Order::query())
            ->when($period !== 'all', function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end]);
            })->sum('amount');
    }

    public function income($for = 'user', $period = 'year')
    {
        if ($period === 'year') {
            $start = Carbon::now()->startOfYear();
            $end = Carbon::now()->endOfYear();
        } elseif ($period === 'month') {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
        } else {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
        }

        return (float) (($for === 'user') ? Auth::user()->transactions() : Transaction::query())
            ->where('status', 'complete')
            ->when($period !== 'all', function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end]);
            })->sum('amount');
    }

    public function customers($for = 'user', $period = 'year')
    {
        if ($period === 'year') {
            $start = Carbon::now()->startOfYear();
            $end = Carbon::now()->endOfYear();
        } elseif ($period === 'month') {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
        } else {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
        }

        return (int) User::when($period !== 'all', function ($q) use ($start, $end) {
            $q->whereBetween('created_at', [$start, $end]);
        })->count('id');
    }

    /**
     * Get the bar chart
     *
     * @param  string  $for
     * @return App\Actions\Greysoft\Charts::getBar
     */
    public function getPie($for = 'user')
    {
        $savings = (float) (($for === 'user') ? Auth::user()->savings() : Saving::query())
        ->get()->map(function ($value, $key) {
            return $value->total ?? 0;
        })->sum();

        $orders = (float) (($for === 'user') ? Auth::user()->orders() : Order::query())
        ->get()->map(function ($value, $key) {
            return $value->amount;
        })->sum();

        return $this->pie([
            'legend' => [
                'savings' => 'Savings',
                'fruit_orders' => 'Fruit Orders',
            ],
            'data' => [
                [
                    'key' => 'savings',
                    'color' => '#ffa85a',
                    'value' => floor($savings),
                ], [
                    'key' => 'fruit_orders',
                    'color' => '#2a945b',
                    'value' => floor($orders),
                ],
            ],
        ], true);
    }

    /**
     * Get the bar chart
     *
     * @param  string  $for
     * @return App\Actions\Greysoft\Charts::getBar
     */
    public function getBar($for = 'user')
    {
        return $this->bar([
            'transactions' => collect(range(1, 12))->map(function ($get) use ($for) {
                $start = Carbon::now()->month($get)->startOfMonth();
                $end = Carbon::now()->month($get)->endOfMonth();

                return (($for === 'user') ? Auth::user()->transactions() : Transaction::query())
                    ->where('status', 'complete')
                    ->whereBetween('created_at', [$start, $end])->sum('amount');
            })->toArray(),
            'subscriptions' => collect(range(1, 12))->map(function ($get) use ($for) {
                $start = Carbon::now()->month($get)->startOfMonth();
                $end = Carbon::now()->month($get)->endOfMonth();

                return (($for === 'user') ? Auth::user()->subscriptions() : Subscription::query())
                    ->whereBetween('created_at', [$start, $end])->get()->map(function ($sub) {
                        return num_reformat($sub->total_saved);
                    })->sum();
            })->toArray(),
            'fruit_orders' => collect(range(1, 12))->map(function ($get) use ($for) {
                $start = Carbon::now()->month($get)->startOfMonth();
                $end = Carbon::now()->month($get)->endOfMonth();

                return (($for === 'user') ? Auth::user()->orders() : Order::query())
                    ->whereBetween('created_at', [$start, $end])->sum('amount');
            })->toArray(),
            'savings' => collect(range(1, 12))->map(function ($get) use ($for) {
                $start = Carbon::now()->month($get)->startOfMonth();
                $end = Carbon::now()->month($get)->endOfMonth();

                return (($for === 'user') ? Auth::user()->savings() : Saving::query())
                    ->where('status', 'complete')
                    ->whereBetween('created_at', [$start, $end])->sum('amount');
            })->toArray(),
        ], true);
    }
}