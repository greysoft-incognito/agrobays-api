<?php

namespace App\Http\Controllers;

use App\Models\Saving;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Support\Facades\Auth $auth
     * @return \Illuminate\Http\Response
     */
    public function index(Auth $auth)
    {
        $user = $auth::user();
        $user->subscription;
        return $this->buildResponse([
            'message' => 'OK',
            'status' => 'success',
            'response_code' => 200,
            'user' => $user,
        ]);
    }

    /**
     * Display a listing of the user's transactions or return
     * a particular transaction if an id is provided.
     *
     * @param \Illuminate\Support\Facades\Auth $auth
     * @return \Illuminate\Http\Response
     */
    public function savings(Auth $auth, $id = null)
    {
        if (!$id)
        {
            $model = Saving::where('user_id', Auth::id());
            return app('datatables')->eloquent($model)
                ->editColumn('created_at', function(Saving $item) {
                    return $item->created_at->format('Y-m-d H:i');
                })
                ->editColumn('amount', function(Saving $item) {
                    return money($item->amount);
                })
                ->editColumn('type', function(Saving $item) {
                    return $item->subscription->plan->title;
                })
                ->removeColumn('updated_at')->toJson();
        }

        $savings = $auth::user()->savings();

        if ($id && !($saving = $savings->find($id))) {
            return $this->buildResponse([
                'message' => 'The requested saving no longer exists.',
                'status' => 'error',
                'response_code' => 404,
            ]);
        }

        return $this->buildResponse([
            'message' => 'OK',
            'status' => 'success',
            'response_code' => 200,
            $id ? 'saving' : 'savings' => $id ? $saving : $savings->paginate(15),
        ]);
    }

    /**
     * Update the user data
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            return $this->buildResponse([
                'message' => 'The requested user no longer exists',
                'status' => 'error',
                'response_code' => 404,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'gender' => ['in:male,female,non-binary,transgender,bisexual,other'],
            'nextofkin' => ['required', 'string', 'max:255'],
            'nextofkin_relationship' => ['required', 'string', 'max:255'],
            'nextofkin_phone' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->buildResponse([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'response_code' => 422,
                'errors' => $validator->errors(),
            ]);
        }

        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->username = $request->username??$user->username;
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

        return $this->buildResponse([
            'message' => 'Your profile has been successfully updated.',
            'status' =>  'success',
            'response_code' => 200,
            'user' => $user,
        ]);
    }
}
