<?php

namespace App\Console\Commands;

use App\Models\Dispatch as ModelsDispatch;
use App\Models\Order;
use App\Models\Subscription;
use Illuminate\Console\Command;
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
        $savings = Subscription::whereRelation('allSavings', 'status', 'complete')->with(['user', 'bag'])->doesntHave('dispatch')->get()->filter(fn($s)=>$s->days_left<=0);
        if ($savings->isNotEmpty()) {
            $savings->each(function($saving) {
                $dispatch = new ModelsDispatch;
                $dispatch->code = mt_rand(100000, 999999);
                $saving->dispatch()->save($dispatch);
                $saving->dispatch->notify(new Dispatched());
            });
        }
        if ($orders->isNotEmpty()) {
            $orders->each(function($order) {
                $dispatch = new ModelsDispatch;
                $dispatch->code = mt_rand(100000, 999999);
                $order->dispatch()->save($dispatch);
                $order->dispatch->notify(new Dispatched);
            });
        }
        return 0;
    }
}
