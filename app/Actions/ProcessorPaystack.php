<?php

namespace App\Services\Payment;

use App\EnumsAndConsts\HttpStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yabacon\Paystack;
use Yabacon\Paystack\Exception\ApiException;

class ProcessorPaystack
{
    protected $request;

    protected $user;

    /**
     * ProcessorPaystack constructor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     */
    public function __construct(Request $request, User $user = null)
    {
        $this->request = $request;
        $this->user = $user;
    }

    /**
     * Initialize a paystack transaction.
     *
     * @param  int  $amount
     * @param  callable  $callback
     * @return \Illuminate\Support\Collection
     */
    public function initialize(int $amount = 0, $callback = null)
    {
        $tranx = null;
        $user = $this->user;
        $code = HttpStatus::BAD_REQUEST;
        $due = $amount;
        $msg = 'Transaction Failed';

        $reference = config('settings.trx_prefix', 'AGB-') . Str::random(15);
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
                        'redirect_url',
                        config('settings.frontend_link')
                            ? config('settings.frontend_link') . '/payment/verify'
                            : config('settings.payment_verify_url', route('payment.paystack.verify'))
                    ),
                ]);
            }

            // Call the callback function
            if ($callback) {
                $callback($reference, $tranx, $real_due);
            }

            $code = HttpStatus::OK;
            $msg = 'Transaction initialized';
        } catch (ApiException | \InvalidArgumentException | \ErrorException $e) {
            $msg = $e->getMessage();
            $code = $e instanceof ApiException ? HttpStatus::BAD_REQUEST : HttpStatus::SERVER_ERROR;
        }

        $response->amount = $due;
        $response->message = $msg;
        $response->payload = $tranx;
        $response->reference = $reference;
        $response->status_code = $code;

        // Return the response as a collection
        return collect($response);
    }

    /**
     * Verify a transaction payment
     *
     * @param  Request  $request
     * @param  callable  $callback
     * @return \Illuminate\Support\Collection
     */
    public function verify($callback = null)
    {
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

            // Call the callback function
            if ($callback) {
                $callback($this->request->reference, $tranx);
            }

            $code = HttpStatus::OK;
            $msg = 'Transaction verified';
        } catch (ApiException | \InvalidArgumentException | \ErrorException $e) {
            $tranx = $e instanceof ApiException ? $e->getResponseObject() : [];
            $code = HttpStatus::UNPROCESSABLE_ENTITY;
            $msg = $e->getMessage();
        }

        $response->message = $msg;
        $response->payload = $tranx;
        $response->status_code = $code;

        // Return the response as a collection
        return collect($response);
    }
}
