<?php

namespace App\Http\Controllers\v2\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\DispatchCollection;
use App\Http\Resources\DispatchResource;
use App\Models\Dispatch;
use App\Models\Order;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DispatchController extends Controller
{
    /**
     * Display a listing of all dispatches based on the status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $type
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        \Gate::authorize('usable', 'dispatch.'.$status);

        $query = Dispatch::orderBy('id', 'DESC');

        // Set default period
        $period_placeholder = Carbon::now()->subDays(30)->format('Y/m/d').'-'.Carbon::now()->addDays(2)->format('Y/m/d');

        // Get period
        $period = $request->period == '0' ? [] : explode('-', urldecode($request->get('period', $period_placeholder)));

        $query->when(isset($period[0]), function ($query) use ($period) {
            // Filter by period
            $query->whereBetween('created_at', [new Carbon($period[0]), (new Carbon($period[1]))->addDay()]);
        });

        // Set the dispatch Status
        if (in_array($status, ['pending', 'confirmed', 'dispatched', 'delivered'])) {
            $query->whereStatus($status);
        }

        // Search For an dispatched order
        $query->when($request->search, function ($query) use ($request) {
            $query->where(function ($query) use ($request) {
                $query->where('reference', $request->search);
                $query->orWhereHas('user', function ($query) use ($request) {
                    $query->orWhereRaw("CONCAT_WS(' ', firstname, lastname) LIKE '%$request->search%'");
                });
                $query->orWhereHas('dispatchable', function ($query) use ($request) {
                    $query->whereHas('user', function ($query) use ($request) {
                        $query->whereRaw("CONCAT_WS(' ', firstname, lastname) LIKE '%$request->search%'");
                    });
                });
            });
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
     * @param  \App\Models\Dispatch  $dispatched
     * @return void
     */
    public function show(Dispatch $dispatched)
    {
        return (new DispatchResource($dispatched))->additional([
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Get a particular dispatch
     *
     * @param  Request  $request
     * @param  string  $id
     * @return void
     */
    public function getDispatch(Request $request, $id)
    {
        $query = Dispatch::where('user_id', '!=', null)->whereHasMorph(
            'dispatchable',
            [Order::class, Subscription::class],
            function ($query) {
                $query->where('user_id', Auth::id());
            }
        );

        $item = $query->with(['dispatchable', 'user', 'dispatchable.user'])->find($id);

        if ($item->type === 'order') {
            $item->load('dispatchable.transaction', 'dispatchable.user');
        } elseif ($item->type === 'foodbag') {
            $item->load('dispatchable.bag', 'dispatchable.user');
        }
        $item && \Gate::authorize('usable', 'dispatch.'.$item->status);

        return $this->buildResponse([
            'message' => ! $item ? 'The requested item no longer exists' : 'OK',
            'status' => ! $item ? 'info' : 'success',
            'response_code' => ! $item ? 404 : 200,
            'item' => $item ?? (object) [],
        ]);
    }
}
