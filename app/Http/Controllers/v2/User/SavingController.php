<?php

namespace App\Http\Controllers\v2\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SavingCollection;
use App\Http\Resources\SavingResource;
use App\Models\Cooperative;
use App\Models\Saving;
use App\Models\Transaction;
use App\Services\Payment\PaystackProcessor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use stdClass;

class SavingController extends Controller
{
    /**
     * Display a listing of the user's savings
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $subscription
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $subscription_id)
    {
        /** @var \App\Models\User */
        $user = $request->user();

        $cooperative = null;
        if ($request->cooperative_id) {
            /** @var \App\Models\Cooperative */
            $cooperative = Cooperative::whereId($request->cooperative_id)
                ->orWhere('slug', $request->cooperative_id)
                ->firstOrFail();

            /** @var \App\Models\Subscription */
            $subscription = $cooperative->subscriptions()->whereId($subscription_id)->firstOrFail();
            $query = $subscription->allSavings();
        } else {
            /** @var \App\Models\Subscription */
            $subscription = $user->subscriptions()->whereId($subscription_id)->firstOrFail();
            $query = $subscription->allSavings();
        }

        if (in_array($request->status, ['rejected', 'pending', 'complete'])) {
            $query->where('status', $request->status);
        }

        if ($p = $request->get('period')) {
            $period = explode('-', $p);
            $query->whereBetween('created_at', [new Carbon($period[0]), new Carbon($period[1])]);
        }

        $query->orderBy('id', 'DESC');

        /** @var \App\Models\Saving */
        $savings = $query->paginate($request->get('limit', 30));

        $msg = $savings->isEmpty() ? 'You have not made any savings.' : HttpStatus::message(HttpStatus::OK);
        $last = $savings->last();
        $first = $savings->first();

        $_period = $savings->isNotEmpty()
            ? ($last->created_at->format('Y/m/d') . '-' . $first->created_at->format('Y/m/d'))
            : '';

