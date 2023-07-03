<?php

namespace App\Http\Controllers;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Resources\UserBasicDataCollection;
use App\Http\Resources\UserBasicDataResource;
use App\Models\ModelMember;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of all user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (ModelMember::forUser($request->user())->isCooperativeAdmin()->doesntExist()) {
            return $this->responseBuilder([
                'message' => 'You do not have permission to view this resource.',
                'status' => 'error',
                'response_code' => HttpStatus::FORBIDDEN,
            ]);
        }

        $query = User::query();

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

        if ($request->role) {
            $query->where('role', $request->role);

            if ($request->role === 'dispatch') {
                $query->with('dispatches');
            }
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

        $users = $query->paginate($request->get('limit', 30));

        return (new UserBasicDataCollection($users))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Display the specified user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, User $user)
    {
        if (ModelMember::forUser($request->user())->isCooperativeAdmin()->doesntExist()) {
            return $this->responseBuilder([
                'message' => 'You do not have permission to view this resource.',
                'status' => 'error',
                'response_code' => HttpStatus::FORBIDDEN,
            ]);
        }

        return (new UserBasicDataResource($user))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }
}
