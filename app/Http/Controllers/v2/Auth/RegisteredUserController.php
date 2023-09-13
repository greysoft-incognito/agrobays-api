<?php

namespace App\Http\Controllers\v2\Auth;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use DeviceDetector\DeviceDetector;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        $this->validate($request, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                config('settings.verify_email', true) ? 'required' : 'nullable',
                'string',
                'email',
                'max:255',
                'unique:users',
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'referral_code' => 'nullable|exists:users,referral_code',
        ]);

        // Split the name into firstname and lastname
        $name = Str::of($request->name)->explode(' ');
        $firstname = $name->first(fn ($k) => $k !== null, $request->name);
        $lastname = $name->last(fn ($k) => $k !== $firstname, '');

        // Create the user
        $user = new User();
        $user->firstname = $firstname;
        $user->lastname = $lastname;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);

        // Assign a referral code to the user
        /** @var \App\Models\User $referrer */
        $referrer = User::where('referral_code', $request->referral_code)->first();
        // Add the referrer id to the user
        if ($referrer && config('settings.referral_system', false)) {
            $user->referrer_id = $referrer->id;

            if (config('settings.referral_mode', 2) == 0) {
                $referrer->wallet()->create([
                    'amount' => config('settings.referral_bonus', 1),
                    'type' => 'credit',
                    'source' => 'Referral Bonus',
                    'detail' => __('Referral bonus for :0\'s registration.', [$user->fullname]),
                ]);
            }
        }
        $user->save();

        event(new Registered($user));

        $dev = new DeviceDetector($request->userAgent());
        $device = $dev->getBrandName() ? ($dev->getBrandName().$dev->getDeviceName()) : $request->userAgent();

        $token = $user->createToken($device)->plainTextToken;

        return $this->preflight($token);
    }

    protected function preflight($token)
    {
        [$id, $user_token] = explode('|', $token, 2);
        $token_data = DB::table('personal_access_tokens')->where('token', hash('sha256', $user_token))->first();
        $user_id = $token_data->tokenable_id;

        Auth::loginUsingId($user_id);
        $user = Auth::user();

        return (new UserResource($user))->additional([
            'message' => 'Registration was successfull',
            'status' => 'success',
            'response_code' => HttpStatus::CREATED,
            'token' => $token,
        ])->response()->setStatusCode(HttpStatus::CREATED);
    }
}
