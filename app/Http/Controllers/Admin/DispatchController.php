<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispatch;
use App\Notifications\Dispatched;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class DispatchController extends Controller
{
    /**
     * Display a listing of all dispatches based on the status.
     *
     * @param \Illuminate\Http\Request  $request
     * @param  String $type
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $limit = '15', $status = 'pending')
    {
        \Gate::authorize('usable', 'dispatch.'.$status);
        $query = Dispatch::query()->with(['dispatchable', 'user']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Search and filter columns
        if ($request->search) {
            $query->where(function($query) use($request) {
                $query->where('dispatchable_type', 'like', "%$request->search%")
                    ->orWhere('reference', "%$request->search%")
                    ->orWhere('pending', "%$request->search%")
                    ->orWhere('created_at', 'like', "%$request->search%");
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

        $items = ($limit <= 0 || $limit === 'all') ? $query->get() : $query->paginate($limit);

        return $this->buildResponse([
            'message' => 'OK',
            'status' =>  $items->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'items' => $items??[],
        ]);
    }

    public function getDispatch(Request $request, $id)
    {
        $item = Dispatch::with(['dispatchable', 'user', 'dispatchable.user'])->find($id);
        if ($item->type === 'order') {
            $item->load('dispatchable.transaction', 'dispatchable.user');
        } elseif ($item->type === 'foodbag') {
            $item->load('dispatchable.bag', 'dispatchable.user');
        }
        $item && \Gate::authorize('usable', 'dispatch.'.$item->status);

        return $this->buildResponse([
            'message' => !$item ? 'The requested item no longer exists' : 'OK',
            'status' =>  !$item ? 'info' : 'success',
            'response_code' => !$item ? 404 : 200,
            'item' => $item ?? (object)[],
        ]);
    }

    /**
     * Update the status of a dispatch
     *
     * @param Request $request
     * @param string $id
     * @return void
     */
    public function setStatus(Request $request)
    {
        $item = Dispatch::find($request->id);
        $item && \Gate::authorize('usable', 'dispatch.'.$item->status);
        if (!$item) {
            return $this->buildResponse([
                'message' => 'The requested item no longer exists',
                'status' => 'info',
                'response_code' => 404,
            ]);
        }

        $item = $item ?? new Dispatch;

        $item->last_location = $request->last_location ?? $item->last_location;
        $item->status = $request->status ?? 'pending';

        $item->save();

        // Notify the user of the change
        $item->notify(new Dispatched());

        return $this->buildResponse([
            'message' => 'Item status has been updated.',
            'status' =>  'success',
            'response_code' => 200,
            'item' => $item,
        ]);
    }

    /**
     * Save or update a dispatch
     *
     * @param Request $request
     * @param string $id
     * @return void
     */
    public function store(Request $request, $id = '')
    {
        $item = Dispatch::find($id);
        $item && \Gate::authorize('usable', 'dispatch.'.$item->status);
        if ($id && !$item) {
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

        $item = $item ?? new Dispatch;

        $item->user_id = $request->user_id ?? null;
        $item->last_location = $request->last_location ?? $item->last_location;
        $item->status = $request->status ?? 'pending';

        $item->save();

        // Notify the user of the change
        $item->notify(new Dispatched());

        return $this->buildResponse([
            'message' => $id ? 'Item has been updated' : 'New item created.',
            'status' =>  'success',
            'response_code' => 200,
            'item' => $item,
        ]);
    }

    /**
     * Remove the specified dispatch from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id = null)
    {
        if ($request->items)
        {
            $count = collect($request->items)->map(function($id) {
                $item = Dispatch::whereId($id)->first();
                $item && \Gate::authorize('usable', 'dispatch.'.$item->status);
                if ($item) {
                    return $item->delete();
                }
                return false;
            })->filter(fn($i)=>$i!==false)->count();

            return $this->buildResponse([
                'message' => "{$count} items have been deleted.",
                'status' =>  'success',
                'response_code' => 200,
            ]);
        }
        else
        {
            $item = Dispatch::whereId($id)->first();
            $item && \Gate::authorize('usable', 'dispatch.'.$item->status);
        }

        if ($item)
        {
            $item->delete();

            return $this->buildResponse([
                'message' => "Item has been deleted.",
                'status' =>  'success',
                'response_code' => 200,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested item no longer exists.',
            'status' => 'error',
            'response_code' => 404,
        ]);
    }
}
