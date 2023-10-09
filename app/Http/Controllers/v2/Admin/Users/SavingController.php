<?php

namespace App\Http\Controllers\v2\Admin\Users;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SavingCollection;
use App\Http\Resources\SavingResource;
use App\Models\Cooperative;
use App\Models\Saving;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Payment\PaystackProcessor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SavingController extends Controller
{
    /**
     * Display a listing of the user's savings
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @param  int  $subscription
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, User $user, $subscription_id)
    {
        $this->authorize('usable', 'users');

        $cooperative = null;
        if ($request->cooperative_id) {
            /** @var \App\Models\Cooperative */
            $cooperative = Cooperative::whereId($request->cooperative_id)
                ->orWhere('slug', $request->cooperative_id)
                ->firstOrFail();

            if ($subscription_id !== 'all') {
                /** @var \App\Models\Subscription */
                $subscription = $cooperative->subscriptions()->whereId($subscription_id)->firstOrFail();
                $query = $subscription->allSavings();
            } else {
                $query = $cooperative->savings();
            }
        } else {
            if ($subscription_id !== 'all') {
                /** @var \App\Models\Subscription */
                $subscription = $user->subscriptions()->whereId($subscription_id)->firstOrFail();
                $query = $subscription->allSavings();
            } else {
                $query = $user->savings();
            }
        }

        // Filter by status
        $query->when(
            $request->status && in_array($request->status, ['rejected', 'pending', 'complete']),
            function ($query) use ($request) {
                $query->where('status', $request->get('status'));
            }
        );

        // Set default period
        $period_placeholder = Carbon::now()->subDays(30)->format('Y/m/d') . '-' . Carbon::now()->addDays(2)->format('Y/m/d');

        // Get period
        $period = $request->period == '0' ? [] : explode('-', urldecode($request->get('period', $period_placeholder)));

        $query->when(isset($period[0]), function ($query) use ($period) {
            // Filter by period
            $query->whereBetween('created_at', [new Carbon($period[0]), (new Carbon($period[1]))->addDay()]);
        });

        $query->orderBy('id', 'DESC');

        /** @var \App\Models\Saving */
        $savings = $query->paginate($request->get('limit', 30));

        $msg = $savings->isEmpty() ? 'You have not made any savings.' : HttpStatus::message(HttpStatus::OK);

        return (new SavingCollection($savings))->additional([
            'message' => $msg,
            'status' => $savings->isEmpty() ? 'info' : 'success',
            'response_code' => HttpStatus::OK,
            'period' => implode(' to ', $period),
            'date_range' => $period,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @param  int  $subscription
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, User $user, $subscription_id)
    {
        $this->authorize('usable', 'users');

        // Validate this request
        $this->validate($request, [
            'inline' => ['nullable', 'boolean'],
            'cooperative_id' => 'nullable|exists:cooperatives,id',
        ], [
            'cooperative_id.exists' => 'The selected cooperative does not exist.',
        ]);

        $cooperative = null;
        if ($request->cooperative_id) {
            /** @var \App\Models\Cooperative */
            $cooperative = Cooperative::whereId($request->cooperative_id)
                ->orWhere('slug', $request->cooperative_id)
                ->firstOrFail();

            /** @var \App\Models\Subscription */
            $subscription = $cooperative->subscriptions()->whereId($subscription_id)->firstOrFail();
            $savings = $subscription->allSavings();
            $handler = $cooperative;
        } else {
            /** @var \App\Models\Subscription */
            $subscription = $user->subscriptions()->whereId($subscription_id)->firstOrFail();
            $savings = $subscription->allSavings();
            $handler = $user;
        }

        // Validate this request
        $this->validate($request, [
            'days' => ['required', 'numeric', 'min:1', 'max:' . $subscription->days_left],
            'payment_method' => 'nullable|in:wallet,paystack',
        ], [
            'days.min' => 'You have to save for at least 1 day.',
            'days.max' => "You cannot save for more than {$subscription->days_left} days.",
        ]);

        // Set the payment info
        $method = $request->get('payment_method', 'paystack');

        // Add the method to the request
        $request->merge(['method' => $method]);

        $due = round(($subscription->plan->amount / $subscription->plan->duration) * $request->days, 2);
        $fees = round(($subscription->bag->fees / $subscription->plan->duration) * $request->days, 2);
        $amount = round($due + $fees, 2);
        $reference = config('settings.trx_prefix', 'AGB-') . \Str::random(15);

        // Pay with wallet
        if ($method === 'wallet') {
            if ($handler->wallet_balance >= $amount) {
                $tranx = [
                    'reference' => $reference,
                    'method' => 'wallet',
                ];

                $handler->wallet()->create([
                    'reference' => $reference,
                    'amount' => $amount,
                    'type' => 'debit',
                    'source' => 'Savings',
                    'detail' => __('Payment for :0 subscription', [$subscription->plan->title]),
                ]);

                $transaction = $this->initialize($user, $due, $fees, $amount, $reference, $savings, $request);

                return (new SavingResource($transaction->transactable))->additional([
                    'message' => $msg ?? HttpStatus::message(HttpStatus::ACCEPTED),
                    'status' => ! $subscription ? 'info' : 'success',
                    'response_code' => $code ?? HttpStatus::ACCEPTED,
                    'payload' => $tranx ?? [],
                    'amount' => $amount,
                    'cooperative_id' => $request->cooperative_id,
                ])->response()->setStatusCode($response['code'] ?? HttpStatus::ACCEPTED);
            } else {
                return $this->responseBuilder([
                    'message' => __(':0 not have enough funds in :1 wallet', [
                        $request->cooperative_id ? $handler->name . ' does' : 'You do',
                        $request->cooperative_id ? 'the cooperative' : 'your',
                    ]),
                    'status' => 'error',
                    'response_code' => HttpStatus::BAD_REQUEST,
                ], HttpStatus::BAD_REQUEST);
            }
        } else {
            $paystack = new PaystackProcessor($request, $user);

            return $paystack->initialize(
                $amount,
                function ($reference, $tranx, $real_due, $msg) use ($subscription, $request, $user, $due, $fees, $savings) {
                    $transaction = $this->initialize($user, $due, $fees, $real_due / 100, $reference, $savings, $request);

                    return (new SavingResource($transaction->transactable))->additional([
                        'message' => $msg ?? HttpStatus::message(HttpStatus::ACCEPTED),
                        'status' => ! $subscription ? 'info' : 'success',
                        'response_code' => $code ?? HttpStatus::ACCEPTED,
                        'payload' => $tranx ?? [],
                        'amount' => $real_due / 100,
                        'cooperative_id' => $request->cooperative_id,
                    ])->response()->setStatusCode($response['code'] ?? HttpStatus::ACCEPTED);
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
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @param  int  $subscription_id
     * @param  string  $reference
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, User $user, $subscription_id, $id)
    {
        $this->authorize('usable', 'users');

        if ($request->cooperative_id) {
            /** @var \App\Models\Cooperative */
            $user = Cooperative::whereId($request->cooperative_id)
                ->orWhere('slug', $request->cooperative_id)
                ->firstOrFail();
        }

        /** @var \App\Models\Saving */
        $saving = $user->savings()
            ->when($request->cooperative_id && ! $request->get_all, function (Builder $query) use ($request) {
                $query->where('savings.user_id', $request->user()->id);
            })
            ->where('savings.id', $id)
            ->where('subscription_id', $subscription_id)
            ->firstOrFail();

        return (new SavingResource($saving))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @param  int  $subscription_id
     * @param  string  $reference
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user, $subscription_id, $reference)
    {
        $this->authorize('usable', 'users');

        // Validate this request
        $this->validate($request, [
            'cooperative_id' => 'nullable|exists:cooperatives,id',
        ], [
            'cooperative_id.exists' => 'The selected cooperative does not exist.',
        ]);

        $transaction = Transaction::where('reference', $reference)->where(function ($q) {
            $q->where('status', 'pending');
            $q->orWhere('webhook->data->status', 'success');
        })->first();
        ! $transaction && abort(404, 'We are unable to find this transaction, it may have previously been verified.');

        // Set the payment info
        $method = strtolower($transaction->method ?? 'wallet');

        // Add the reference to the request
        $request->merge(['reference' => $reference, 'method' => $method]);

        if ($method === 'wallet') {
            $tranx = new \stdClass();
            $tranx->data = new \stdClass();
            $tranx->data->status = 'failed';

            /** @var \App\Models\CooperativeWallet|\App\Models\Wallet */
            $wallet = app($request->cooperative_id ? CooperativeWallet::class : Wallet::class);
            if ($request->cooperative_id) {
                $wallet->whereCooperativeId($request->cooperative_id);
            } else {
                $wallet->whereUserId($user->id);
            }

            if ($wallet->whereReference($reference)->exists()) {
                $tranx->data->status = 'success';
            }

            $response = $this->verify($request, $tranx, $transaction);

            return (new SavingResource($response['saving']))->additional([
                'message' => $response['msg'] ?? $msg ?? HttpStatus::message(HttpStatus::ACCEPTED),
                'status' => $response['status'] ?? 'success',
                'response_code' => $response['code'] ?? HttpStatus::ACCEPTED,
                'payload' => $response['payload'] ?? $tranx ?? new \stdClass(),
                'subscription' => $response['data'] ?? new \stdClass(),
                'reference' => $reference,
            ])->response()->setStatusCode($response['code'] ?? HttpStatus::ACCEPTED);
        } else {
            $paystack = new PaystackProcessor($request, $user);

            return $paystack->verify(
                function ($reference, $tranx, $msg, $code) use ($request, $transaction) {
                    $response = $this->verify($request, $tranx, $transaction);

                    return (new SavingResource($response['saving']))->additional([
                        'message' => $response['msg'] ?? $msg ?? HttpStatus::message(HttpStatus::ACCEPTED),
                        'status' => $response['status'] ?? 'success',
                        'response_code' => $response['code'] ?? HttpStatus::ACCEPTED,
                        'payload' => $response['payload'] ?? $tranx ?? new \stdClass(),
                        'subscription' => $response['data'] ?? new \stdClass(),
                        'reference' => $reference,
                    ])->response()->setStatusCode($response['code'] ?? HttpStatus::ACCEPTED);
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
    protected function initialize($user, $amount, $fees, $due, $reference, $savings, $request)
    {
        /** @var \App\Models\Saving */
        $saving = $savings->save(new Saving([
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_ref' => $reference,
            'days' => $request->days,
            'amount' => $amount,
            'due' => $due,
        ]));

        return $saving->transaction()->create([
            'user_id' => $user->id,
            'reference' => $reference,
            'method' => ucfirst($request->payment_method ?? 'paystack'),
            'status' => 'pending',
            'amount' => $amount,
            'fees' => $fees,
            'due' => $due,
        ]);
    }

    /**
     * Process a saving's payment request
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  object  $tranx  Transaction data returned by paystack
     * @param  \App\Models\Transaction  $transaction  Transaction model
     * @return array
     */
    protected function verify(Request $request, object $tranx, Transaction $transaction = null): array
    {
        $msg = 'Unable to verify transaction.';
        $code = HttpStatus::UNPROCESSABLE_ENTITY;
        $status = 'error';
        $subscription = null;
        $saving = null;

        if ($transaction) {
            /** @var \App\Models\Saving */
            $saving = $transaction->transactable;

            // $saving = Saving::where('payment_ref', $request->reference)->where('status', 'pending')->first();

            if (
                $saving &&
                $saving->status === 'pending' ||
                (
                    isset($transaction?->webhook['data']['status']) &&
                    $transaction?->webhook['data']['status'] === 'success'
                )
            ) {
                if ($request->cooperative_id) {
                    /** @var \App\Models\Subscription */
                    $subscription = Cooperative::findOrFail($request->cooperative_id)
                        ->subscriptions()
                        ->find($saving->subscription_id);
                } else {
                    /** @var \App\Models\Subscription */
                    $subscription = $saving->user->subscriptions()->find($saving->subscription_id);
                }

                if ($subscription) {
                    $_amount = money($transaction->amount);
                    $_left = $subscription->days_left - $saving->days;
                } else {
                    $tranx->data->status = 'failed';
                    $msg = 'We could not find the subscription for this saving.';
                }

                if ('success' === $tranx->data->status) {
                    $saving->status = 'complete';
                    $transaction->status = 'complete';

                    $canPayRef = in_array(config('settings.referral_mode', 2), [1, 2]) &&
                        config('settings.referral_system', false) &&
                        !isset($transaction?->webhook['data']['status']);

                    if ($canPayRef && $saving->user->referrer && $saving->days < 1) {
                        $saving->user->referrer->wallet()->create([
                            'amount' => config('settings.referral_bonus', 1),
                            'type' => 'credit',
                            'source' => 'Referral Bonus',
                            'detail' => __('Referral bonus for :0\'s first saving.', [$saving->user->fullname]),
                        ]);
                    }

                    if ($_left <= 1) {
                        $subscription->status = 'complete';
                        $msg = 'You have completed the saving circle for this subscription, you would be notified when your food bag is ready for pickup or delivery.';
                    } else {
                        $subscription->status = 'active';
                        $plantitle = $subscription->plan->title . (stripos($subscription->plan->title, 'plan') !== false ? '' : ' plan');
                        $msg = __(
                            'You have successfully made :days day(s) savings of :amount for the :plan, you now have only :left days left to save up.',
                            [
                                'days' => $saving->days,
                                'amount' => $_amount,
                                'plan' => $plantitle,
                                'left' => $_left,
                            ]
                        );
                    }

                    $subscription->fees_paid += $transaction->fees;
                    $subscription->next_date = $subscription->setDateByInterval(Carbon::parse(now()));

                    !isset($transaction?->webhook['data']['status']) && $subscription->save();
                    $code = HttpStatus::ACCEPTED;
                } else {
                    $saving->status = 'rejected';
                    $transaction->status = 'rejected';
                }

                if (!isset($transaction?->webhook['data']['status'])) {
                    $saving->save();
                    $transaction->save();
                }
                $status = 'success';
            }
        }

        return [
            'msg' => $msg,
            'code' => $code,
            'status' => $status,
            'payload' => $tranx ?? new \stdClass(),
            'data' => $subscription,
            'saving' => $saving,
        ];
    }

    /**
     * Delete a transaction and related models
     * The most appropriate place to use this is when a user cancels a transaction without
     * completing payments, although there are limitless use cases.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function destroy(Request $request, User $user, $reference)
    {
        $this->authorize('usable', 'users');

        $transaction = $user->transactions()->whereStatus('pending')->whereReference($reference)->first();
        ! $transaction && abort(404, 'We are unable to find this transaction.');

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