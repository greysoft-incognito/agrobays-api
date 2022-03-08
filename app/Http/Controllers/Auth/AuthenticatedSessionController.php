<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DeviceDetector\DeviceDetector;

class AuthenticatedSessionController extends Controller
{
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

        return $this-> buildResponse([
            'message' => 'Login was successful',
            'status' => 'success',
            'response_code' => 200,
            'token' => $request->user()->createToken($device)->plainTextToken,
            'user' => $request->user(),
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

        return $this-> buildResponse([
            'message' => 'You have been successfully logged out',
            'status' => 'success',
            'response_code' => 200,
        ]);
    }
}