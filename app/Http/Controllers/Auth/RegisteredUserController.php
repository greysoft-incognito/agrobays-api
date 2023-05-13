<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use DeviceDetector\DeviceDetector;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

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
        $cIso2 = 'NG';
        if (($ipInpfo = \Illuminate\Support\Facades\Http::get('ipinfo.io/'.request()->ip().'?token='.config('settings.ipinfo_access_token')))->status() === 200) {
            $cIso2 = $ipInpfo->json('country') ?? $cIso2;
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            // 'lastname' => ['required', 'string', 'max:255'],
            'email' => [config('settings.verify_email', true) ? 'required' : 'nullable', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => config('settings.verify_phone', false) ? "required|phone:$cIso2" : 'nullable|string|max:255|unique:users',
            // 'username' => ['required', 'string', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [], [
            'phone' => 'Phone Number',
        ]);

        if ($validator->fails()) {
            return $this->validatorFails($validator);
        }

        $name = Str::of($request->name)->explode(' ');

        if (config('settings.verify_email', true)) {
            $eser = Str::of($request->email)->explode('@');
            $username = $eser->first(fn ($k) => (User::where('username', $k)->doesntExist()), $eser->first().rand());
        } else {
            $username = Str::of($name)->append();
        }

        $user = User::create([
            'firstname' => ($firstname = $name->first(fn ($k) => $k !== null, $request->name)),
            'lastname' => $name->last(fn ($k) => $k !== $firstname, ''),
            'email' => $request->email,
            'phone' => $request->phone,
            'username' => $username,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        $dev = new DeviceDetector($request->userAgent());
        $device = $dev->getBrandName() ? ($dev->getBrandName().$dev->getDeviceName()) : $request->userAgent();

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

        return (new UserResource($user))->additional([
            'message' => 'Registration was successfull',
            'status' => 'success',
            'status_code' => 201,
            'token' => $token,
        ])->response()->setStatusCode(201);
    }
}
