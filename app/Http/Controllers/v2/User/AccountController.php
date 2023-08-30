<?php

namespace App\Http\Controllers\v2\User;

use App\Actions\Greysoft\Charts;
use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'password_confirmation',
        'password',
        'firstname',
        'lastname',
        'name',
        'country',
        'state',
        'city',
        'address',
        'image',
        'type',
        'bank',
    ];

    public function ping()
    {
        return response()->json([
            'message' => 'PONG!',
        ], 200);
    }

    /**
     * Get the currently logged user.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->role === 'dispatch') {
            $user->load('dispatches');
        }

        return (new UserResource($user))->additional([
            'message' => 'OK',
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    public function update(Request $request, $identifier = 'password')
    {
        /** @var \App\Models\User */
        $user = $request->user();

        $filled = collect($request->all());
        $fields = collect($request->all())->only($this->fillable)->keys();

        $updated = [];
        if (! $user) {
            return $this->responseBuilder([
                'message' => 'The requested user does not exists',
                'status' => 'error',
                'response_code' => HttpStatus::NOT_FOUND,
            ]);
        }

        $valid = $fields->mapWithKeys(function ($field) use ($filled) {
            if (Str::contains($field, ':image')) {
                $field = current(explode(':image', $field));
            }

            $vals = $field == 'image' ? 'mimes:png,jpg' : (is_array($filled[$field])
                ? 'array'
                : (is_int($filled[$field])
                    ? 'numeric'
                    : 'string'
                )
            );
            if ($field === 'password') {
                $vals .= '|min:8|confirmed';
            }
            if (is_array($filled[$field])) {
                return [$field . '.*' => 'required'];
            }

            return [$field => "required|$vals"];
        })->all();

        $this->validate($request, $valid, [], $fields->filter(function ($k) use ($filled) {
            return is_array($filled[$k]);
        })->mapWithKeys(function ($field, $value) use ($filled) {
            return collect(array_keys((array) $filled[$field]))->mapWithKeys(fn ($k) => ["$field.$k" => "$field $k"]);
        })->all());

        $fields = $fields->filter(function ($k) {
            return ! Str::contains($k, '_confirmation');
        });

        if (!$request->hasFile('image')) {
            foreach ($fields as $_field) {
                if (Str::contains($_field, ':image')) {
                    $_field = current(explode(':image', (string) $_field));
                }

                if ($_field !== 'password') {
                    $updated[$_field] = $request->{$_field};
                }
                $user->{$_field} = $request->{$_field};
            }
        }

        $user->save();

        return (new UserResource($user))->additional([
            'message' => "Your profile $identifier has been successfully updated.",
            'status' => 'success',
            'response_code' => HttpStatus::OK,
            'image' => $user->image_url,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Update the user data
     *
     * @param  Request  $request
     * @return void
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $this->validate($request, [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'gender' => ['in:male,female,non-binary,transgender,bisexual,other'],
            'nextofkin' => ['required', 'string', 'max:255'],
            'nextofkin_relationship' => ['required', 'string', 'max:255'],
            'nextofkin_phone' => ['required', 'string', 'max:255'],
            'address.home' => ['required', 'string', 'max:255'],
            'address.shipping' => ['nullable', 'string', 'max:255'],
            'country.name' => ['required', 'string', 'max:255'],
            'state.name' => ['required', 'string', 'max:255'],
            'city.name' => ['required', 'string', 'max:255'],
        ], [], [
            'country.name' => 'Country',
            'state.name' => 'State',
            'city.name' => 'City',
            'address.home' => 'Home Address',
            'address.shipping' => 'Shipping Address',
        ]);

        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->username = $request->username ?? $user->username;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->gender = $request->gender;
        $user->nextofkin = $request->nextofkin;
        $user->nextofkin_relationship = $request->nextofkin_relationship;
        $user->nextofkin_phone = $request->nextofkin_phone;
        $user->address = $request->address;
        $user->country = $request->country;
        $user->state = $request->state;
        $user->city = $request->city;

        $user->save();

        return (new UserResource($user))->additional([
            'message' => 'Your profile has been successfully updated.',
            'status' => 'success',
            'response_code' => 200,
        ])->response()->setStatusCode(200);
    }

    public function dashboard()
    {
        return $this->responseBuilder([
            'message' => 'OK',
            'status' => 'success',
            'response_code' => 200,
            'charts' => [
                'pie' => (new Charts())->getPie('user'),
                'bar' => (new Charts())->getBar('user'),
                'transactions' => (new Charts())->totalTransactions('user', 'all'),
            ],
        ]);
    }
}
