<?php

namespace App\Services;

use App\Models\v1\Company;
use App\Models\v1\Feed;
use App\Models\v1\Order;
use App\Models\v1\Transaction;
use Carbon\Carbon;
use Flowframe\Trend\Trend;
use Illuminate\Http\Request;

class ChartsPlus
{
    protected $type;

    /**
     * Generate the transaction and order charts dataset for echarts
     * Flowframe\Trend\Trend is used to generate the data
     *
     * @link https://echarts.apache.org/en/option.html#dataset
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\Company  $company
     * @return \Illuminate\Support\Collection
     */
    public function transactionAndOrderCharts(Request $request, Company $company, $type = null)
    {
        // Add the last 6 months to the dimensions array
        $orders = Trend::query(Order::completed()->byCompany($company->id))
            ->between(now()->endOfMonth()->subMonths(5), now()->endOfMonth())
            ->perMonth()->sum('amount')->mapWithKeys(function ($item) {
                return [$item->date => $item->aggregate];
            });

        // $transactions = Trend::query(Transaction::status('completed')->belongsToCompany($company->id))
        //     ->between(now()->endOfMonth()->subMonths(6), now()->endOfMonth())
        //     ->perMonth()->sum('amount')->mapWithKeys(function ($item) {
        //         return [$item->date => $item->aggregate];
        //     });

        $dataset = collect([
            'legend' => $orders->keys(),
            'dimensions' => ['Type', ...$orders->keys()],
            'source' => [
                ['Type' => 'Sales', ...$orders],
                // ['Type' => 'Transactions', ...$transactions],
            ],
        ]);

        return [
            'chart' => $dataset,
        ];
    }

    /**
     * Generate the admin transaction and order charts dataset for echarts
     * Flowframe\Trend\Trend is used to generate the data
     *
     * @link https://echarts.apache.org/en/option.html#dataset
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Collection
     */
    public function adminFeedsAndTalksChart(Request $request, $type = null)
    {
        // Add the last 6 months to the dimensions array
        $feeds = Trend::query(Feed::where('meta->type', 'feeds'))
            ->between(now()->endOfMonth()->subMonths(5), now()->endOfMonth())
            ->perMonth()->count('id')->mapWithKeys(function ($item) {
                return [$item->date => $item->aggregate];
            });

        $talks = Trend::query(Feed::where('meta->type', 'talks'))
            ->between(now()->endOfMonth()->subMonths(5), now()->endOfMonth())
            ->perMonth()->count('id')->mapWithKeys(function ($item) {
                return [$item->date => $item->aggregate];
            });

        $dataset = collect([
            'series' => [
                [
                    'name' => 'Feeds',
                    'data' => $feeds->values(),
                ],
                [
                    'name' => 'Talks',
                    'data' => $talks->values(),
                ],
            ],
            'chartOptions' => [
                'xaxis' => [
                    'categories' => $talks->keys()->map(function ($item) {
                        return Carbon::parse($item.'-01')->format('M Y');
                    })->toArray(),
                ],
            ],
        ]);

        return [
            'chart' => $dataset,
        ];
    }
}