        return (new SavingCollection($savings))->additional([
            'message' => $msg,
            'status' => $savings->isEmpty() ? 'info' : 'success',
            'response_code' => HttpStatus::OK,
            'period' => $p ? urldecode($p) : $_period,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $subscription
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $subscription_id)
    {
        // Validate this request
        $this->validate($request, [
            'cooperative_id' => 'nullable|exists:cooperatives,id',
        ], [
            'cooperative_id.exists' => 'The selected cooperative does not exist.',
        ]);

        /** @var \App\Models\User */
        $user = $request->user();

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
        $method = $request->get('method', 'paystack');
        $due = round(($subscription->plan->amount / $subscription->plan->duration) * $request->days, 2);
        $fees = round(($subscription->bag->fees / $subscription->plan->duration) * $request->days, 2);
        $amount = round($due + $fees, 2);
        $reference = config('settings.trx_prefix', 'AGB-') . \Str::random(15);

        // Pay with wallet
        if ($request->get('method', $method) === 'wallet') {
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

                return $this->responseBuilder([
                    'message' => $msg ?? HttpStatus::message(HttpStatus::ACCEPTED),
                    'status' => ! $subscription ? 'info' : 'success',
                    'response_code' => $code ?? HttpStatus::ACCEPTED,
                    'payload' => $tranx ?? [],
                    'transaction' => $transaction,
                    'amount' => $amount,
                    'cooperative_id' => $request->cooperative_id,
                ]);
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

                    return $this->responseBuilder([
                        'message' => $msg ?? HttpStatus::message(HttpStatus::ACCEPTED),
                        'status' => ! $subscription ? 'info' : 'success',
                        'response_code' => $code ?? HttpStatus::ACCEPTED,
                        'payload' => $tranx ?? [],
                        'transaction' => $transaction ?? new \stdClass(),
                        'amount' => $real_due / 100,
                        'cooperative_id' => $request->cooperative_id,
                    ]);
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
     * @param  int  $subscription_id
     * @param  string  $reference
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $subscription_id, $id)
    {
        if ($request->cooperative_id) {
            /** @var \App\Models\Cooperative */
            $user = Cooperative::whereId($request->cooperative_id)
                ->orWhere('slug', $request->cooperative_id)
                ->firstOrFail();
        } else {
            /** @var \App\Models\User */
            $user = $request->user();
        }

        /** @var \App\Models\Saving */
        $saving = $user->savings()
            ->when($request->cooperative_id && !$request->get_all, function (Builder $query) use ($request) {
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
     * @param  int  $subscription_id
     * @param  string  $reference
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $subscription_id, $reference)
    {
        // Validate this request
        $this->validate($request, [
            'cooperative_id' => 'nullable|exists:cooperatives,id',
        ], [
            'cooperative_id.exists' => 'The selected cooperative does not exist.',
        ]);

        // Validate this request
        $this->validate($request, [
            'payment_method' => 'nullable|in:wallet,paystack',
        ], [
        ]);

        // Add the reference to the request
        $request->merge(['reference' => $reference]);

        /** @var \App\Models\User */
        $user = $request->user();

        // Set the payment info
        $method = $request->get('method', 'paystack');

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

            $transaction = Transaction::where('reference', $reference)->where('status', 'pending')->first();
            $response = $this->verify($request, $tranx, $transaction);

            return $this->responseBuilder([
                'message' => $response['msg'] ?? $msg ?? HttpStatus::message(HttpStatus::ACCEPTED),
                'status' => $response['status'] ?? 'success',
                'response_code' => $response['code'] ?? HttpStatus::ACCEPTED,
                'payload' => $response['payload'] ?? $tranx ?? new \stdClass(),
                'saving' => $response['saving'] ?? new \stdClass(),
                'subscription' => $response['data'] ?? new \stdClass(),
                'reference' => $reference,
            ]);
        } else {
            $paystack = new PaystackProcessor($request, $user);

            return $paystack->verify(
                function ($reference, $tranx, $msg, $code) use ($request) {
                    $transaction = Transaction::where('reference', $reference)->where('status', 'pending')->first();
                    $response = $this->verify($request, $tranx, $transaction);

                    return $this->responseBuilder([
                        'message' => $response['msg'] ?? $msg ?? HttpStatus::message(HttpStatus::ACCEPTED),
                        'status' => $response['status'] ?? 'success',
                        'response_code' => $response['code'] ?? $code ?? HttpStatus::ACCEPTED,
                        'payload' => $response['payload'] ?? $tranx ?? new \stdClass(),
                        'saving' => $response['saving'] ?? new \stdClass(),
                        'subscription' => $response['data'] ?? new \stdClass(),
                        'reference' => $reference,
                    ]);
                },
                function ($msg, $code, $tranx) {
                    return $this->responseBuilder([
                        'message' => $msg ?? HttpStatus::message(HttpStatus::UNPROCESSABLE_ENTITY),
                        'status' => 'error',
                        'response_code' => $code,
                        'payload' => $tranx ?? new \stdClass(),
                    ]);
                },
                true
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
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
            'method' => 'Paystack',
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
            if ($saving && $saving->status === 'pending') {
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
                    $_amount = money($tranx->data->amount / 100);
                    $_left = $subscription->days_left - $saving->days;
                } else {
                    $tranx->data->status = 'failed';
                    $msg = 'We could not find the subscription for this saving.';
                }

                if ('success' === $tranx->data->status) {
                    $saving->status = 'complete';
                    $transaction->status = 'complete';

                    if ($_left <= 1) {
                        $subscription->status = 'complete';
                        $msg = 'You have completed the saving circle for this subscription, you would be notified when your food bag is ready for pickup or delivery.';
                    } else {
                        $subscription->status = 'active';
                        $plantitle = $subscription->plan->title . (stripos($subscription->plan->title, 'plan') !== false ? '' : ' plan');
                        $msg = __(
                            "You have successfully made :days day(s) savings of :amount for the :plan, you now have only :left days left to save up.",
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

                    $subscription->save();
                    $code = HttpStatus::ACCEPTED;
                } else {
                    $saving->status = 'rejected';
                    $transaction->status = 'rejected';
                }

                $saving->save();
                $transaction->save();
                $status = 'success';
            }
        }

        return [
            'msg' => $msg,
            'code' => $code,
            'status' => $status,
            'payload' => $tranx ?? new \stdClass(),
            'data' => $subscription,
            'saving' => $saving ?? new \stdClass(),
        ];
    }
}
