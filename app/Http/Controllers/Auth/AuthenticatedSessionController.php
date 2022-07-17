<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use DeviceDetector\DeviceDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function index()
    {
        if ($user = Auth::user()) {
            $errors = $code = $messages = $action = null;

            return view('web-user', compact('user', 'errors', 'code', 'action'));
        }

        return view('login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();
        $dev = new DeviceDetector($request->userAgent());
        $device = $dev->getBrandName() ? ($dev->getBrandName().$dev->getDeviceName()) : $request->userAgent();

        $user = $request->user();
        $user->subscription = $user->subscriptions()->latest();

        if (! $request->ajax()) {
            return response()->redirectToRoute('web.user');
        }

        return $this->buildResponse([
            'message' => 'Login was successful',
            'status' => 'success',
            'response_code' => 200,
            'token' => $user->createToken($device)->plainTextToken,
            'user' => $user,
        ]);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $request->user()->tokens()->delete();

        if (! $request->isXmlHttpRequest()) {
            session()->flush();

            return response()->redirectToRoute('web.login');
        }

        return $this->buildResponse([
            'message' => 'You have been successfully logged out',
            'status' => 'success',
            'response_code' => 200,
        ]);
    }
}