<?php

namespace App\Http\Controllers\v2\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\DispatchCollection;
use App\Http\Resources\DispatchResource;
use App\Models\Dispatch;
use App\Models\Order;
use App\Models\Subscription;
use App\Notifications\Dispatched;
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
        /** @var \App\Models\User $user */
        $user = $request->user();

        $status = $request->get('status', 'pending');

        \Gate::authorize('usable', 'dispatch.' . $status);

        $query = Dispatch::query();

        // Set default period
        $period_placeholder = Carbon::now()->subDays(30)->format('Y/m/d') . '-' . Carbon::now()->addDays(2)->format('Y/m/d');

        // Get period
        $period = $request->period == '0' ? [] : explode('-', urldecode($request->get('period', $period_placeholder)));

        $query->when(isset($period[0]), function ($query) use ($period) {
            // Filter by period
            $query->whereBetween('dispatches.created_at', [new Carbon($period[0]), (new Carbon($period[1]))->addDay()]);
        });

        $query->when($user->role === 'dispatch' || $request->restrict === 'dispatch', function ($query) use ($user) {
            // Filter by handler
            $query->where('dispatches.user_id', $user->id);
        });

        // Set the dispatch Status
        if (in_array($status, ['pending', 'confirmed', 'dispatched', 'delivered'])) {
            $query->where('dispatches.status', $status);
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

        $dispatches = $query->latest('placed_at')->paginate($request->get('limit', 30));

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
     * @param  string  $id
     * @return void
     */
    public function show(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->role === 'dispatch' || $request->restrict === 'dispatch') {
            $query = $user->dispatches();
            $dispatched = $query->where(fn ($q) => $q->whereId($id)->orWhere('reference', $id))->firstOrFail();
        } else {
            $dispatched = Dispatch::whereId($id)->orWhere('reference', $id)->firstOrFail();
        }

        $dispatched->load(['user']);

        \Gate::authorize('usable', 'dispatch.' . $dispatched->status);

        return (new DispatchResource($dispatched))->additional([
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Update the status of a dispatch
     * Save or update a dispatch
     *
     * Set status
     * Assign Rider
     * Update Location
     *
     * @param  Request  $request
     * @param  string  $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->role === 'dispatch') {
            /** @var \App\Models\Dispatch $dispatch */
            $dispatch = $user->dispatches()->findOrFail($id);
        } else {
            /** @var \App\Models\Dispatch $dispatch */
            $dispatch = Dispatch::findOrFail($id);
        }

        \Gate::authorize('usable', 'dispatch.' . $dispatch->status);

        // Validate your request
        $this->validate($request, [
            'log' => 'nullable|string|regex:/\s/',
            'code' => ['nullable', 'required_if:status,delivered', 'string', 'exists:dispatches,code'],
            'status' => 'required|string|in:pending,confirmed,dispatched,delivered',
            'user_id' => ['nullable', 'numeric', 'exists:users,id'],
            'vendor_id' => ['nullable', 'numeric', 'exists:users,id'],
            'last_location' => 'nullable|array',
            'last_location.lat' => 'required|string',
            'last_location.lng' => 'required|string',
        ], [
            'log.regex' => 'Your log must contain at least 2 words',
            'code.exists' => __(implode(" ", [
                "You have entered an invalid confirmation code,",
                "please reachout to admin or the customer for assistance.",
            ]))
        ]);

        $old_status = $dispatch->status;
        $item_user_id = $dispatch->user_id;

        $dispatch->status = $request->status ?? 'pending';
        if ($request->user_id) {
            $dispatch->user_id = $request->user_id;
        }
        if ($request->vendor_id) {
            $dispatch->vendor_id = $request->vendor_id;
        }
        $dispatch->last_location = $request->last_location ?? $dispatch->last_location;

        if ($request->log) {
            $dispatch->extra_data = $dispatch->log($request->log, $user->id);
        }

        $dispatch->save();

        // Notify the Dispatch Rider
        if ($item_user_id !== $dispatch->user_id) {
            $dispatch->user->notify(new Dispatched($dispatch, 'assigned'));
        }

        // Notify the user of the change
        if ((!$item_user_id && $request->status === 'pending') || $old_status !== $request->status) {
            $dispatch->dispatchable->user->notify(new Dispatched($dispatch));
            $dispatch->log($request->status, $user->id, true);
        }

        $dispatch->load(['user']);

        return (new DispatchResource($dispatch))->additional([
            'message' => __('This order has been updated successfully.'),
            'status' => 'success',
            'response_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Detach the specified dispatch from the user.
     * Delete the specified dispatch if user is admin
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->role === 'dispatch') {
            /** @var \App\Models\Dispatch $dispatch */
            $dispatch = $user->dispatches()->findOrFail($id);
            $dispatch->user_id = null;
            $dispatch->save();
            $message = __('You rejected this order assinment.');
        } else {
            /** @var \App\Models\Dispatch $dispatch */
            $dispatch = Dispatch::findOrFail($id);
            $dispatch->delete();
            $message = __('You have deleted this order dispatch.');
        }

        return (new DispatchResource($dispatch))->additional([
            'message' => $message,
            'status' => 'success',
            'response_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }
}
