<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DispatchCollection;
use App\Models\Dispatch;
use App\Notifications\Dispatched;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DispatchController extends Controller
{
    /**
     * Display a listing of all dispatches based on the status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $type
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $limit = '15', $status = 'pending')
    {
        \Gate::authorize('usable', 'dispatch.'.$status);
        $query = Dispatch::query()->with(['dispatchable', 'user']);

        if (Auth::user()->role === 'dispatch') {
            $query->where('user_id', Auth::id());
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('dispatchable_type', 'like', "%$request->search%")
                    ->orWhere('reference', 'like', "%$request->search%")
                    ->orWhere('created_at', 'like', "%$request->search%");
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

        $items = ($limit <= 0 || $limit === 'all') ? $query->get() : $query->paginate($limit);

        return (new DispatchCollection($items))->additional([
            'message' => 'OK',
            'status' => $items->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
        ])->response()->setStatusCode(200);
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
        $query = Dispatch::query();

        if (Auth::user()->role === 'dispatch') {
            $query->where('user_id', Auth::id());
        }

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

    /**
     * Update the status of a dispatch
     *
     * @param  Request  $request
     * @param  string  $id
     * @return void
     */
    public function setStatus(Request $request)
    {
        $query = Dispatch::query();

        \Gate::authorize('usable', 'dispatch.status');
        if (Auth::user()->role === 'dispatch') {
            $query->where('user_id', Auth::id());
        }

        $item = $query->find($request->id);
        $item && \Gate::authorize('usable', 'dispatch.'.$item->status);
        if (! $item) {
            return $this->buildResponse([
                'message' => 'The requested item no longer exists',
                'status' => 'info',
                'response_code' => 404,
            ]);
        }

        $item = $item ?? new Dispatch();
        $item_status = $item->status;
        $item_user_id = $item->user_id;

        $item->last_location = $request->last_location ?? $item->last_location;
        $item->status = $request->status ?? 'pending';

        // Verify confirmation code
        if ($request->status === 'delivered' && (! $request->code || $request->code !== $item->code)) {
            return $this->buildResponse([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'response_code' => 422,
                'errors' => ['code' => 'The confirmation code you entered is incorrect.'],
            ]);
        }

        $item->save();

        // Update the location of all this dispatch user's packages
        if ($item->last_location->lon && $item->last_location->lat) {
            Dispatch::where('user_id', $item->user_id)
                ->where('status', 'confirmed')
                ->update(['last_location' => ['lon' => $item->last_location->lon, 'lat' => $item->last_location->lat]]);
        }

        // Notify the user of the change
        if ((! $item_user_id && $request->status === 'pending') || $item_status !== $request->status) {
            $item->dispatchable->user->notify(new Dispatched($item));
        }

        return $this->buildResponse([
            'message' => 'Item status has been updated.',
            'status' => 'success',
            'response_code' => 200,
            'item' => $item,
        ]);
    }

    /**
     * Save or update a dispatch
     *
     * @param  Request  $request
     * @param  string  $id
     * @return void
     */
    public function store(Request $request, $id = '')
    {
        $query = Dispatch::query();

        \Gate::authorize('usable', 'dispatch.update');
        if (Auth::user()->role === 'dispatch') {
            $query->where('user_id', Auth::id());
        }

        $item = $query->find($id);
        if ($id && ! $item) {
            return $this->buildResponse([
                'message' => 'The requested item no longer exists',
                'status' => 'info',
                'response_code' => 404,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'numeric'],
            'last_location' => 'nullable|array',
            'status' => 'required|string',
        ], );

        if ($validator->fails()) {
            return $this->buildResponse([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'response_code' => 422,
                'errors' => $validator->errors(),
            ]);
        }

        $item = $item ?? new Dispatch();
        $item_status = $item->status;
        $item_user_id = $item->user_id;
        $item_code = $item->code;

        $item->user_id = $request->user_id ?? null;
        $item->last_location = $request->last_location ?? $item->last_location;
        $item->status = $request->status ?? 'pending';

        // Verify confirmation code
        if (Auth::user()->role !== 'admin' && $request->status === 'delivered' && (! $request->code || $request->code !== $item_code)) {
            return $this->buildResponse([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'response_code' => 422,
                'errors' => ['code' => 'The confirmation code you entered is incorrect.'],
            ]);
        }

        $item->save();

        // Notify the Dispatch Rider
        if ($item_user_id !== $item->user_id) {
            $item->user->notify(new Dispatched($item, 'assigned'));
        }

        // Notify the user of the change
        if ((! $item_user_id && $request->status === 'pending') || $item_status !== $request->status) {
            $item->dispatchable->user->notify(new Dispatched($item));
        }

        return $this->buildResponse([
            'message' => $id ? 'Item has been updated' : 'New item created.',
            'status' => 'success',
            'response_code' => 200,
            'item' => $item,
        ]);
    }

    /**
     * Remove the specified dispatch from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id = null)
    {
        $query = Dispatch::query();

        \Gate::authorize('usable', 'dispatch.delete');
        if (Auth::user()->role === 'dispatch') {
            $query->where('user_id', Auth::id());
        }

        if ($request->items) {
            $count = collect($request->items)->map(function ($id) use ($query) {
                $item = $query->whereId($id)->first();
                $item && \Gate::authorize('usable', 'dispatch.'.$item->status);
                if ($item) {
                    return $item->delete();
                }

                return false;
            })->filter(fn ($i) => $i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} items have been deleted.",
                'status' => 'success',
                'response_code' => 200,
            ]);
        } else {
            $item = $query->whereId($id)->first();
        }

        if ($item) {
            $item->delete();

            return $this->buildResponse([
                'message' => 'Item has been deleted.',
                'status' => 'success',
                'response_code' => 200,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested item no longer exists.',
            'status' => 'error',
            'response_code' => 404,
            'ignore' => [404],
        ]);
    }
}
