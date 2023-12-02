<?php

namespace App\Http\Controllers\v2;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserSlimCollection;
use App\Http\Resources\UserSlimResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * @param Request $request
     *
     * Display a listing of the resource
     */
    public function index(Request $request)
    {
        $query = User::query();

        Gate::denyIf(
            fn (User $user) => $user->role == 'user' && !$user->vendor->exists,
            'You do not have permission to perform this action.'
        );

        $query->when($request->search, function (Builder $q) use ($request) {
            $q->where(function (Builder $q) use ($request) {
                $q->where('country', $request->search);
                $q->orWhere('city', $request->search);
                $q->orWhere('state', $request->search);
                $q->orWhere('role', $request->search);
                $q->orWhere('email', $request->search);
                $q->orWhere('phone', $request->search);
                $q->orWhere('address', 'like', "%{$request->search}%");
                $q->orWhere('username', 'like', "%{$request->search}%");
                $q->orWhere('firstname', 'like', "%{$request->search}%");
                $q->orWhereRaw("CONCAT_WS(' ', firstname, lastname) LIKE '%$request->search%'");
            });
        })->when($request->order && is_array($request->order), function (Builder $q) use ($request) {
            foreach ($request->order as $by => $dir) {
                if (!in_array($by, ['desc', 'asc', 'null', null, ''])) {
                    $q->orderBy($by, mb_strtoupper($dir));
                }
            }
        })->when($request->role, function (Builder $q) use ($request) {
            if ($request->role === 'vendor') {
                $q->whereHas('vendor');
            } else {
                $q->where('role', $request->role);
            }
        })->latest();

        $users = $query->paginate($request->get('limit', '15'));

        return (new UserSlimCollection($users))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return (new UserSlimResource($user))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }
}
