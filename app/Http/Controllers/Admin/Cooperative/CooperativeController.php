<?php

namespace App\Http\Controllers\Admin\Cooperative;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CooperativeCollection;
use App\Http\Resources\CooperativeResource;
use App\Models\Cooperative;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CooperativeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        \Gate::authorize('usable', 'cooperatives.manage');
        $query = Cooperative::query();

        if ($request->creator_id) {
            $creator = $request->creator_id;

            $query->where(function ($query) use ($request, $creator) {
                $query->where('user_id', $request->creator_id);
                $query->orWhereHas('members', function (Builder $query) use ($creator) {
                    $query->forUser($creator)->isAccepted();
                });
            });
        }

        if (! auth()->user()->is_admin) {
            $query->where(function ($query) {
                if (config('settings.require_org_approval', false)) {
                    $query->where('is_active', true);
                } else {
                    $query->where('is_active', true);
                    $query->orWhere('is_active', false);
                }
                $query->orWhere('user_id', auth()->user()->id);
            });
        }

        if ($request->follower_id) {
            // Only get feeds from cooperatives the user is following
            $query->where(function ($query) use ($request) {
                $query->whereHas('followers', function (Builder $query) use ($request) {
                    $query->where('user_id', $request->follower_id);
                });
                $query->orWhere('user_id', $request->follower_id);
            });
        }

        // Search and filter columns
        if ($request->search) {
            $query = $query->where('name', 'like', '%'.$request->search.'%');
        }

        if (! $request->search) {
            if ($request->type) {
                $query->where('type', $request->type);
            }

            if ($request->type && ! $request->follower_id && ! $request->following_id) {
                $query->where('type', $request->type);
            }

            // Reorder Columns
            if ($request->order === 'random') {
                $query->inRandomOrder();
            } elseif ($request->order === 'latest') {
                $query->latest();
            } elseif ($request->order === 'oldest') {
                $query->oldest();
            } elseif ($request->order && is_array($request->order)) {
                foreach ($request->order as $key => $dir) {
                    if ($dir == 'desc') {
                        $query->orderByDesc($key ?? 'id');
                    } else {
                        $query->orderBy($key ?? 'id');
                    }
                }
            }
        }

        $orgs = $query->paginate($request->get('limit', 15))->onEachSide(0)->withQueryString();

        return (new CooperativeCollection($orgs))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\v1\Cooperative  $org
     * @return \Illuminate\Http\Response
     */
    public function show(Cooperative $cooperative)
    {
        \Gate::authorize('usable', 'cooperatives.manage');

        return (new CooperativeResource($cooperative))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }
}
