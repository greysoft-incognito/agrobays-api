<?php

namespace App\Console\Commands;

use App\Events\ActionComplete;
use App\Models\Dispatch as ModelsDispatch;
use App\Models\Order;
use App\Models\Saving;
use App\Models\Subscription;
use App\Notifications\AutoSavingsMade;
use App\Notifications\Dispatched;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Yabacon\Paystack;

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
        // broadcast(new ActionComplete([
        //     'type' => 'savings',
        //     'data' => collect(Subscription::first())->except(['user', 'savings', 'transaction', 'items', 'bag']),
        //     'mode' => 'automatic',
        //     'updated' => ['user' => true, 'savings' => true, 'subscriptions' => true, 'transactions' => true],
        //     'created_at' => now(),
        // ], Subscription::first()->user));
        // return 0;
        // Run auto save for all intervals
        foreach (['daily', 'weekly', 'monthly', 'yearly'] as $interval) {
            $this->handleAutoSave($interval);
        }

        // Dispatch all savings that are due for delivery
        $savings = Subscription::where([
            ['status', '!=', 'closed'],
        ])->whereRelation('allSavings', 'status', 'complete')->with(['user', 'bag'])
            ->doesntHave('dispatch')->get()->filter(fn ($s) => $s->days_left <= 0);
        if ($savings->isNotEmpty()) {
            $savings->each(function ($saving) {
                $dispatch = new ModelsDispatch();
                $dispatch->code = mt_rand(100000, 999999);
                $dispatch->reference = config('settings.trx_prefix', 'AGB-').Str::random(12);
                $saving->dispatch()->save($dispatch);
                $saving->user->notify(new Dispatched($saving->dispatch));
                $this->info("Saving with ID of {$saving->id} has been dispatced for proccessing.");
            });
        } else {
            $this->error('No savings to dispatch.');
        }

        // Dispatch all orders that are due for delivery
        $orders = Order::whereRelation('transaction', 'status', 'complete')->doesntHave('dispatch')->get();
        if ($orders->isNotEmpty()) {
            $orders->each(function ($order) {
                $dispatch = new ModelsDispatch();
                $dispatch->code = mt_rand(100000, 999999);
                $dispatch->reference = config('settings.trx_prefix', 'AGB-').Str::random(12);
                $order->dispatch()->save($dispatch);
                $order->user->notify(new Dispatched($order->dispatch));
                $this->info("Order with ID of {$order->id} has been dispatced for proccessing.");
            });
        } else {
            $this->error('No orders to dispatch.');
        }

        return 0;
    }

    /**
     * Process all daily auto save
     *
     * @return int
     */
    private function handleAutoSave($interval = 'daily')
    {
        $this->info("Now checking {$interval} savings.");

        $daysMap = [
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'yearly' => 365,
        ];

        $days = $daysMap[$interval];

        $query = Subscription::where('interval', $interval)
            ->where(function ($query) {
                $query->where('status', 'pending')
                    ->orWhere('status', 'active');
            })
            ->whereHas('user', function ($query) {
                $query->whereJsonContains('data->payment_method->type', 'paystack');
                $query->orWhereJsonContains('data->payment_method->type', 'wallet');
            })->whereDoesntHave('allSavings', function ($query) {
                $query->whereRaw('created_at >= subscriptions.next_date');
            });

        $query->where(function ($query) use ($interval) {
            $query->where(function ($query) use ($interval) {
                // Based on interval, get all savings that are due for processing
                if ($interval == 'daily') {
                    $query->whereDate('subscriptions.next_date', '<=', now());
                } elseif ($interval == 'weekly') {
                    $query->whereRaw('WEEK(`next_date`) = '.now()->weekOfYear);
                    $query->whereMonth('subscriptions.next_date', '<=', now());
                } elseif ($interval == 'monthly') {
                    $query->whereMonth('subscriptions.next_date', '<=', now());
                }

                $query->whereYear('subscriptions.next_date', '<=', now());
            });
            $query->orWhere('next_date', null);

            // ->where('subscriptions.next_date', '<=', now())
            // ->whereDoesntHave('allSavings', function ($query) {
            //     $query->whereRaw('created_at >= subscriptions.next_date'); //->from('subscriptions as sub');
            // });
        });

        $savings = $query->get();

        if ($savings->isNotEmpty()) {
            $savings->each(function ($sub) use ($days, $interval) {
                $this->info("Now processing {$interval} savings for user with ID of {$sub->user_id}.");

                /**
                 * @var \App\Models\User $user
                 */
                $user = $sub->user;
                $due = round($sub->next_amount, 2);
                $fees = ($sub->bag->fees / $sub->plan->duration) * $days;

                if ($user->data['payment_method']['type'] == 'paystack') {
                    $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));

                    $reference = config('settings.trx_prefix', 'AGB-').Str::random(15);
                    $tranx = $paystack->transaction->charge([
                        'amount' => $due * 100,       // in kobo
                        'email' => $user->data['payment_method']['email'] ?? $user->email,
                        'authorization_code' => $user->data['payment_method']['authorization_code'],
                        'reference' => $reference,
                        // 'queue' => true,
                    ]);
                    if ($tranx->data->status == 'success') {
                        $saving = $sub->savings()->save(
                            new Saving([
                                'user_id' => $user->id,
                                'status' => 'complete',
                                'payment_ref' => $reference,
                                'days' => $days,
                                'amount' => $due,
                                'due' => $due,
                            ])
                        );

                        $transaction = $saving->transaction();

                        $transaction->create([
                            'user_id' => $user->id,
                            'reference' => $reference,
                            'method' => 'Paystack',
                            'status' => 'complete',
                            'amount' => $due,
                            'fees' => $fees,
                            'due' => $due + $fees,
                        ]);

                        $_left = $sub->days_left - $saving->days;
                        $sub->status = $_left <= 1 ? 'complete' : 'active';
                        $sub->fees_paid += $fees;
                        $sub->next_date = $sub->setDateByInterval(\Illuminate\Support\Carbon::parse(now()));
                        $sub->save();

                        // Notify the user of the new savings
                        $user->notify(new AutoSavingsMade($sub));
                        broadcast(new ActionComplete([
                            'type' => 'savings',
                            'mode' => 'automatic',
                            'data' => collect($sub)->except(['user', 'savings', 'transaction', 'items', 'bag']),
                            'updated' => ['user' => true, 'savings' => true, 'subscriptions' => true, 'transactions' => true],
                            'created_at' => now(),
                        ], $user));

                        $this->info("Saving with ID of {$saving->id} has been processed.");
                    } else {
                        $user->notify(new AutoSavingsMade($sub, 'failed'));
                        $this->error("Unable to process saving for user with ID of {$user->id}.");
                    }
                }
            });
        } else {
            $this->error("No {$interval} savings to process.");
        }

        return 0;
    }
}
