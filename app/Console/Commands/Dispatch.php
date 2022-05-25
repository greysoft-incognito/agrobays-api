<?php

namespace App\Console\Commands;

use App\Models\Dispatch as ModelsDispatch;
use App\Models\Order;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Notifications\Dispatched;

class Dispatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dispatch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch orders and savings for delivery';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $orders = Order::whereRelation('transaction', 'status', 'complete')->doesntHave('dispatch')->get();
        $savings = Subscription::whereRelation('allSavings', 'status', 'complete')->with(['user', 'bag'])
                    ->doesntHave('dispatch')->get()->filter(fn($s)=>$s->days_left<=0);

        if ($savings->isNotEmpty()) {
            $savings->each(function($saving) {
                $dispatch = new ModelsDispatch;
                $dispatch->code = mt_rand(100000, 999999);
                $dispatch->reference = config('settings.trx_prefix', 'AGB-') . Str::random(12);
                $saving->dispatch()->save($dispatch);
                $saving->user->notify(new Dispatched($dispatch, 'pending'));
                $this->info("Saving with ID of {$saving->id} has been dispatced for proccessing.");
            });
        } else {
            $this->error("No savings to dispatch.");
        }
        if ($orders->isNotEmpty()) {
            $orders->each(function($order) {
                $dispatch = new ModelsDispatch;
                $dispatch->code = mt_rand(100000, 999999);
                $dispatch->reference = config('settings.trx_prefix', 'AGB-') . Str::random(12);
                $order->dispatch()->save($dispatch);
                $order->user->notify(new Dispatched($dispatch, 'pending'));
                $this->info("Order with ID of {$order->id} has been dispatced for proccessing.");
            });
        } else {
            $this->error("No orders to dispatch.");
        }
        return 0;
    }
}