<?php

namespace App\Http\Controllers\v2\Vendor;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\DispatchCollection;
use App\Http\Resources\DispatchResource;
use App\Notifications\Dispatched;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

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
        /** @var \App\Models\Vendor $vendor */
        $vendor = $user->vendor;

        $query = $vendor->dispatches()->getQuery();
        $query->when($request->status && $request->status != 'all', function (Builder $q) use ($request) {
            // Load by status
            $q->where('status', $request->status);
        })->when($request->has('order') && is_array($request->order), function (Builder $q) use ($request) {
            // Reorder Columns
            foreach ($request->order as $key => $dir) {
                if ($dir === 'desc') {
                    $q->orderByDesc($key ?? 'id');
                } else {
                    $q->orderBy($key ?? 'id');
                }
            }
        })->when($request->search, function (Builder $query) use ($request) {
            // Search For an dispatched order
            $query->where(function (Builder $query) use ($request) {
                $query->where('reference', $request->search);
                $query->orWhereHas('user', function (Builder $query) use ($request) {
                    $query->orWhereRaw("CONCAT_WS(' ', firstname, lastname) LIKE '%$request->search%'");
                });
                $query->orWhereHas('dispatchable', function (Builder $query) use ($request) {
                    $query->whereHas('user', function (Builder $query) use ($request) {
                        $query->whereRaw("CONCAT_WS(' ', firstname, lastname) LIKE '%$request->search%'");
                    });
                });
            });
        });

        $dispatches = $query->latest()->paginate($request->get('limit', 30));

        return (new DispatchCollection($dispatches))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Get a particular dispatch
     *
     * @param  Request  $request
     * @param  string  $id
     * @return void
     */
    public function show(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        /** @var \App\Models\Vendor $vendor */
        $vendor = $user->vendor;

        /** @var \App\Models\Dispatch $dispatch */
        $dispatch = $vendor->dispatches()->where(fn ($q) => $q->whereId($id)->orWhere('reference', $id))->firstOrFail();

        return (new DispatchResource($dispatch))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
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
        /** @var \App\Models\Vendor $vendor */
        $vendor = $user->vendor;
        // dd($user->basic_data);
        /** @var \App\Models\Dispatch $dispatch */

        $dispatch = $vendor->dispatches()->findOrFail($id);

        // Validate your request
        $this->validate($request, [
            'log' => 'nullable|string|regex:/\s/',
            'code' => ['nullable', 'required_if:status,delivered', 'string', 'exists:dispatches,code'],
            'status' => 'required|string|in:pending,confirmed,dispatched,delivered',
            'user_id' => ['nullable', 'numeric', 'exists:users,id'],
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
        $dispatch->user_id = $request->user_id ?? null;
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

        return (new DispatchResource($dispatch))->additional([
            'message' => __('This order has been updated successfully.'),
            'status' => 'success',
            'response_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }

    /**
     * Detach the specified dispatch from the vendor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        /** @var \App\Models\Vendor $vendor */
        $vendor = $user->vendor;

        /** @var \App\Models\Dispatch $dispatch */
        $dispatch = $vendor->dispatches()->findOrFail($id);
        $dispatch->vendor_id = null;
        $dispatch->save();

        return (new DispatchResource($dispatch))->additional([
            'message' => __('You rejected this order assinment.'),
            'status' => 'success',
            'response_code' => HttpStatus::ACCEPTED,
        ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }
}
