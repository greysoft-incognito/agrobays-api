<?php

namespace App\Http\Controllers;

use App\Models\FruitBay;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\Saving;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yabacon\Paystack;
use Yabacon\Paystack\Exception\ApiException;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Initialize Payment.
     *
     * @param  \Illuminate\Http\Client\Request  $request
     * @param  String $action
     * @return \Illuminate\Http\Response
     */
    public function initializeSaving(Request $request)
    {
        if (($validator = Validator::make($request->all(), [
            'subscription_id' => ['required', 'numeric'],
        ]))->stopOnFirstFailure()->fails()) {
            return $this->validatorFails($validator, 'subscription_id');
        }

        $subscription = Auth::user()->subscription()->find($request->subscription_id);

        $real_due = 0;
        $code = 403;

        if (!$subscription)
        {
            $msg = 'You do not have an active subscription';
        }
        else
        {
            if (($validator = Validator::make($request->all(), [
                'days' => ['required', 'numeric', 'min:1', 'max:'.$subscription->days_left],
            ], [
                'days.min' => 'You have to save for at least 1 day.',
                'days.max' => "You cannot save for more than {$subscription->days_left} days."
            ]))->stopOnFirstFailure()->fails()) {
                return $this->validatorFails($validator, 'email');
            }

            $due = round(($subscription->plan->amount / $subscription->plan->duration) * $request->days, 2);
            try {
                $paystack = new Paystack(env("PAYSTACK_SECRET_KEY"));
                $reference = config('settings.trx_prefix', 'AGB-') . Str::random(12);

                $real_due = $due*100;

                // Dont initialize paystack for inline transaction
                if ($request->inline) {
                    $tranx = [
                        'data' => ['reference' => $reference]
                    ];
                    $real_due = $due;
                } else {
                    $tranx = $paystack->transaction->initialize([
                      'amount' => $real_due,       // in kobo
                      'email' => Auth::user()->email,         // unique to customers
                      'reference' => $reference,         // unique to transactions
                      'callback_url' => config('settings.payment_verify_url', route('payment.paystack.verify'))
                    ]);
                    $real_due = $due;
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
                    'method' => 'Paystack',
                    'status' => 'pending',
                    'amount' => $due,
                    'due' => $due,
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
            'message' => $msg??'OK',
            'status' =>  !$subscription ? 'info' : 'success',
            'response_code' => $code ?? 200, //202
            'payload' => $tranx??[],
            'transaction' => $transaction??[],
            'amount' => $real_due,
        ]);
    }


    /**
     * Initialize FruitBay Payment.
     *
     * @param  \Illuminate\Http\Client\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function initializeFruitBay(Request $request)
    {
        if (($validator = Validator::make($request->all(), [
            'cart' => ['required', 'array'],
        ]))->stopOnFirstFailure()->fails()) {
            return $this->validatorFails($validator, 'cart');
        }

        $items = collect($request->cart)->map(fn($k)=>collect($k)->except('qty'))->flatten()->all();
        if (($available = FruitBay::whereIn('id', $items)->get('id'))) {
            $cart = collect($request->cart)->map(function($value) {
                $item = FruitBay::find($value['item_id']);
                if ($item) {
                    $item->total = $item->price * $value['qty'];
                    $item->qty = $value['qty'];
                }
                return $item;
            })->filter(fn($k) => $k !== null);

            if ($request->address && $request->address !== Auth::user()->address){
                $user = User::find(Auth::id());
                $user->address = $request->address;
                $user->save();
            }

            $real_due = 0;
            $code = 403;

            if ($cart->count() <= 0)
            {
                $msg = 'You have too few items in your basket, or some items may no longer be available.';
            }
            else
            {
                $due = $cart->mapWithKeys(function($value, $key) {
                    return [$key => $value->total];
                })->sum();
                $real_due = $due;

                try {
                    $paystack = new Paystack(env("PAYSTACK_SECRET_KEY"));
                    $reference = config('settings.trx_prefix', 'AGB-') . Str::random(15);

                    // Dont initialize paystack for inline transaction
                    if ($request->inline) {
                        $tranx = [
                            'data' => ['reference' => $reference]
                        ];
                    } else {
                        $tranx = $paystack->transaction->initialize([
                            'amount' => $due*100,       // in kobo
                            'email' => Auth::user()->email,         // unique to customers
                            'reference' => $reference,         // unique to transactions
                            'callback_url' => config('settings.payment_verify_url', route('payment.paystack.verify'))
                        ]);
                    }

                    $code = 200;

                    $order = Order::create([
                        'due' => $due,
                        'items' => $cart,
                        'status' => 'pending',
                        'amount' => $due,
                        'user_id' => Auth::id(),
                        'payment' => 'pending',
                        'reference' => $reference,
                    ]);

                    $transaction = $order->transaction();
                    $transaction->create([
                        'user_id' => Auth::id(),
                        'reference' => $reference,
                        'method' => 'Paystack',
                        'status' => 'pending',
                        'amount' => $due,
                        'due' => $due,
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
            'message' => $msg??'OK',
            'status' =>  $code !== 200 ? 'error' : 'success',
            'response_code' => $code ?? 200, //202
            'payload' => $tranx??[],
            'items' => $cart ?? [$subscription ?? null],
            'amount' => $real_due,
        ]);
    }


    /**
     * Verify the paystack payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  String $action
     * @return \Illuminate\Http\Response
     */
    public function paystackVerify(Request $request)
    {
        $msg = 'Invalid Transaction.';
        $status = 'error';
        $set_type = 'deposit';
        $code = 403;
        if(!$request->reference){
            $msg = 'No reference supplied';
        }

        try {
            $paystack = new Paystack(env("PAYSTACK_SECRET_KEY"));
            $tranx = $paystack->transaction->verify([
              'reference' => $request->reference,   // unique to transactions
            ]);

            $transaction = Transaction::where('reference', $request->reference)->where('status', 'pending')->first();
            throw_if(!$transaction, \ErrorException::class, 'Transaction not found.');

            if (($transactable = $transaction->transactable) instanceof Saving) {
                $processSaving = $this->processSaving($request, $tranx, $transactable);
            }
            elseif (($transactable = $transaction->transactable) instanceof Order) {
                $processSaving = $this->processOrder($request, $tranx, $transactable);
                $set_type = 'order';
            }
            extract($processSaving);
        } catch (ApiException | \InvalidArgumentException | \ErrorException $e) {
            $payload = $e instanceof ApiException ? $e->getResponseObject() : [];
            Log::error($e->getMessage(), ['url'=>url()->full(), 'request' => $request->all()]);
            return $this->buildResponse([
                'message' => $e->getMessage(),
                'status' => 'error',
                'response_code' => 422,
                'payload' => $payload,
            ]);
        }

        return $this->buildResponse([
            'message' => $msg??'OK',
            'status' => $status ?? 'success',
            'response_code' => $code ?? 200,
            'payload' => $tranx??[],
            $set_type => $subscription??$order??[],
        ]);
    }

    /**
     * Process a saving's payment request
     *
     * @param  \Illuminate\Http\Request  $request
     * @param object $tranx                          Transaction data returned by paystack
     * @param object $saving                          Transaction saving retrieved from transaction
     * @return array
     */
    public function processSaving(Request $request, $tranx, $saving = null): array
    {
        $msg = "An unrecoverable error occured";
        $code = 422;
        $status = 'error';
        $subscription = [];
        // $saving = Saving::where('payment_ref', $request->reference)->where('status', 'pending')->first();
        if ($saving && $saving->status === 'pending') {
            $subscription = User::find($saving->user_id)->subscription()->where('id', $saving->subscription_id)->first();
            $_amount = money($tranx->data->amount/100);
            $_left = $subscription->days_left - $saving->days;

            if ('success' === $tranx->data->status) {
                $saving->status = 'complete';
                $trns = $saving->transaction;
                $trns->status = 'complete';

                if ($_left <= 1)
                {
                    $subscription->status = 'complete';
                    $msg = 'You have completed the saving circle for this subscription, you would be notified when your food bag is ready for pickup or delivery.';
                }
                else
                {
                    $subscription->status = 'active';
                    $plantitle = $subscription->plan->title . (stripos($subscription->plan->title, 'plan') !== false ? '' : ' plan');
                    $msg = "You have successfully made {$saving->days} day(s) savings of {$_amount} for the {$plantitle}, you now have only {$_left} days left to save up.";
                }
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
            'subscription' => $subscription,
        ];
    }

    /**
     * Process an order payment
     *
     * @param  \Illuminate\Http\Request  $request
     * @param object $tranx                          Transaction data returned by paystack
     * @param object $order                          Transaction order retrieved from transaction
     * @return array
     */
    public function processOrder(Request $request, $tranx, $order = null): array
    {
        $msg = "An unrecoverable error occured";
        $code = 422;
        $status = 'error';
        if ($order && $order->payment === 'pending')
        {
            $trns = $order->transaction;
            if ('success' === $tranx->data->status) {
                $order->payment = 'complete';
                $trns->status = 'complete';
                $msg = "Your order has been placed successfully, you will be notified whenever it is ready for pickup or delivery.";
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
            'order' => $order,
        ];
    }

    /**
     * Subscribe the user.
     *
     * @param  \Illuminate\Http\Client\Request  $request
     * @param  String $action
     * @return \Illuminate\Http\Response
     */
    public function makeSaving(Request $request)
    {
        $subscription = Auth::user()->subscription;

        $key = 'subscription';
        $validator = Validator::make($request->all(), [
            'days' => ['required', 'numeric', 'min:1', 'max:'.$subscription->plan->duration],
        ], [
            'days.min' => 'You have to save for at least 1 day.',
            'days.max' => "You cannot save for more than {$subscription->plan->duration} days."
        ]);

        if ($validator->fails()) {
            return $this->validatorFails($validator);
        }

        if (!$subscription)
        {
            $msg = 'You do not have an active subscription';
        }
        elseif ($subscription->days_left <= 1)
        {
            $msg = 'You have completed the saving circle for this subscription, you would be notified when your food bag is ready for pickup or delivery.';
        }
        else
        {
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
            $subscription = Auth::user()->subscription;

            $key = 'deposit';
            $_amount = money($savings->amount*$request->days);
            $_left = $subscription->days_left;
            $msg = !$subscription
                ? 'You do not have an active subscription'
                : "You have successfully made a {$savings->days} day savings of {$_amount} for the {$subscription->plan->title} plan, you now have only {$_left} days left to save up.";
        }

        return $this->buildResponse([
            'message' => $msg,
            'status' =>  $status ?? (!$subscription ? 'info' : 'success'),
            'response_code' => 200,
            $key => $subscription??[],
        ]);
    }

    /**
     * Delete a transaction and related models
     * The most appropriate place to use this is when a user cancels a transaction without
     * completing payments, although there are limitless use cases.
     *
     * @param Request $request
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
            'message' => $deleted ? "Transaction with reference: {$request->reference} successfully deleted." : "Transaction not found",
            'status' => !$deleted ? 'info' : 'success',
            'response_code' => 200,
        ]);
    }
}