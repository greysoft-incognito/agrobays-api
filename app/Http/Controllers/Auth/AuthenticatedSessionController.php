<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DeviceDetector\DeviceDetector;

class AuthenticatedSessionController extends Controller
{
    public function index()
    {
        if (Auth::user()) {
            return view('web-user', Auth::user());
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
        $device = $dev->getBrandName()?($dev->getBrandName().$dev->getDeviceName()):$request->userAgent();

        $user = $request->user();
        $user->subscription;

        if (!$request->isXmlHttpRequest()) {
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

        if (!$request->isXmlHttpRequest()) {
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