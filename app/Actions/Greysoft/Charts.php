<?php 
namespace App\Actions\Greysoft;

class Charts 
{
    public function pie(array $data)
    {
        return [
            "tooltip" => [
                "trigger" => "item",
                "formatter" => "{a} <br/>{b}: {c} ({d}%)",
            ],
            "legend" => [
                "bottom" => "10",
                "left" => "center",
                "data" => $data['legend'],
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
                    "data" => collect($data['data'])->map(function($get) {
                        return [
                            "value" => $get['value'],
                            "name" => "Savings",
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