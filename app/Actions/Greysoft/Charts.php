<?php 
namespace App\Actions\Greysoft;

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
        return [
            "tooltip" => [
                "trigger" => "axis",
                "axisPointer" => [
                    "type": "shadow", // The default is a straight line, optional:'line' |'shadow'
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
                    "data" => [40, 45, 27, 50, 32, 50, 70, 30, 30, 40, 67, 29],
                    "color" => "#546bfa",
                ], [
                    "name" => "Subscriptions",
                    "type" => "bar",
                    "data" => [124, 100, 20, 120, 117, 70, 110, 90, 50, 90, 20, 50],
                    "color" => "#3a9688",
                ], [
                    "name" => "Food Orders",
                    "type" => "bar",
                    "data" => [17, 2, 0, 29, 20, 10, 23, 0, 8, 20, 11, 30],
                    "color" => "#02a9f4",
                ], [
                    "name" => "Savings",
                    "type" => "bar",
                    "data" => [20, 100, 80, 14, 90, 86, 100, 70, 120, 50, 30, 60],
                    "color" => "#f88c2b",
                ]
            ],
        ];
    }
}