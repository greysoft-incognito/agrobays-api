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

        if (($validator = Validator::make($request->all(), [
            'days' => ['required', 'numeric', 'min:1', 'max:'.$subscription->days_left],
        ], [
            'days.min' => 'You have to save for at least 1 day.',
            'days.max' => "You cannot save for more than {$subscription->days_left} days."
        ]))->stopOnFirstFailure()->fails()) {
            return $this->validatorFails($validator, 'email');
        }

        $code = 403;

        if (!$subscription)
        {
            $msg = 'You do not have an active subscription';
        }
        else
        {
            $due = round($subscription->plan->amount / $subscription->plan->duration, 2);
            try {
                $paystack = new Paystack(env("PAYSTACK_SECRET_KEY"));
                $reference = Str::random(12);

                $tranx = $paystack->transaction->initialize([
                  'amount' => ($due * $request->days)*100,       // in kobo
                  'email' => Auth::user()->email,         // unique to customers
                  'reference' => $reference,         // unique to transactions
                  'callback_url' => config('settings.payment_verify_url', route('payment.paystack.verify'))
                ]);

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
                    'amount' => $due * $request->days,
                    'due' => $due * $request->days,
                ]);

                $payload = $tranx;

            } catch (ApiException | \InvalidArgumentException $e) {
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
            'payload' => $payload??[],
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

        $cart = collect($request->cart)->map(function($value) {
            $item = FruitBay::find($value['item_id']);
            $item->total = $item->price * $value['qty'];
            $item->qty = $value['qty'];
            return $item;
        });

        if ($request->address && $request->address !== Auth::user()->address){
            User::find(Auth::id())->update([
                'address' => $request->address
            ]);
        }

        $code = 403;

        if ($cart->count() <= 0)
        {
            $msg = 'You have too few items in your basket, please add more to checkout.';
        }
        else
        {
            $due = $cart->mapWithKeys(function($value, $key) {
                return [$key => $value->total];
            })->sum();

            try {
                $paystack = new Paystack(env("PAYSTACK_SECRET_KEY"));
                $reference = Str::random(15);

                $tranx = $paystack->transaction->initialize([
                  'amount' => $due*100,       // in kobo
                  'email' => Auth::user()->email,         // unique to customers
                  'reference' => $reference,         // unique to transactions
                  'callback_url' => config('settings.payment_verify_url', route('payment.paystack.verify'))
                ]);

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

                $payload = $tranx;

            } catch (ApiException | \InvalidArgumentException $e) {
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
            'status' =>  $code !== 200 ? 'error' : 'success',
            'response_code' => $code ?? 200, //202
            'payload' => $payload??[],
        ]);
    }


    /**
     * Verify the paystack payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  String $action
     * @return \Illuminate\Http\Response
     */
    public function paystackVerify(Request $request, $type = 'savings')
    {
        $msg = 'Invalid Transaction.';
        $status = 'error';
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
            }
            extract($processSaving);
        } catch (ApiException | \InvalidArgumentException $e) {
            return $this->buildResponse([
                'message' => $e->getMessage(),
                'status' => 'error',
                'response_code' => 422,
                'payload' => $e instanceof ApiException ? $e->getResponseObject() : [],
            ]);
        }

        return $this->buildResponse([
            'message' => $msg??'OK',
            'status' => $status ?? 'success',
            'response_code' => $code ?? 200,
            'payload' => $payload??[],
            'deposit' => $subscription??[]
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
        $subscription = $payload = [];
        $saving = Saving::where('payment_ref', $request->reference)->where('status', 'pending')->first();
        if ($saving) {
            $subscription = User::find($saving->user_id)->subscription()->where('id', $saving->subscription_id)->first();
            $_amount = money($tranx->data->amount/100);
            $_left = $subscription->days_left - $request->duration;

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
                    $msg = "You have successfully made a {$saving->days} day savings of {$_amount} for the {$subscription->plan->title} plan, you now have only {$_left} days left to save up.";
                }
                $subscription->save();

            } else {
                $saving->status = 'rejected';
                $trns = $saving->transaction;
                $trns->status = 'rejected';
            }
            $saving->save();
            $trns->save();
            $payload = $tranx;
            $status = 'success';
            $code = 200;
        }

        return [
            'msg' => $msg,
            'code' => $code,
            'status' => $status,
            'payload' => $payload,
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
        $order = $order->where('status', 'pending')->first();
        if ($order)
        {
            $trns = $order->transaction;
            if ('success' === $tranx->data->status) {
                $order->payment = 'complete';
                $trns->status = 'complete';

                $msg = "Your order has been placed successfully, you will be notified whenever it is ready for pickup or delivery.";
                $order->save();

            } else {
                $order->payment = 'rejected';
                $order->status = 'rejected';
                $trns->status = 'rejected';
            }
            $order->save();
            $trns->save();
            $payload = $tranx;
            $status = 'success';
            $code = 200;
        }

        return [
            'msg' => $msg,
            'code' => $code,
            'status' => $status,
            'payload' => $payload,
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
}