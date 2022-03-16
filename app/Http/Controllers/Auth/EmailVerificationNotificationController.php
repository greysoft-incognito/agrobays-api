<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->buildResponse([
                'message' => 'Your email is already verified.',
                'status' => 'success',
                'response_code' => 200,
            ])->redirect()->route('account.index');
            // return redirect()->intended(RouteServiceProvider::HOME);
        }

        $request->user()->sendEmailVerificationNotification();

        return $this->buildResponse([
            'message' => 'Verification link sent',
            'status' => 'success',
            'response_code' => 200,
        ]);
    }
}
