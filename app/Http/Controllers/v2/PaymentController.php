<?php

namespace App\Http\Controllers\v2;

use App\Http\Controllers\Controller;
use App\Models\Saving;
use App\Models\Transaction;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function paystackWebhook(Response $response)
    {
        $statusCode = 200;
        $event = \Yabacon\Paystack\Event::capture();

        // Log::channel('paystack')->info('Paystack Webhook Recieved', [
        //     'event' => $event->obj->event,
        //     'data' => $event->raw
        // ]);

        /* Verify that the signature matches one of your keys*/
        $my_keys = [
            'live' => env('PAYSTACK_SECRET_KEY'),
            'test' => env('PAYSTACK_SECRET_KEY'),
        ];

        $owner = $event->discoverOwner($my_keys);

        // dd(
        //     $this->testSign($event->raw),
        //     $owner,
        //     $event->obj->data->reference,
        //     request()->header('HTTP_X_PAYSTACK_SIGNATURE'),
        //     $event->raw,
        //     request()->header('HTTP_X_PAYSTACK_SIGNATURE') === hash_hmac('sha512', $event->raw, $my_keys['live'])
        // );

        if (! $owner) {
            // None of the keys matched the event's signature
            return $response->setStatusCode(200);
        }

        if ('success' === $event->obj->data->status) {
            switch ($event->obj->event) {
                case 'charge.success':
                    $reference = $event->obj->data->reference;
                    $transaction = Transaction::whereReference($reference)->first();

                    if ($transaction?->status === 'pending') {
                        $transactable = $transaction->transactable;

                        // Process orders
                        if ($transactable instanceof Order) {
                            // Referal Payouts system
                            $canPayRef = in_array(config('settings.referral_mode', 2), [1, 3]) &&
                            config('settings.referral_system', false);

                            $countUserOrders = $transactable->user->orders()->paymentStatus('complete')->count();

                            if ($canPayRef && $transactable->user->referrer && $countUserOrders < 1) {
                                $transactable->user->referrer->wallet()->create([
                                'amount' => config('settings.referral_bonus', 1),
                                'type' => 'credit',
                                'source' => 'Referral Bonus',
                                'detail' => __('Referral bonus for :0\'s first order.', [$transactable->user->fullname]),
                                ]);
                            }

                            $transactable->payment = 'complete';

                            // Process subscriptions
                        } elseif ($transactable instanceof Saving) {
                            $subscription = $transactable->subscription;

                            if ($subscription) {
                                $_left = $subscription->days_left - $transactable->days;

                                // Referal Payouts system
                                $canPayRef = in_array(config('settings.referral_mode', 2), [1, 2]) &&
                                config('settings.referral_system', false);

                                if ($canPayRef && $transactable->user->referrer && $transactable->days < 1) {
                                    $transactable->user->referrer->wallet()->create([
                                    'amount' => config('settings.referral_bonus', 1),
                                    'type' => 'credit',
                                    'source' => 'Referral Bonus',
                                    'detail' => __('Referral bonus for :0\'s first saving.', [$transactable->user->fullname]),
                                    ]);
                                }

                                if ($_left <= 1) {
                                    $subscription->status = 'complete';
                                } else {
                                    $subscription->status = 'active';
                                }

                                $subscription->fees_paid += $transaction->fees;
                                $subscription->next_date = $subscription->setDateByInterval(Carbon::parse(now()));
                                $subscription->save();

                                $transactable->status = 'complete';
                                $transactable->save();
                            }
                        }

                        // Save the transactable
                        $transactable->save();

                        // Set the transaction status, webhook data and save
                        $transaction->webhook = $event->obj;
                        $transaction->status = 'complete';
                        $transaction->save();
                    }
                    break;
            }
        } else {
            // The transaction has failed so let's update all related models as required.
            $reference = $event->obj->data->reference ?? 'definitly-not-found-reference-' . time();
            $transaction = Transaction::whereReference($reference)->first();

            if ($transaction) {
                // Set the transaction status, webhook data and save
                $transaction->webhook = $event->obj;
                $transaction->status = 'rejected';
                $transaction->save();

                $transactable = $transaction->transactable;

                if ($transactable instanceof Order) {
                    // Process failed orders
                    $transactable->payment = 'rejected';
                } elseif ($transactable instanceof Saving) {
                    // Process failed subscriptions
                    $transactable->status = 'rejected';
                }
                $transactable->save();
            }
        }

        return $response->setStatusCode($statusCode);
    }

    protected function testSign($data)
    {
        //sha256 is a $algo
        $secret = env('PAYSTACK_SECRET_KEY'); //$key
        //false is bool value to check whether the method works or not
        $hash_it = hash_hmac('sha512', $data, $secret, false);

        if (! $hash_it) { //checks true or false
            echo 'Data was not encrypted!';
        }
        echo "Your encrypted signature is:<b> \"$hash_it\" </b>";
    }
}