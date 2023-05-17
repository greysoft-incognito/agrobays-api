<?php

namespace App\Http\Controllers;

use App\Models\FruitBay;
use App\Models\Order;
use App\Models\Saving;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yabacon\Paystack;
use Yabacon\Paystack\Exception\ApiException;

class PaymentController extends Controller
{
    /**
     * Initialize Payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $action
     * @return \Illuminate\Http\Response
     */
    public function initializeSaving(Request $request, $method = 'paystack')
    {
        if (($validator = Validator::make($request->all(), [
            'subscription_id' => ['required', 'numeric'],
        ]))->stopOnFirstFailure()->fails()) {
            return $this->validatorFails($validator, 'subscription_id');
        }

        $subscription = Auth::user()->subscriptions()->find($request->subscription_id);

        $real_due = 0;
        $code = 403;

        if (! $subscription) {
            $msg = 'You do not have an active subscription';
        } else {
            if (($validator = Validator::make($request->all(), [
                'days' => ['required', 'numeric', 'min:1', 'max:'.$subscription->days_left],
            ], [
                'days.min' => 'You have to save for at least 1 day.',
                'days.max' => "You cannot save for more than {$subscription->days_left} days.",
            ]))->stopOnFirstFailure()->fails()) {
                return $this->validatorFails($validator, 'email');
            }

            $method = $request->get('method', $method);
            $due = round(($subscription->plan->amount / $subscription->plan->duration) * $request->days, 2);

            $fees = ($subscription->bag->fees / $subscription->plan->duration) * $request->days;

            try {
                $reference = config('settings.trx_prefix', 'AGB-').Str::random(12);
                if ($request->get('method', $method) === 'wallet') {
                    if ($request->user()->wallet_balance >= $due) {
                        $tranx = [
                            'reference' => $reference,
                            'method' => 'wallet',
                        ];

                        $request->user()->wallet()->create([
                            'reference' => $reference,
                            'amount' => round($due + $fees, 2),
                            'type' => 'debit',
                            'source' => 'Savings',
                            'detail' => __('Payment for :0 subscription', [$subscription->plan->title]),
                        ]);
                    } else {
                        return $this->buildResponse([
                            'message' => 'You do not have enough funds in your wallet',
                            'status' => 'error',
                            'status_code' => HttpStatus::BAD_REQUEST,
                        ], HttpStatus::BAD_REQUEST);
                    }
                } else {
                    $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));

                    $real_due = $due * 100;

                    if ($fees > 0) {
                        $real_due = round($real_due + ($fees * 100));
                    }

                    // Dont initialize paystack for inline transaction
                    if ($request->inline) {
                        $tranx = [
                            'data' => ['reference' => $reference],
                        ];
                        $real_due = $due;
                    } else {
                        $tranx = $paystack->transaction->initialize([
                            'amount' => $real_due,       // in kobo
                            'email' => Auth::user()->email,         // unique to customers
                            'reference' => $reference,         // unique to transactions
                            'callback_url' => config('settings.frontend_link')
                                ? config('settings.frontend_link').'/payment/verify'
                                : config('settings.payment_verify_url', route('payment.paystack.verify')),
                        ]);
                        $real_due = $due;
                    }
                }

                $code = 200;

                $savings = $subscription->savings()->save(
                    new Saving([
                        'user_id' => Auth::id(),
                        'status' => 'pending',
                        'payment_ref' => $reference,
                        'days' => $request->days,
                        'amount' => $due,
                        'due' => $due,
                    ])
                );
                $transaction = $savings->transaction();
                $transaction->create([
                    'user_id' => Auth::id(),
                    'reference' => $reference,
                    'method' => ucfirst($method),
                    'status' => 'pending',
                    'amount' => $due,
                    'fees' => $fees,
                    'due' => $due + $fees,
                ]);
            } catch (ApiException | \InvalidArgumentException | \ErrorException $e) {
                return $this->buildResponse([
                    'message' => $e->getMessage(),
                    'status' => 'error',
                    'response_code' => 422,
                    'due' => $due,
                    'payload' => $e instanceof ApiException ? $e->getResponseObject() : [],
                ]);
            }
        }

        return $this->buildResponse([
            'message' => $msg ?? 'OK',
            'status' => ! $subscription ? 'info' : 'success',
            'response_code' => $code ?? 200, //202
            'payload' => $tranx ?? [],
            'transaction' => $transaction ?? [],
            'amount' => $real_due,
        ]);
    }

    /**
     * Initialize FruitBay Payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $method
     * @return \Illuminate\Http\Response
     */
    public function initializeFruitBay(Request $request, $method = 'paystack')
    {
        if (($validator = Validator::make($request->all(), [
            'cart' => ['required', 'array'],
        ]))->stopOnFirstFailure()->fails()) {
            return $this->validatorFails($validator, 'cart');
        }

        $real_due = 0;
        $code = 403;

        $user = Auth::user();
        $items = collect($request->cart)->map(fn ($k) => collect($k)->except('qty'))->flatten()->all();
        $cartItems = FruitBay::whereIn('id', $items)->get();

        if ($cartItems->isNotEmpty()) {
            $cart = $cartItems->map(function ($item) use ($request) {
                $value = collect($request->cart)->filter(fn ($k) => $k['item_id'] == $item->id)->first();
                if ($item) {
                    $item->total = $item->price * $value['qty'];
                    $item->qty = $value['qty'];
                }

                return $item;
            })->filter(fn ($k) => $k !== null);

            // Calculate the global shipping fee
            $delivery_method = $request->delivery_method ?? 'delivery';
            $globalSshippingFee = config('settings.paid_shipping', false) || $delivery_method == 'delivery'
                ? config('settings.shipping_fee')
                : 0;

            if ($user && $request->address && $request->address !== ($user->address->shipping ?? '')) {
                $address = $user->address;
                $address->shipping = $request->address;
                $user->address = $address;
                $user->save();
            }

            if ($cart->count() <= 0) {
                $msg = 'You have too few items in your basket, or some items may no longer be available.';
            } else {
                $shipping_fees = $cart->sum('fees') + $globalSshippingFee;
                $due = $cart->sum('total');
                $real_due = $due;
                $method = $request->get('method', $method);

                try {
                    $reference = config('settings.trx_prefix', 'AGB-').Str::random(15);
                    if ($request->get('method', $method) === 'wallet') {
                        if ($request->user()->wallet_balance >= $due) {
                            $tranx = [
                                'reference' => $reference,
                                'method' => 'wallet',
                            ];

                            $request->user()->wallet()->create([
                                'reference' => $reference,
                                'amount' => $due + $shipping_fees,
                                'type' => 'debit',
                                'source' => 'Cart Checkout',
                                'detail' => trans_choice('Payment for order of :0 items', $cart->count(), [$cart->count()]),
                            ]);
                        } else {
                            return $this->buildResponse([
                                'message' => 'You do not have enough funds in your wallet',
                                'status' => 'error',
                                'status_code' => HttpStatus::BAD_REQUEST,
                            ], HttpStatus::BAD_REQUEST);
                        }
                    } else {
                        $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));

                        // Dont initialize paystack for inline transaction
                        if ($request->inline) {
                            $tranx = [
                                'data' => ['reference' => $reference],
                            ];
                        } else {
                            $tranx = $paystack->transaction->initialize([
                                'amount' => ($due + $shipping_fees) * 100,       // in kobo
                                'email' => $user->email,         // unique to customers
                                'reference' => $reference,         // unique to transactions
                                'callback_url' => $request->get('callback_url', config('settings.payment_verify_url', route('payment.paystack.verify'))),
                            ]);
                        }
                    }

                    $code = 200;

                    $order = Order::create([
                        'due' => $due + $shipping_fees,
                        'fees' => $shipping_fees,
                        'items' => $cart,
                        'status' => 'pending',
                        'amount' => $due,
                        'user_id' => $user->id,
                        'payment' => 'pending',
                        'reference' => $reference,
                        'delivery_method' => $delivery_method,
                    ]);

                    $transaction = $order->transaction();
                    $transaction->create([
                        'reference' => $reference,
                        'user_id' => $user->id,
                        'method' => ucfirst($method ?? 'paystack'),
                        'status' => 'pending',
                        'amount' => $due,
                        'fees' => $shipping_fees,
                        'due' => $due + $shipping_fees,
                    ]);
                } catch (ApiException | \InvalidArgumentException | \ErrorException $e) {
                    return $this->buildResponse([
                        'message' => $e->getMessage(),
                        'status' => 'error',
                        'response_code' => 422,
                        'due' => $due,
                        'payload' => $e instanceof ApiException ? $e->getResponseObject() : [],
                    ]);
                }
            }
        } else {
            $code = 403;
            $msg = 'One or more items on your cart no are no longer available.';
        }

        return $this->buildResponse([
            'message' => $msg ?? 'OK',
            'status' => $code !== 200 ? 'error' : 'success',
            'response_code' => $code ?? 200, //202
            'payload' => $tranx ?? [],
            'items' => $cart ?? [$subscription ?? null],
            'amount' => $real_due,
            'delivery_method' => $delivery_method,
        ]);
    }

    /**
     * Verify the paystack payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $action
     * @return \Illuminate\Http\Response
     */
    public function paystackVerify(Request $request, $method = 'paystack')
    {
        $msg = 'Invalid Transaction.';
        $status = 'error';
        $set_type = 'deposit';
        $code = 403;
        if (! $request->reference) {
            $msg = 'No reference supplied';
        }

        try {
            if ($request->get('method', $method) === 'wallet') {
                $tranx = new \stdClass();
                $tranx->data = new \stdClass();
                $tranx->data->status = 'failed';
                if ($request->user()->wallet()->where('reference', $request->reference)->exists()) {
                    $tranx->data->status = 'success';
                }
            } else {
                $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));
                $tranx = $paystack->transaction->verify([
                    'reference' => $request->reference,   // unique to transactions
                ]);
            }

            if ('success' === $tranx->data->status) {
                $transaction = Transaction::where('reference', $request->reference)->where('status', 'pending')->first();
                if ($request->get('method', $method) === 'wallet') {
                    $tranx->data->amount = $transaction?->amount * 100;
                }
                throw_if(! $transaction, \ErrorException::class, 'Transaction not found.');
                if ($transaction->transactable instanceof Saving) {
                    $processSaving = $this->processSaving($request, $tranx, $transaction);
                } elseif ($transaction->transactable instanceof Order) {
                    $processSaving = $this->processOrder($request, $tranx, $transaction);
                    $set_type = 'order';
                }
                $msg = $processSaving['msg'] ?? 'OK';
                $code = $processSaving['code'] ?? 200;
                $status = $processSaving['status'] ?? 'success';
            }
        } catch (ApiException | \InvalidArgumentException | \ErrorException $e) {
            $payload = $e instanceof ApiException ? $e->getResponseObject() : [];
            Log::error($e->getMessage(), ['url' => url()->full(), 'request' => $request->all()]);

            return $this->buildResponse([
                'message' => $e->getMessage(),
                'status' => 'error',
                'response_code' => 422,
                'payload' => $payload,
            ]);
        }

        return $this->buildResponse([
            'message' => $msg ?? 'OK',
            'status' => $status ?? 'success',
            'response_code' => $code ?? 200,
            'payload' => $tranx ?? new \stdClass(),
            $set_type => $processSaving['data'] ?? new \stdClass(),
        ]);
    }

    /**
     * Process a saving's payment request
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  object  $tranx                          Transaction data returned by paystack
     * @param  \App\Models\Transaction  $transaction    Transaction model
     * @return array
     */
    public function processSaving(Request $request, $tranx, Transaction $transaction = null): array
    {
        $msg = 'An unrecoverable error occured';
        $code = 422;
        $status = 'error';
        $subscription = [];
        $saving = $transaction->transactable;

        // $saving = Saving::where('payment_ref', $request->reference)->where('status', 'pending')->first();
        if ($saving && $saving->status === 'pending') {
            $subscription = User::find($saving->user_id)->subscriptions()->where('id', $saving->subscription_id)->first();
            $_amount = money($tranx->data->amount / 100);
            $_left = $subscription->days_left - $saving->days;

            if ('success' === $tranx->data->status) {
                $saving->status = 'complete';
                $trns = $saving->transaction;
                $trns->status = 'complete';

                if ($_left <= 1) {
                    $subscription->status = 'complete';
                    $msg = 'You have completed the saving circle for this subscription, you would be notified when your food bag is ready for pickup or delivery.';
                } else {
                    $subscription->status = 'active';
                    $plantitle = $subscription->plan->title.(stripos($subscription->plan->title, 'plan') !== false ? '' : ' plan');
                    $msg = "You have successfully made {$saving->days} day(s) savings of {$_amount} for the {$plantitle}, you now have only {$_left} days left to save up.";
                }
                $subscription->fees_paid += $transaction->fees;
                $subscription->next_date = $subscription->setDateByInterval(\Illuminate\Support\Carbon::parse(now()));
                $subscription->save();
            } else {
                $saving->status = 'rejected';
                $trns = $saving->transaction;
                $trns->status = 'rejected';
            }
            $saving->save();
            $trns->save();
            $status = 'success';
            $code = 200;
        }

        return [
            'msg' => $msg,
            'code' => $code,
            'status' => $status,
            'payload' => $tranx ?? [],
            'data' => $subscription,
        ];
    }

    /**
     * Process an order payment
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  object  $tranx                          Transaction data returned by paystack
     * @param  \App\Models\Transaction  $transaction    Transaction model
     * @return array
     */
    public function processOrder(Request $request, $tranx, Transaction $transaction = null): array
    {
        $msg = 'An unrecoverable error occured';
        $code = 422;
        $status = 'error';
        $order = $transaction->transactable;

        if ($order && $order->payment === 'pending') {
            $trns = $order->transaction;
            if ('success' === $tranx->data->status) {
                $order->payment = 'complete';
                $trns->status = 'complete';
                $msg = 'Your order has been placed successfully, you will be notified whenever it is ready for pickup or delivery.';
            } else {
                $order->payment = 'rejected';
                $order->status = 'rejected';
                $trns->status = 'rejected';
            }
            $order->save();
            $trns->save();
            $status = 'success';
            $code = 200;
        }

        return [
            'msg' => $msg,
            'code' => $code,
            'status' => $status,
            'payload' => $tranx,
            'data' => $order,
        ];
    }

    /**
     * Subscribe the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $action
     * @return \Illuminate\Http\Response
     */
    public function makeSaving(Request $request)
    {
        $subscription = Auth::user()->subscriptions()->where([
            ['status', '!=', 'complete'],
            ['status', '!=', 'withdraw'],
            ['status', '!=', 'closed'],
        ])->latest()->first();

        $key = 'subscription';
        $validator = Validator::make($request->all(), [
            'days' => ['required', 'numeric', 'min:1', 'max:'.$subscription->plan->duration],
        ], [
            'days.min' => 'You have to save for at least 1 day.',
            'days.max' => "You cannot save for more than {$subscription->plan->duration} days.",
        ]);

        if ($validator->fails()) {
            return $this->validatorFails($validator);
        }

        if (! $subscription) {
            $msg = 'You do not have an active subscription';
        } elseif ($subscription->days_left <= 1) {
            $msg = 'You have completed the saving circle for this subscription, you would be notified when your food bag is ready for pickup or delivery.';
        } else {
            $save = new Saving([
                'user_id' => Auth::id(),
                'days' => $request->days,
                'amount' => $subscription->plan->amount / $subscription->plan->duration,
                'due' => $subscription->plan->amount / $subscription->plan->duration,
            ]);

            $savings = $subscription->savings()->save($save);
            $trans = $savings->transaction();
            $trans->create([
                'user_id' => Auth::id(),
                'reference' => Str::random(12),
                'method' => 'direct',
                'amount' => $subscription->plan->amount * $request->days,
                'due' => $subscription->plan->amount * $request->days,
            ]);

            $subscription->status = $subscription->days_left >= 1 ? 'active' : 'complete';
            $subscription->save();
            $subscription = Auth::user()->subscriptions()->where([
                ['status', '!=', 'complete'],
                ['status', '!=', 'withdraw'],
                ['status', '!=', 'closed'],
            ])->latest()->first();

            $key = 'deposit';
            $_amount = money($savings->amount * $request->days);
            $_left = $subscription->days_left;
            $msg = ! $subscription
                ? 'You do not have an active subscription'
                : "You have successfully made a {$savings->days} day savings of {$_amount} for the {$subscription->plan->title} plan, you now have only {$_left} days left to save up.";
        }

        return $this->buildResponse([
            'message' => $msg,
            'status' => $status ?? (! $subscription ? 'info' : 'success'),
            'response_code' => 200,
            $key => $subscription ?? [],
        ]);
    }

    /**
     * Delete a transaction and related models
     * The most appropriate place to use this is when a user cancels a transaction without
     * completing payments, although there are limitless use cases.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function terminateTransaction(Request $request)
    {
        $deleted = false;
        if ($transaction = Transaction::whereReference($request->reference)->where('user_id', Auth::id())->with(['transactable'])->first()) {
            if ($transaction->transactable) {
                $transaction->transactable->delete();
            }
            $transaction->delete();
            $deleted = true;
        }

        return $this->buildResponse([
            'message' => $deleted ? "Transaction with reference: {$request->reference} successfully deleted." : 'Transaction not found',
            'status' => ! $deleted ? 'info' : 'success',
            'response_code' => 200,
        ]);
    }
}
