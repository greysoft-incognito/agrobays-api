<?php

namespace App\Http\Controllers;

use App\Models\Saving;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Actions\Greysoft\Charts;

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
        'address',
        'image',
        'type',
    ];

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
    public function savings(Auth $auth, $id = null, $planned = false)
    {
        if (!$id || $planned === 'planned')
        {
            $model = Saving::where('user_id', Auth::id());
            if ($planned !== false) {
                $model->where('subscription_id', $id);
            }
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

    public function updateField(Request $request, $identifier = 'password')
    {
        $filled = collect($request->all());
        $fields = collect($request->all())->keys();

        foreach ($fields as $key => $_field) {
            $allow = in_array($_field, $this->fillable);
            if (!$allow) break;
        }

        $updated = [];
        $user = User::find(Auth::id());
        if (!$user) {
            return $this->buildResponse([
                'message' => 'The requested user does not exists',
                'status' => 'error',
                'response_code' => 404,
            ]);
        }

        $valid = $fields->mapWithKeys(function($field) use ($filled) {
            if (Str::contains($field, ':image')) {
                $field = current(explode(':image', $field));
            }
            $vals = $field == 'image' ? 'mimes:png,jpg' : (is_array($filled[$field]) ? 'array' : 'string');
            if ($field === 'password') {
                $vals .= '|min:8|confirmed';
            }
            if (is_array($filled[$field])) {
                return [$field.'.*' => "required|string"];
            }
            return [$field => "required|$vals"];
        })->all();

        $validator = Validator::make($request->all(), $valid, [], $fields->filter(function($k) use ($filled) {
            return is_array($filled[$k]);
        })->mapWithKeys(function($field, $value) use ($filled) {
            return collect(array_keys($filled[$field]))->mapWithKeys(fn($k)=>["$field.$k" => "$k $field"]);
        })->all());

        if ($validator->fails() || !$allow) {
            return $this->buildResponse([
                'message' => !$allow ? 'An error occured!' : $validator->errors()->first(),
                'status' => 'error',
                'response_code' => 422,
                'errors' => !$allow ? ["You are not allowed to update $_field"] : $validator->errors(),
            ]);
        }

        $fields = $fields->filter(function($k) {
            return !Str::contains($k, '_confirmation');
        });

        if ($request->hasFile('image'))
        {
            $user->image && Storage::delete($user->image??'');
            $user->image = $request->file('image')->storeAs(
                'public/uploads/images', rand() . '_' . rand() . '.' . $request->file('image')->extension()
            );
        }
        else
        {
            foreach ($fields as $_field) {
                if (Str::contains($_field, ':image')) {
                    $_field = current(explode(':image', (string)$_field));
                }
                $allow = in_array($_field, $this->fillable);
                if (!$allow) break;

                if ($_field !== 'password') {
                    $updated[$_field] = $request->{$_field};
                }
                $user->{$_field} = $request->{$_field};
            }
        }

        $user->save();

        return $this->buildResponse(collect($updated)->merge([
            'message' => "Your profile $identifier has been successfully updated.",
            'status' =>  'success',
            'response_code' => 200,
            'user' => $user,
        ])->all(), $identifier == 'image' ? ['image' => $user->image_url] : null);
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
            'address.*' => ['required', 'string', 'max:255'],
            'country.*' => ['required', 'string', 'max:255'],
            'state.*' => ['required', 'string', 'max:255'],
            'city.*' => ['required', 'string', 'max:255'],
        ], [], [
            'address.home' => "Home Address",
            'address.shipping' => "Shipping Address"
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

        if ($request->hasFile('image'))
        {
            $user->image && Storage::delete($user->image??'');
            $user->image = $request->file('image')->storeAs(
                'public/uploads/images', rand() . '_' . rand() . '.' . $request->file('image')->extension()
            );
        }

        $user->save();

        return $this->buildResponse([
            'message' => 'Your profile has been successfully updated.',
            'status' =>  'success',
            'response_code' => 200,
            'user' => $user,
        ]);
    }

    public function charts($type = 'pie')
    {
        return $this->buildResponse([
            'message' => 'OK',
            'status' =>  'success',
            'response_code' => 200,
            'charts' => [
                "pie" => (new Charts)->getPie('user'),
                "bar" => (new Charts)->getBar('user'),
                "transactions" => (new Charts)->totalTransactions('user', 'month')
            ],
        ]);
    }
}