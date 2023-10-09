<?php

namespace App\Http\Controllers\v2\Admin\Users;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\DispatchCollection;
use App\Http\Resources\DispatchResource;
use App\Models\Dispatch;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DispatchController extends Controller
{
    /**
     * Display a listing of all dispatches based on the status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, User $user)
    {
        $query = Dispatch::whereHasMorph(
            'dispatchable',
            [Order::class, Subscription::class],
            function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }
        )->orderBy('id', 'DESC');

        // Set default period
        $period_placeholder = Carbon::now()->subDays(30)->format('Y/m/d') . '-' . Carbon::now()->addDays(2)->format('Y/m/d');

        // Get period
        $period = $request->period == '0' ? [] : explode('-', urldecode($request->get('period', $period_placeholder)));

        $query->when(isset($period[0]), function ($query) use ($period) {
            // Filter by period
            $query->whereBetween('created_at', [new Carbon($period[0]), (new Carbon($period[1]))->addDay()]);
        });

        $dispatches = $query->paginate();

        return (new DispatchCollection($dispatches))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Get a particular dispatched item.
     *
     * @param  Request  $request
     * @param  \App\Models\User  $user
     * @param  string  $id
     * @return void
     */
    public function show(Request $request, User $user, $id)
    {
        $dispatch = Dispatch::whereHasMorph(
            'dispatchable',
            [Order::class, Subscription::class],
            function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }
        )->find($id);

        ! $dispatch && abort(HttpStatus::NOT_FOUND, 'The requested item no longer exists.');

        return (new DispatchResource($dispatch))->additional([
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ]);
    }
}
