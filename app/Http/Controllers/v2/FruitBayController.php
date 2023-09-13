<?php

namespace App\Http\Controllers\v2;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\FruitbayCollection;
use App\Http\Resources\FruitbayResource;
use App\Http\Resources\OrderResource;
use App\Models\FruitBay;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\Payment\PaystackProcessor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FruitBayController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = FruitBay::where('available', true)
            ->when($request->has('category_id'), function ($q) use ($request) {
                $q->whereHas('category', function (Builder $q) use ($request) {
                    $q->where('id', $request->category_id);
                    $q->orWhere('slug', $request->category_id);
                });
            })
            ->when($request->has('q'), function (Builder $q) use ($request) {
                $q->where('name', 'like', "%{$request->q}%");
                $q->orWhereFullText('description', $request->q);
                $q->orWhereHas('category', function (Builder $q) use ($request) {
                    $q->where('name', 'like', "%{$request->q}%");
                });
            });

        $items = $query->paginate($request->get('limit', 15));

        return (new FruitbayCollection($items))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(FruitBay $fruitbay)
    {
        return (new FruitbayResource($fruitbay))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Initialize FruitBay Payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $method
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'cart' => ['bail', 'required', 'array'],
            'cart.*.item_id' => ['bail', 'required', 'exists:fruit_bays,id'],
            'cart.*.qty' => ['bail', 'numeric', 'required', 'min:1'],
            'address' => ['nullable', 'string', 'max:255'],
            'inline' => ['nullable', 'boolean'],
            'payment_method' => ['nullable', 'string', 'in:wallet,paystack'],
        ], [], [
            'cart.*.item_id' => ';'
        ]);

        $user = $request->user();
        $items = collect($request->cart)->map(fn ($k) => collect($k)->except('qty'))->flatten()->all();
        $method = $request->get('payment_method', 'paystack');
        $cartItems = FruitBay::whereIn('id', $items)->get();

        if ($cartItems->isNotEmpty()) {
            $cart = $cartItems->map(function ($item) use ($request) {
                $value = collect($request->cart)->filter(fn ($k) => $k['item_id'] == $item->id)->first();
                if ($item) {
                    $item->total = $item->price * $value['qty'];
                    $item->total_fees = $item->fees * $value['qty'];
                    $item->qty = $value['qty'];
                }

                return $item;
            })->filter(fn ($k) => $k !== null);

            // Calculate the global shipping fee
            $delivery_method = $request->delivery_method ?? 'delivery';
            $globalSshippingFee = config('settings.paid_shipping', false) || $delivery_method == 'delivery'
                ? config('settings.shipping_fee', 0)
                : 0;

            if ($request->address && $request->address !== ($user->address['shipping'] ?? '')) {
                $address = $user->address;
                $address['shipping'] = $request->address;
                $user->address = $address;
                $user->save();
            }

            if ($cart->count() <= 0) {
                return $this->responseBuilder([
                    'message' => 'You have too few items in your basket, or some items may no longer be available.',
                    'status' => 'error',
                    'response_code' => HttpStatus::UNPROCESSABLE_ENTITY,
                ], HttpStatus::UNPROCESSABLE_ENTITY);
            } else {
                $shipping_fees = $cart->sum('total_fees') + $globalSshippingFee;
                $due = $cart->sum('total');
                $amount = round($due + $shipping_fees, 2);
                $reference = config('settings.trx_prefix', 'AGB-') . Str::random(15);

                try {
                    if ($method === 'wallet') {
                        if ($request->user()->wallet_balance >= $amount) {
                            $tranx = [
                                'reference' => $reference,
                                'method' => 'wallet',
                            ];

                            $transaction = $request->user()->wallet()->create([
                                'reference' => $reference,
                                'amount' => $amount,
                                'type' => 'debit',
                                'source' => 'Cart Checkout',
                                'detail' => trans_choice('Payment for order of :0 items', $cart->count(), [$cart->count()]),
                            ]);


                            $transaction = $this->initialize(
                                $user,
                                $due,
                                $shipping_fees,
                                $amount,
                                $reference,
                                $cart,
                                $request
                            );

                            return (new OrderResource($transaction->transactable))->additional([
                                'message' => $msg ?? HttpStatus::message(HttpStatus::ACCEPTED),
                                'status' => 'success',
                                'response_code' => HttpStatus::ACCEPTED,
                                'payload' => $tranx ?? [],
                                'amount' => $due,
                            ])->response()->setStatusCode(HttpStatus::ACCEPTED);
                        } else {
                            return $this->responseBuilder([
                                'message' => 'You do not have enough funds in your wallet',
                                'status' => 'error',
                                'response_code' => HttpStatus::BAD_REQUEST,
                            ], HttpStatus::BAD_REQUEST);
                        }
                    } else {
                        $paystack = new PaystackProcessor($request, $user);

                        return $paystack->initialize(
                            $amount,
                            function ($reference, $tranx, $real_due, $msg) use ($shipping_fees, $cart, $due, $user, $request) {
                                $transaction = $this->initialize(
                                    $user,
                                    $due,
                                    $shipping_fees,
                                    $real_due / 100,
                                    $reference,
                                    $cart,
                                    $request
                                );
                                return (new OrderResource($transaction->transactable))->additional([
                                    'message' => $msg ?? HttpStatus::message(HttpStatus::ACCEPTED),
                                    'status' => 'success',
                                    'response_code' => HttpStatus::ACCEPTED,
                                    'payload' => $tranx ?? [],
                                    'amount' => $real_due / 100,
                                ])->response()->setStatusCode(HttpStatus::ACCEPTED);
                            },
                            function ($error, $code) {
                                return $this->responseBuilder([
                                    'message' => $error,
                                    'status' => 'error',
                                    'response_code' => $code,
                                ], $code);
                            },
                            true
                        );
                    }
                } catch (ApiException | \InvalidArgumentException | \ErrorException $e) {
                    return $this->responseBuilder([
                        'message' => $e->getMessage(),
                        'status' => 'error',
                        'response_code' => 422,
                        'due' => $due,
                        'payload' => $e instanceof ApiException ? $e->getResponseObject() : [],
                    ]);
                }
            }
        } else {
            return $this->responseBuilder([
                'message' => 'One or more items on your cart no are no longer available.',
                'status' => 'error',
                'response_code' => 403,
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $subscription_id
     * @param  string  $reference
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $reference)
    {
        // Validate this request
        $this->validate($request, [
            'payment_method' => 'nullable|in:wallet,paystack',
        ]);

        /** @var \App\Models\User */
        $user = $request->user();

        $transaction = Transaction::where('reference', $reference)->where('status', 'pending')->first();
        !$transaction && abort(404, 'We are unable to find this transaction.');

        // Set the payment info
        $method = strtolower($transaction->method ?? 'wallet');

        // Add the reference to the request
        $request->merge(['reference' => $reference]);

        if ($method === 'wallet') {
            $tranx = new \stdClass();
            $tranx->data = new \stdClass();
            $tranx->data->status = 'failed';

            /** @var \App\Models\Wallet */
            $wallet = $user->wallet();
            if ($wallet->whereReference($reference)->exists()) {
                $tranx->data->status = 'success';
            }

            $response = $this->verify($tranx, $transaction);

            return (new OrderResource($response['data']))->additional([
                'message' => $response['msg'] ?? HttpStatus::message(HttpStatus::ACCEPTED),
                'status' => 'success',
                'response_code' => HttpStatus::ACCEPTED,
                'payload' => $response['payload'] ?? $tranx ?? new \stdClass(),
                'reference' => $reference,
            ])->response()->setStatusCode(HttpStatus::ACCEPTED);
        } else {
            $paystack = new PaystackProcessor($request, $user);

            return $paystack->verify(
                function ($reference, $tranx, $msg, $code) use ($request, $transaction) {
                    $response = $this->verify($tranx, $transaction);

                    return (new OrderResource($response['data']))->additional([
                        'message' => $response['msg'] ?? HttpStatus::message(HttpStatus::ACCEPTED),
                        'status' => 'success',
                        'response_code' => HttpStatus::ACCEPTED,
                        'payload' => $response['payload'] ?? $tranx ?? new \stdClass(),
                        'reference' => $reference,
                    ])->response()->setStatusCode(HttpStatus::ACCEPTED);
                },
                function ($msg, $code, $tranx) {
                    return $this->responseBuilder([
                        'message' => $msg ?? HttpStatus::message(HttpStatus::UNPROCESSABLE_ENTITY),
                        'status' => 'error',
                        'response_code' => $code,
                    ], [
                        'payload' => $tranx ?? new \stdClass(),
                    ]);
                },
                true
            );
        }
    }

    /** Initialize the saving and transaction.
     *
     * @param  \App\Models\User  $user
     * @param  int  $amount
     * @param  int  $fees
     * @param  int  $due (Amount + Fees)
     * @param  string  $reference
     * @param $savings (\App\Models\Saving)
     * @param  \Illuminate\Http\Request  $request
     */
    protected function initialize($user, $amount, $fees, $due, $reference, $cart, $request)
    {
        $order = Order::create([
            'due' => $due,
            'fees' => $fees,
            'items' => $cart,
            'status' => 'pending',
            'amount' => $amount,
            'user_id' => $user->id,
            'payment' => 'pending',
            'reference' => $reference,
            'delivery_method' => $request->delivery_method,
        ]);

        $transaction = $order->transaction();

        return $transaction->create([
            'reference' => $reference,
            'user_id' => $user->id,
            'method' => ucfirst($request->payment_method ?? 'paystack'),
            'status' => 'pending',
            'amount' => $due,
            'fees' => $fees,
            'due' => $due,
        ]);
    }

    /**
     * Process an order payment
     *
     * @param  object  $tranx                          Transaction data returned by paystack
     * @param  \App\Models\Transaction  $transaction    Transaction model
     * @return array
     */
    public function verify($tranx, Transaction $transaction = null): array
    {
        $msg = 'An unrecoverable error occured';
        $code = HttpStatus::UNPROCESSABLE_ENTITY;
        $status = 'error';
        $order = $transaction->transactable;

        if ($order && $order->payment === 'pending') {
            if ('success' === $tranx->data->status) {
                $order->payment = 'complete';
                $transaction->status = 'complete';
                $msg = 'Your order has been placed successfully, you will be notified whenever it is ready for pickup or delivery.';

                $canPayRef = in_array(config('settings.referral_mode', 2), [1, 3]) &&
                    config('settings.referral_system', false);

                $countUserOrders = $order->user->orders()->paymentStatus('complete')->count();

                if ($canPayRef && $order->user->referrer && $countUserOrders < 1) {
                    $order->user->referrer->wallet()->create([
                        'amount' => config('settings.referral_bonus', 1),
                        'type' => 'credit',
                        'source' => 'Referral Bonus',
                        'detail' => __('Referral bonus for :0\'s first order.', [$order->user->fullname]),
                    ]);
                }
            } else {
                $order->payment = 'rejected';
                $order->status = 'rejected';
                $transaction->status = 'rejected';
            }
            $code = HttpStatus::ACCEPTED;
            $order->save();
            $transaction->save();
            $status = 'success';
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
     * Delete a transaction and related models
     * The most appropriate place to use this is when a user cancels a transaction without
     * completing payments, although there are limitless use cases.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function destroy(Request $request, $reference)
    {
        $user = $request->user();
        $transaction = $user->transactions()->whereStatus('pending')->whereReference($reference)->first();
        !$transaction && abort(404, 'We are unable to find this transaction.');

        if ($transaction->transactable) {
            $transaction->transactable->delete();
        }
        $transaction->delete();

        return $this->responseBuilder([
            'message' => "Transaction with reference: {$reference} successfully deleted.",
            'status' => 'success',
            'response_code' => HttpStatus::ACCEPTED,
        ]);
    }
}
