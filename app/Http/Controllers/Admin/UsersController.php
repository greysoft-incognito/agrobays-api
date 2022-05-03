<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    /**
     * Display a listing of all user.
     *
     * @param \Illuminate\Http\Request  $request
     * @param  String $type
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $limit = '15', $role = 'user')
    {
        $query = User::query();

        if ($role !== 'all') {
            $query->where('role', $role);
        }

        // Search and filter columns
        if ($request->search) {
            $query->where(function($query) use($request) {
                $query->where('username', 'like', "%$request->search%")
                    ->orWhere('lastname', 'like', "%$request->search%")
                    ->orWhere('firstname', 'like', "%$request->search%")
                    ->orWhere('address->home', 'like', "%$request->search%")
                    ->orWhere('address->shipping', 'like', "%$request->search%")
                    ->orWhere('country->name', 'like', "%$request->search%")
                    ->orWhere('city->name', 'like', "%$request->search%")
                    ->orWhere('state->name', 'like', "%$request->search%")
                    ->orWhere('gender', 'like', "%$request->search%")
                    ->orWhere('state', 'like', "%$request->search%");
            });
        }

        // Reorder Columns
        if ($request->order && is_array($request->order)) {
            foreach ($request->order as $key => $dir) {
                if ($dir === 'desc') {
                    $query->orderByDesc($key??'id');
                } else {
                    $query->orderBy($key??'id');
                }
            }
        }

        $users = ($limit <= 0 || $limit === 'all') ? $query->get() : $query->paginate($limit);

        return $this->buildResponse([
            'message' => 'OK',
            'status' =>  $users->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'users' => $users??[],
        ]);
    }

    public function getUser(Request $request, $id)
    {
        $user = User::find($id);

        return $this->buildResponse([
            'message' => !$user ? 'The requested user no longer exists' : 'OK',
            'status' =>  !$user ? 'info' : 'success',
            'response_code' => !$user ? 404 : 200,
            'user' => $user ?? (object)[],
        ]);
    }

    public function store(Request $request, $id = '')
    {
        $user = User::find($id);
        if ($id && !$user) {
            return $this->buildResponse([
                'message' => 'The requested user no longer exists',
                'status' => 'info',
                'response_code' => 404,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id??'')],
            'phone' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id??'')],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($user->id??'')],
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

        $user = $user ?? new User;

        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->username = $request->username??$user->username;
        $user->email = $request->email;
        $user->password = $request->password;
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
            'message' => $id ? Str::of($user->name)->append(' Has been updated!') : 'New user has been created.',
            'status' =>  'success',
            'response_code' => 200,
            'content' => $user,
        ]);
    }

    /**
     * Remove the specified user from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id = '')
    {
        if ($request->users)
        {
            $count = collect($request->users)->map(function($id) {
                $user = User::whereId($id)->first();
                if ($user) {
                    $user->image && Storage::delete($user->image);
                    return $user->delete();
                }
                return false;
            })->filter(fn($i)=>$i!==false)->count();

            return $this->buildResponse([
                'message' => "{$count} users have been deleted.",
                'status' =>  'success',
                'response_code' => 200,
            ]);
        }
        else
        {
            $user = User::whereId($id)->first();
        }

        if ($user)
        {
            $user->image && Storage::delete($user->image);
            $user->delete();

            return $this->buildResponse([
                'message' => "{$user->username} has been deleted.",
                'status' =>  'success',
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
