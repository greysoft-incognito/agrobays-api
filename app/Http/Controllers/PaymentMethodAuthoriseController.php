<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Yabacon\Paystack;
use Yabacon\Paystack\Exception\ApiException;

class PaymentMethodAuthoriseController extends Controller
{
    /**
     * Authorize a payment method for a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $method
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $method = 'paystack')
    {
        $due = 100;
        $msg = 'OK';
        $user = Auth::user();
        $payload = new \stdClass();
        $userData = $user->data ?? collect(['payment_method' => []]);
        $deauth = $request->boolean('deauthorize', false);

        try {
            $reference = config('settings.trx_prefix', 'AGB-') . Str::random(15);
            if ($deauth) {
                // Remove the payment method
                $userData['payment_method'] = [];
                $msg = __('Your :0:1 has been deauthorized from processing automatic payments.', [
                    $user->data['payment_method']['channel'] ?? 'wallet',
                    isset($user->data['payment_method']['last4']) ? ' ending in ' . $user->data['payment_method']['last4'] : '',
                ]);
            } elseif ($request->get('method', $method) === 'wallet') {
                // Authorize the wallet
                $userData['payment_method'] = [
                    'type' => 'wallet',
                    'channel' => 'wallet',
                    'auth_date' => now()->toDateTimeString(),
                ];
                $msg = __('Your wallet has been authorized for automatic payments.');
            } else {
                // Authorize the payment method using Paystack
                $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));

                // Dont initialize paystack for inline transaction
                if ($request->inline) {
                    $payload = [
                        'data' => ['reference' => $reference],
                    ];
                } else {
                    $payload = $paystack->transaction->initialize([
                        'amount' => $due * 100,       // in kobo
                        'email' => $user->email,         // unique to customers
                        'reference' => $reference,         // unique to transactions
                        'callback_url' => $request->get('callback_url', config('settings.payment_verify_url', route('payment.paystack.verify'))),
                    ]);
                }
            }

            // Save the user data
            $user->data = $userData;
            $user->save();
        } catch (ApiException | \InvalidArgumentException | \ErrorException $e) {
            return $this->buildResponse([
                'message' => $e->getMessage(),
                'status' => 'error',
                'response_code' => 422,
                'due' => $due,
                'payload' => $e instanceof ApiException ? $e->getResponseObject() : [],
            ]);
        }

        // Return the response
        return (new UserResource($user))->additional([
            'message' => $msg,
            'status' => 'success',
            'response_code' => 200,
            'payload' => $payload,
        ])->response()->setStatusCode(200);
    }

    /**
     * Verify the payment method for a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $action
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $method = 'paystack')
    {
        $msg = 'Invalid Authorization code.';
        $status = 'error';
        $code = 403;
        if (! $request->reference) {
            $msg = 'No reference supplied';
        }
        $user = Auth::user();
        $payload = new \stdClass();
        $userData = $user->data ?? collect(['payment_method' => []]);

        try {
            // Verify the payment method using Paystack
            if ($request->get('method', $method) === 'paystack') {
                $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));
                $tranx = $paystack->transaction->verify([
                    'reference' => $request->reference,   // unique to transactions
                ]);
            }

            // Pass the current signature to a variable for comparison
            $signature = $userData['payment_method']['signature'] ?? null;

            // Check if the card has already been authorized
            if ('success' === $tranx->data->status && $signature === $tranx->data->authorization->signature) {
                $msg = __('Your card ending with :0 has already been authorized for automatic payments.', [
                    $tranx->data->authorization->last4,
                ]);
                $code = 422;
                $status = 'error';
            } elseif ('success' === $tranx->data->status) {
                // Check if the transaction was successful
                $userData['payment_method'] = $tranx->data->authorization;
                $userData['payment_method']->type = 'paystack';
                $userData['payment_method']->email = $tranx->data->customer->email;
                $userData['payment_method']->auth_date = now()->toDateTimeString();
                $user->wallet()->firstOrNew()->topup(
                    'Refunds',
                    $tranx->data->amount / 100,
                    'Card Authorization Refund'
                );

                $msg = __('Your card ending with :0 has been authorized for automatic payments.', [
                    $tranx->data->authorization->last4,
                ]);
                $code = 200;
                $status = 'success';
            }

            if ($code === 200) {
                // Save the user data
                $user->data = $userData;
                $user->save();
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

        // Return the response
        return (new UserResource($user))->additional([
            'message' => $msg,
            'status' => $status,
            'response_code' => $code,
            'payload' => $payload,
        ])->response()->setStatusCode($code);
    }
}
