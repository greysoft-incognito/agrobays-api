<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    /**
     * Display a listing of all user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $type
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $limit = '15', $role = 'user')
    {
        \Gate::authorize('usable', 'users.'.$role);
        $query = User::query();

        if ($role !== 'all') {
            $query->where('role', $role);
        }

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('username', $request->search)
                    ->orWhereRaw("CONCAT_WS(' ', firstname, lastname) LIKE '%$request->search%'")
                    ->orWhere('address->home', 'like', "%$request->search%")
                    ->orWhere('address->shipping', 'like', "%$request->search%")
                    ->orWhere('country->name', 'like', "%$request->search%")
                    ->orWhere('city->name', $request->search)
                    ->orWhere('state->name', $request->search)
                    ->orWhere('state', $request->search);
            });
        }

        // Reorder Columns
        if ($request->order && is_array($request->order)) {
            foreach ($request->order as $key => $dir) {
                if ($dir === 'desc') {
                    $query->orderByDesc($key ?? 'id');
                } else {
                    $query->orderBy($key ?? 'id');
                }
            }
        }

        $users = ($limit <= 0 || $limit === 'all') ? $query->get() : $query->paginate($limit);

        if ($role === 'dispatch') {
            $users->load('dispatches');
        }

        return $this->buildResponse([
            'message' => 'OK',
            'status' => $users->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'users' => $users ?? [],
        ]);
    }

    public function getUser(Request $request, $id)
    {
        $user = User::find($id);
        $user && \Gate::authorize('usable', 'users.'.$user->role);

        return $this->buildResponse([
            'message' => ! $user ? 'The requested user no longer exists' : 'OK',
            'status' => ! $user ? 'info' : 'success',
            'response_code' => ! $user ? 404 : 200,
            'user' => $user ?? (object) [],
        ]);
    }

    public function show(Request $request, User $user)
    {
        \Gate::authorize('usable', 'users.'.$user->role);

        return (new UserResource($user))->additional([
            'message' => 'OK',
            'status' => 'success',
            'response_code' => 200,
        ]);
    }

    public function store(Request $request, $id = '', $skip = false, $legacy = false)
    {
        $user = User::find($id);
        $user && \Gate::authorize('usable', 'users.'.$user->role);
        if ($id && ! $user) {
            return $legacy ? $this->buildResponse([
                'message' => 'The requested user no longer exists',
                'status' => 'info',
                'response_code' => 404,
            ]) : $this->responseBuilder([
                'message' => 'The requested user no longer exists',
                'status' => 'info',
                'response_code' => 404,
            ]);
        }

        $validator = Validator::make($request->all(), [
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
            'country.*' => ['nullable', 'string', 'max:255'],
            'state.*' => ['nullable', 'string', 'max:255'],
            'city.*' => ['nullable', 'string', 'max:255'],
        ], [], [
            'address.home' => 'Home Address',
            'address.shipping' => 'Shipping Address',
        ]);

        if ($validator->fails()) {
            return $legacy ? $this->buildResponse([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'response_code' => 422,
                'errors' => $validator->errors(),
            ]) : $this->responseBuilder([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'response_code' => 422,
                'errors' => $validator->errors(),
            ]);
        }

        $user = $user ?? new User();

        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->username = $request->username ?? $user->username;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->gender = $request->gender ?? 'male';
        $user->nextofkin = $request->nextofkin;
        $user->nextofkin_relationship = $request->nextofkin_relationship;
        $user->nextofkin_phone = $request->nextofkin_phone;
        $user->address = $request->address;
        $user->country = $request->country;
        $user->state = $request->state;
        $user->city = $request->city;
        if ($request->role) {
            $user->role = $request->role;
        }
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return $legacy ? $this->buildResponse([
            'message' => $id ? Str::of($user->fullname)->append(' Has been updated!') : 'New user has been created.',
            'status' => 'success',
            'response_code' => 200,
            'content' => $user,
        ]) : (new UserResource($user))->additional([
            'message' => $id ? Str::of($user->fullname)->append(' Has been updated!') : 'New user has been created.',
            'status' => 'success',
            'response_code' => 200,
        ]);
    }

    public function storeLegacy(Request $request, $id = '')
    {
        return $this->store($request, $id, true, true);
    }

    public function updatePassword(Request $request, User $user)
    {
        $user && \Gate::authorize('usable', 'users.'.$user->role);

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $user ?? new User();

        $user->password = Hash::make($request->password);

        $user->save();

        return (new UserResource($user))->additional([
            'message' => __('Password has been updated!'),
            'status' => 'success',
            'response_code' => 200,
        ]);
    }

    /**
     * Remove the specified user from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id = '')
    {
        // Delete multiple users
        if ($request->users) {
            $count = User::whereIn('id', $request->users)->with(['transactions', 'subscription'])->get()->map(function ($user) {
                $user && \Gate::authorize('usable', 'users.'.$user->role);
                // Delete Transactions
                if ($user->transactions) {
                    $user->transactions->map(function ($transaction) {
                        if ($transaction->transactable) {
                            $transaction->transactable->delete();
                        }
                        $transaction->delete();
                    });
                }
                if ($user->subscriptions) {
                    $user->subscriptions->map(function ($subscription) {
                        $subscription->delete();
                    });
                }
                if ($user) {
                    return $user->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} users have been deleted.",
                'status' => 'success',
                'response_code' => 200,
            ]);
        } else {
            $user = User::whereId($id)->first();
            $user && \Gate::authorize('usable', 'users.'.$user->role);
        }

        // Delete single user
        if ($user) {
            if ($user->transactions) {
                $user->transactions->map(function ($transaction) {
                    if ($transaction->transactable) {
                        $transaction->transactable->delete();
                    }
                    $transaction->delete();
                });
            }
            if ($user->subscriptions) {
                $user->subscriptions->map(function ($subscription) {
                    $subscription->delete();
                });
            }
            $user->delete();

            return $this->buildResponse([
                'message' => "{$user->username} has been deleted.",
                'status' => 'success',
                'response_code' => 200,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested user no longer exists.',
            'status' => 'error',
            'response_code' => 404,
        ]);
    }
}
