<?php
namespace App\Actions\Greysoft;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Charts
{
    public function pie(array $data)
    {
        if (empty($data['legend']) || empty($data['data']) || empty($data['data'][0]['value'])) {
            return [];
        }

        $currency_symbol = config('settings.currency_symbol');

        return [
            "tooltip" => [
                "trigger" => "item",
                "formatter" => "{a} <br/>{b}: {$currency_symbol}{c} ({d}%)",
            ],
            "legend" => [
                "bottom" => "10",
                "left" => "center",
                "data" => collect($data['legend'])->values(),
            ],
            "series" => [
                [
                    "name" => "Sales",
                    "type" => "pie",
                    "radius" => ["50%", "70%"],
                    "avoidLabelOverlap" => false,
                    "label" => [
                        "show" => false,
                        "position" => "center",
                    ],
                    "emphasis" => [
                        "label" => [
                            "show" => false,
                            "fontSize" => "30",
                            "fontWeight" => "bold",
                        ],
                    ],
                    "labelLine" => [
                        "show" => false,
                    ],
                    "data" => collect($data['data'])->map(function($get) use ($data) {
                        return [
                            "value" => $get['value'],
                            "name" => $data['legend'][$get['key']],
                            "itemStyle" => [
                                "color" => $get['color'],
                            ],
                        ];
                    })
                ],
            ],
        ];
    }

    public function bar(array $data)
    {
        $currency_symbol = config('settings.currency_symbol');
        return [
            "tooltip" => [
                "trigger" => "axis",
                "formatter" => "{a} <br/>{b}: {$currency_symbol}{c} ({d}%)",
                "axisPointer" => [
                    "type" => "shadow", // The default is a straight line, optional:'line' |'shadow'
                ],
            ],
            "grid" => [
                "left" => "2%",
                "right" => "2%",
                "top" => "4%",
                "bottom" => "3%",
                "containLabel" => true,
            ],
            "xAxis" => [
                [
                    "type" => "category",
                    "data" => [ "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ]
                ],
            ],
            "yAxis" => [
                [
                    "type" => "value",
                    "splitLine" => [
                        "show" => false,
                    ],
                ]
            ],
            "series" => [
                [
                    "name" => "Transactions",
                    "type" => "bar",
                    "data" => collect(range(1,12))->map(function($get) {
                        $start = Carbon::now()->month($get)->startOfMonth();
                        $end = Carbon::now()->month($get)->endOfMonth();
                        return Auth::user()->transactions()->whereBetween('created_at', [$start, $end])->sum('amount');
                    })->toArray(),
                    "color" => "#546bfa",
                ], [
                    "name" => "Subscriptions",
                    "type" => "bar",
                    "data" => collect(range(1,12))->map(function($get) {
                        $start = Carbon::now()->month($get)->startOfMonth();
                        $end = Carbon::now()->month($get)->endOfMonth();
                        return Auth::user()->subscriptions()
                            ->whereBetween('created_at', [$start, $end])->get()->map(function($sub) {
                                return num_reformat($sub->total_saved);
                        })->sum();
                    })->toArray(),
                    "color" => "#3a9688",
                ], [
                    "name" => "Food Orders",
                    "type" => "bar",
                    "data" => collect(range(1,12))->map(function($get) {
                        $start = Carbon::now()->month($get)->startOfMonth();
                        $end = Carbon::now()->month($get)->endOfMonth();
                        return Auth::user()->orders()->whereBetween('created_at', [$start, $end])->sum('amount');
                    })->toArray(),
                    "color" => "#02a9f4",
                ], [
                    "name" => "Savings",
                    "type" => "bar",
                    "data" => collect(range(1,12))->map(function($get) {
                        $start = Carbon::now()->month($get)->startOfMonth();
                        $end = Carbon::now()->month($get)->endOfMonth();
                        return Auth::user()->savings()->where('status', 'complete')
                            ->whereBetween('created_at', [$start, $end])->sum('amount');
                    })->toArray(),
                    "color" => "#f88c2b",
                ]
            ],
        ];
    }
}
