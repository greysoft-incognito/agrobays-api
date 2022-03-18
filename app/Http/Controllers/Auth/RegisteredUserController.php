<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use DeviceDetector\DeviceDetector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            return $this->validatorFails($validator);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        $dev = new DeviceDetector($request->userAgent());
        $device = $dev->getBrandName()?($dev->getBrandName().$dev->getDeviceName()):$request->userAgent();

        $token = $user->createToken($device)->plainTextToken;

        return $this->preflight($token);
    }

    public function preflight($token)
    {
        [$id, $user_token] = explode('|', $token, 2);
        $token_data = DB::table('personal_access_tokens')->where('token', hash('sha256', $user_token))->first();
        $user_id = $token_data->tokenable_id;

        Auth::loginUsingId($user_id);
        $user = Auth::user();
        $user->subscription;

        return $this->buildResponse([
            'message' => 'Registration was successful',
            'status' => 'success',
            'response_code' => 201,
            'token' => $token,
            'user' => $user,
        ]);
    }
}