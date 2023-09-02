<?php

namespace App\Services\Payment;

use App\EnumsAndConsts\HttpStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Yabacon\Paystack;
use Yabacon\Paystack\Exception\ApiException;

class PaystackProcessor
{
    protected $request;

    protected $user;

    /**
     * PaystackProcessor constructor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\v1\User  $user
     */
    public function __construct(Request $request, User $user = null)
    {
        $this->request = $request;
        $this->user = $user;
    }

    /**
     * Initialize a paystack transaction.
     *
     * @param  int  $amount  The amount to initialize the transaction with
     * @param  callable  $callback  The callback function to call when the transaction is initialized
     * @param  callable  $error_callback  The callback function to call when an error occurs
     * @param  bool  $respond  Whether to return the callback response or not
     * @return \stdClass
     */
    public function initialize(
        int $amount = 0,
        callable $callback = null,
        callable $error_callback = null,
        bool $respond = false
    ) {
        $tranx = null;
        $user = $this->user;
        $code = HttpStatus::BAD_REQUEST;
        $due = $amount;
        $msg = 'Transaction Failed';

        $reference = config('settings.trx_prefix', 'TRX-').$this->generateString(20, 3);
        $real_due = round($due * 100, 2);

        $response = new \stdClass();

        // Initialize paystack transaction
        try {
            $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));

            // Dont initialize paystack for inline transaction
            if ($this->request->inline) {
                $tranx = [
                    'data' => ['reference' => $reference],
                ];
            } else {
                $tranx = $paystack->transaction->initialize([
                    'amount' => $real_due,       // in kobo
                    'email' => $user->email,     // unique to customers
                    'reference' => $reference,   // unique to transactions
                    'callback_url' => $this->request->get(
                        'redirect',
                        config('settings.frontend_link')
                            ? config('settings.frontend_link').'/payment/verify'
                            : config('settings.payment_verify_url', route('payment.paystack.verify'))
                    ),
                ]);
            }

            $code = HttpStatus::OK;
            $msg = 'Transaction initialized';

            // Call the callback function
            if ($callback) {
                $response = $callback($reference, $tranx, $real_due, $msg, $code);
                if ($respond) {
                    return $response;
                }
            }
        } catch (ApiException | \InvalidArgumentException | \ErrorException $e) {
            $msg = $e->getMessage();
            $code = $e instanceof ApiException ? HttpStatus::BAD_REQUEST : HttpStatus::SERVER_ERROR;

            // Call the error callback function
            if ($error_callback) {
                $response = $error_callback($msg, $code);
                if ($respond) {
                    return $response;
                }
            }
        }

        $response->amount = $due;
        $response->message = $msg;
        $response->payload = $tranx;
        $response->reference = $reference;
        $response->status_code = $code;

        // Return the response as a collection
        return $response;
    }

    /**
     * Verify a transaction payment
     *
     * @param  Request  $request
     * @param  callable  $callback
     * @param  callable  $error_callback  The callback function to call when an error occurs
     * @param  bool  $respond  Whether to return the callback response or not
     * @return \Illuminate\Support\Collection
     */
    public function verify(
        callable $callback = null,
        callable $error_callback = null,
        bool $respond = false
    ) {
        $code = HttpStatus::BAD_REQUEST;
        $msg = 'Transaction Failed';

        if (! $this->request->reference) {
            $msg = 'No transaction reference supplied';
        }

        $response = new \stdClass();

        try {
            $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));
            $tranx = $paystack->transaction->verify([
                'reference' => $this->request->reference,   // unique to transactions
            ]);

            $code = HttpStatus::OK;
            $msg = 'Transaction verified';

            // Call the callback function
            if ($callback) {
                $response = $callback($this->request->reference, $tranx, $msg, $code);
                if ($respond) {
                    return $response;
                }
            }
        } catch (ApiException | \InvalidArgumentException | \ErrorException $e) {
            $tranx = $e instanceof ApiException ? $e->getResponseObject() : new \stdClass();
            $code = HttpStatus::UNPROCESSABLE_ENTITY;
            $msg = $e->getMessage();

            // Call the error callback function
            if ($error_callback) {
                $response = $error_callback($msg, $code, $tranx);
                if ($respond) {
                    return $response;
                }
            }
        }

        $response->message = $msg;
        $response->payload = $tranx;
        $response->status_code = $code;

        // Return the response as a collection
        return collect($response);
    }

    protected function generateString($strength = 16, $group = 0, $input = null)
    {
        $groups = [
            '0123456789abcdefghi'.md5(time()).'jklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'.time().rand(),
            '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'.time().rand(),
            '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '01234567890123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        ];
        $input = $input ?? $groups[$group] ?? $groups[2];

        $input_length = strlen($input);
        $random_string = '';
        for ($i = 0; $i < $strength; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }

        return $random_string;
    }
}
