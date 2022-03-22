<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Saving;
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
        ]))->fails()) {
            return $this->validatorFails($validator);
        }

        $subscription = Auth::user()->subscription()->find($request->subscription_id);

        if (($validator = Validator::make($request->all(), [
            'days' => ['required', 'numeric', 'min:1', 'max:'.$subscription->plan->duration],
        ], [
            'days.min' => 'You have to save for at least 1 day.',
            'days.max' => "You cannot save for more than {$subscription->plan->duration} days."
        ]))->fails()) {
            return $this->validatorFails($validator);
        }

        $code = 403;

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
            try {
                $paystack = new Paystack(env("PAYSTACK_SECRET_KEY"));
                $reference = Str::random(12);
                $due = $subscription->plan->amount / $subscription->plan->duration;

                $tranx = $paystack->transaction->initialize([
                  'amount' => $due,       // in kobo
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

            if ($type === 'savings') {
                $processSaving = $this->processSaving($request, $tranx);
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
     * Undocumented function
     *
     * @param  \Illuminate\Http\Request  $request
     * @param [type] $tranx
     * @return array
     */
    public function processSaving(Request $request, $tranx): array
    {
        $msg = "OK";
        $saving = Saving::where('payment_ref', $request->reference)->where('status', 'pending')->first();
        if ($saving) {
            $subscription = User::find($saving->user_id)->subscription;
            $_amount = money($tranx->data->amount/100);
            $_left = $subscription->days_left;
            if ('success' === $tranx->data->status) {
                $saving->status = 'complete';
                $trns = $saving->transaction;
                $trns->status = 'complete';
                $msg = "You have successfully made a {$saving->days} day savings of {$_amount} for the {$subscription->plan->title} plan, you now have only {$_left} days left to save up.";
                $subscription->status = $subscription->days_left >= 1 ? 'active' : 'complete';
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