<?php

namespace App\Http\Controllers\v2\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\Payment\PaystackDeauth;
use App\Services\Payment\PaystackProcessor;
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
        $msg = HttpStatus::message(HttpStatus::OK);
        $user = Auth::user();
        $payload = new \stdClass();
        $userData = $user->data ?? collect(['payment_method' => []]);
        $method = $request->post('method', $method);

        if (empty($user->data['payment_method'])) {
            try {
                if ($method === 'wallet') {
                    // Authorize the wallet
                    $userData['payment_method'] = [
                        'type' => 'wallet',
                        'channel' => 'wallet',
                        'auth_date' => now()->toDateTimeString(),
                    ];
                    $msg = __('Your wallet has been authorized for automatic payments.');
                } else {
                    $paystack = new PaystackProcessor($request, $user);
                    $payload = $paystack->initialize($due)->payload ?? $payload;
                }

                // Save the user data
                $user->data = $userData;
                $user->save();

                $code = HttpStatus::OK;
                $status = 'success';
            } catch (ApiException | \InvalidArgumentException | \ErrorException $e) {
                return $this->responseBuilder([
                    'message' => $e->getMessage(),
                    'status' => 'error',
                    'response_code' => HttpStatus::UNPROCESSABLE_ENTITY,
                    'due' => $due,
                    'payload' => $e instanceof ApiException ? $e->getResponseObject() : [],
                ]);
            }
        } else {
            $msg = __('To authorize a new payment method, please deauthorize your current payment method.');
            $code = HttpStatus::UNPROCESSABLE_ENTITY;
            $status = 'error';
        }

        // Return the response
        return (new UserResource($user))->additional([
            'message' => $msg,
            'status' => $status,
            'response_code' => $code,
            'payload' => $payload,
        ])->response()->setStatusCode(200);
    }

    /**
     * Verify the payment method for a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $method
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $method = 'paystack')
    {
        $msg = 'Invalid Authorization code.';
        $status = 'error';
        $code = HttpStatus::FORBIDDEN;
        if (! $request->reference) {
            $msg = 'No reference supplied';
        }
        $user = Auth::user();
        $payload = new \stdClass();
        $userData = $user->data ?? collect(['payment_method' => []]);

        try {
            // Verify the payment method using Paystack
            // if ($request->get('method', $method) === 'paystack') {
            $paystack = new PaystackProcessor($request, $user);
            $tranx = $paystack->verify()->payload ?? $payload;
            // }

            // Pass the current signature to a variable for comparison
            $signature = $userData['payment_method']['signature'] ?? null;

            if (isset($tranx->data)) {
            // Check if the card has already been authorized
                if ('success' === $tranx->data->status && $signature === $tranx->data->authorization->signature) {
                    $msg = __('Your card ending with :0 has already been authorized for automatic payments.', [
                    $tranx->data->authorization->last4,
                    ]);
                    $code = HttpStatus::UNPROCESSABLE_ENTITY;
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
                    $code = HttpStatus::OK;
                    $status = 'success';
                }

                if ($code === HttpStatus::OK) {
                    // Save the user data
                    $user->data = $userData;
                    $user->save();
                }
            }
        } catch (ApiException | \InvalidArgumentException | \ErrorException $e) {
            $payload = $e instanceof ApiException ? $e->getResponseObject() : [];
            return $this->responseBuilder([
                'message' => $e->getMessage(),
                'status' => 'error',
                'response_code' => HttpStatus::UNPROCESSABLE_ENTITY,
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

    /**
     * Deauthorize a payment method for a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $msg = HttpStatus::message(HttpStatus::OK);
        $code = 200;
        $user = Auth::user();
        $payload = new \stdClass();
        $data = $user->data ?? collect(['payment_method' => []]);

        if (!empty($user->data['payment_method'])) {
            // Deauthorize Paystack
            if (
                isset($data['payment_method']['type'], $data['payment_method']['authorization_code']) &&
                $data['payment_method']['type'] === 'paystack'
            ) {
                $paystack = new PaystackProcessor($request, $user);
                // Remove Paystack Authorization
                $tranx = $paystack->deauthorize($data['payment_method']['authorization_code'])->payload ?? $payload;
            }

            // Remove the payment method
            $data['payment_method'] = [];

            // Define Response Message
            $msg = __('Your :0:1 has been deauthorized from processing automatic payments.', [
                $user->data['payment_method']['channel'] ?? 'wallet',
                isset($user->data['payment_method']['last4'])
                    ? ' ending in ' . $user->data['payment_method']['last4']
                    : '',
            ]);
        } else {
            $msg = 'You have not authorized any payment method to handle automatic payments.';
            $code = 422;
        }

        // Save the user data
        $user->data = $data;
        $user->save();

        // Return the response
        return (new UserResource($user))->additional([
            'message' => $msg,
            'status' => 'success',
            'response_code' => $code,
            'payload' => $payload,
        ])->response()->setStatusCode($code);
    }
}