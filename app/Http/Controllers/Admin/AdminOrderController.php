<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminOrderController extends Controller
{
    public function index(Request $request, $limit = '15')
    {
        \Gate::authorize('usable', 'orders');
        $query = Order::query()->with('user');

        // Search and filter columns
        if ($request->search) {
            $query->where(function ($query) use ($request) {
                $query->where('status', 'like', "%$request->search%");
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

        return $this->buildResponse([
            'message' => 'OK',
            'status' =>  $items->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'items' => $items ?? [],
        ]);
    }

    public function getItem(Request $request, $item)
    {
        \Gate::authorize('usable', 'orders');
        $order = Order::whereId($item)->first();

        return $this->buildResponse([
            'message' => ! $order ? 'The requested order no longer exists' : 'OK',
            'status' =>  ! $order ? 'info' : 'success',
            'response_code' => ! $order ? 404 : 200,
            'order' => $order,
        ]);
    }

    /**
     * Update the order status
     *
     * @param  Request  $request
     * @param  int  $item
     * @return void
     */
    public function store(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'orders');
        $order = Order::find($item);
        if (! $order) {
            return $this->buildResponse([
                'message' => 'The requested order no longer exists',
                'status' => 'error',
                'response_code' => 404,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,rejected,shipped,delivered',
        ]);

        if ($validator->fails()) {
            return $this->buildResponse([
                'message' => 'Your input has a few errors',
                'status' => 'error',
                'response_code' => 422,
                'errors' => $validator->errors(),
            ]);
        }

        $order->status = $request->status;
        $order->save();

        return $this->buildResponse([
            'message' => 'Order status updated.',
            'status' =>  'success',
            'response_code' => 200,
            'plan' => $order,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $item = null)
    {
        \Gate::authorize('usable', 'orders');
        if ($request->items) {
            $count = collect($request->items)->map(function ($item) {
                $order = Order::whereId($item)->first();
                if ($order) {
                    return $order->delete();
                }

                return false;
            })->filter(fn ($i) =>$i !== false)->count();

            return $this->buildResponse([
                'message' => "{$count} orders bags have been deleted.",
                'status' =>  'success',
                'response_code' => 200,
            ]);
        } else {
            $order = Order::whereId($item)->first();
        }

        if ($order) {
            $food->delete();

            return $this->buildResponse([
                'message' => 'Order has been deleted.',
                'status' =>  'success',
                'response_code' => 200,
            ]);
        }

        return $this->buildResponse([
            'message' => 'The requested order no longer exists.',
            'status' => 'error',
            'response_code' => 404,
        ]);
    }
}
