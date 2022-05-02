<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Foundation\Auth\EmailVerificationRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(
                config('app.frontend_url').RouteServiceProvider::HOME.'?verified=1'
            );
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(
            config('app.frontend_url').RouteServiceProvider::HOME.'?verified=1'
        );
    }

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Foundation\Auth\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->buildResponse([
                'message' => 'Your email is already verified.',
                'status' => 'success',
                'response_code' => 200,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'code' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->validatorFails($validator);
        }

        // check if it has not expired: the time is 30 minutes and that the code is valid
        if ($request->code !== $request->user()->email_verify_code || $request->user()->last_attempt->diffInMinutes(now()) >= 30) {
            return $this->buildResponse([
                'message' => 'An error occured.',
                'status' => 'error',
                'response_code' => 422,
                'errors' => [
                    'code' => __('The code you provided has expired or does not exist.')
                ]
            ]);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return $this->buildResponse([
            'message' => 'We have successfully verified your email address, welcome to our community.',
            'status' => 'success',
            'response_code' => 200,
        ]);
    }
}