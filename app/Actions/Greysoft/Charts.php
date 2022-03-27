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
}