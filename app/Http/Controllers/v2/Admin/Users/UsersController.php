<?php

namespace App\Http\Controllers\v2\Admin\Users;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('usable', 'users.' . $request->get('role', 'user'));
        $query = User::query();

        $query->when($request->role !== 'all', function ($q) use ($request) {
            $q->where('role', $request->role);
        })->when($request->boolean('managed'), function ($q) use ($request) {
            $q->whereNotNull('pen_code');
        })->when($request->search, function (Builder $q) use ($request) {
            // Search and filter columns
            $q->where(function (Builder $query) use ($request) {
                $query->where('username', $request->search)
                    ->orWhere('email', 'like', "%$request->search%")
                    ->orWhere('pen_code', str($request->search)->remove('-'))
                    ->orWhere('firstname', 'like', "%$request->search%")
                    ->orWhere('lastname', 'like', "%$request->search%")
                    ->orWhereRaw("CONCAT_WS(' ', firstname, lastname) LIKE '%$request->search%'")
                    ->orWhere('address->home', 'like', "%$request->search%")
                    ->orWhere('address->shipping', 'like', "%$request->search%")
                    ->orWhere('country->name', 'like', "%$request->search%")
                    ->orWhere('city->name', $request->search)
                    ->orWhere('state->name', $request->search)
                    ->orWhere('state', $request->search);
            });
        })->when($request->order && is_array($request->order), function ($q) use ($request) {
            // Reorder Columns
            foreach ($request->order as $key => $dir) {
                if ($dir === 'desc') {
                    $q->orderByDesc($key ?? 'id');
                } else {
                    $q->orderBy($key ?? 'id');
                }
            }
        });

        $users = $query->paginate($request->get('limit', 15));

        return (new UserCollection($users))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('usable', 'users');

        $this->validate($request, [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required_without:phone', 'email', 'min:1', 'max:255', 'unique:users'],
            'phone' => ['required_without:email', 'min:1', 'max:15', 'unique:users'],
            'gender' => ['in:male,female,non-binary,transgender,bisexual,other'],
            'nextofkin' => ['nullable', 'string', 'max:255'],
            'nextofkin_relationship' => ['nullable', 'string', 'max:255'],
            'nextofkin_phone' => ['nullable', 'string', 'max:255'],
            'address.*' => ['nullable', 'string', 'max:255'],
            'country.*' => ['nullable', 'alpha_num', 'max:255'],
            'state.*' => ['nullable', 'alpha_num', 'max:255'],
            'city.*' => ['nullable', 'alpha_num', 'max:255'],
        ], [], [
            'address.home' => 'Home Address',
            'address.shipping' => 'Shipping Address',
        ]);

        $user = new User();

        $user->email = $request->email ?? '1@1.com';
        $user->phone = $request->phone;
        $user->lastname = $request->lastname;
        $user->firstname = $request->firstname;
        $user->gender = $request->gender ?? 'male';
        $user->nextofkin = $request->nextofkin;
        $user->nextofkin_phone = $request->nextofkin_phone;
        $user->nextofkin_relationship = $request->nextofkin_relationship;
        $user->address = $request->address;
        $user->country = $request->country;
        $user->state = $request->state;
        $user->city = $request->city;
        $user->role = $request->role ?? 'user';
        $user->password = Hash::make(Str::random(8));
        $user->pen_code = $this->genPenCode();

        // Assign a referral code to the user
        if ($request->referral_code) {
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
        }

        $user->save();

        if (!$request->email) {
            $user->email = str($user->username)->slug()->append('@agrobays.com');
            $user->save();
        }

        return (new UserResource($user))->additional([
            'message' => __(':0 has been created successfully', [$user->fullname]),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return (new UserResource($user))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('usable', 'users.' . $user->role);

        $this->validate($request, [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id ?? '')],
            'phone' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id ?? '')],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($user->id ?? '')],
            'gender' => ['in:male,female,non-binary,transgender,bisexual,other'],
            'nextofkin' => ['nullable', 'string', 'max:255'],
            'nextofkin_relationship' => ['nullable', 'string', 'max:255'],
            'nextofkin_phone' => ['nullable', 'string', 'max:255'],
            'address.*' => ['nullable', 'string', 'max:255'],
            'country.*' => ['nullable', 'alpha_num', 'max:255'],
            'state.*' => ['nullable', 'alpha_num', 'max:255'],
            'city.*' => ['nullable', 'alpha_num', 'max:255'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ], [], [
            'address.home' => 'Home Address',
            'address.shipping' => 'Shipping Address',
        ]);

        if ($request->form === 'bank') {
            $this->validate($request, [
                'bank.*' => ['required', 'string', 'max:255'],
                'bank.nuban' => ['required', 'numeric', 'digits:10'],
            ], [], [
                'bank.bank' => 'Bank Name',
                'bank.nuban' => 'Bank Account Number',
            ]);
        }

        $msg = ' Has been updated!';

        $user->bank = $request->bank ?? $user->bank;
        $user->email = $request->email ?? $user->email;
        $user->phone = $request->phone ?? $user->phone;
        $user->username = $request->username ?? $user->username;
        $user->lastname = $request->lastname;
        $user->firstname = $request->firstname;
        $user->gender = $request->gender ?? 'male';
        $user->nextofkin = $request->nextofkin;
        $user->nextofkin_phone = $request->nextofkin_phone;
        $user->nextofkin_relationship = $request->nextofkin_relationship;
        $user->address = $request->address;
        $user->country = $request->country;
        $user->state = $request->state;
        $user->city = $request->city;
        if ($request->role) {
            $user->role = $request->role;
        }
        if ($request->password) {
            $msg = "'s password has been updated!";
            $user->password = Hash::make($request->password);
        }
        if ($request->pen_mode && !$user->pen_code) {
            $user->pen_code = $this->genPenCode();
        }

        $user->save();

        return (new UserResource($user))->additional([
            'message' => str($user->fullname)->append($msg),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id = null)
    {
        // Delete multiple users
        if ($request->users) {
            $count = User::whereIn('id', $request->users)->with(['transactions', 'subscription'])->get()->map(function ($user) {
                if ($user && Gate::allows('usable', 'users.' . $user->role)) {
                    return $user->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->responseBuilder([
                'message' => "{$count} users have been deleted.",
                'status' => 'success',
                'response_code' => 200,
            ]);
        } else {
            $user = User::whereId($id)->orWhere('username', $id)->firstOrFail();
            $user && $this->authorize('usable', 'users.' . $user->role);
        }

        // Delete single user
        if ($user) {
            $user->delete();

            return $this->responseBuilder([
                'message' => "{$user->fullname} has been deleted.",
                'status' => 'success',
                'response_code' => 200,
            ]);
        }
    }

    protected function genPenCode()
    {
        $pcode = str(Str::random(12))->upper()->toString();
        if (User::wherePenCode($pcode)->exists()) {
            return $this->genPenCode();
        }

        return $pcode;
    }
}