<?php

namespace App\Http\Controllers\v2\Admin;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of all paid orders.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $type
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        \Gate::authorize('usable', 'orders');
        $query = Order::query()->orderBy('id', 'DESC');

        // Set default period
        $period_placeholder = Carbon::now()->subDays(30)->format('Y/m/d') . '-' . Carbon::now()->addDays(2)->format('Y/m/d');

        // Get period
        $period = $request->period == '0' ? [] : explode('-', urldecode($request->get('period', $period_placeholder)));

        $query->when(isset($period[0]), function ($query) use ($period) {
            // Filter by period
            $query->whereBetween('created_at', [new Carbon($period[0]), (new Carbon($period[1]))->addDay()]);
        });

        $query->where('payment', 'complete');

        $orders = $query->paginate();

        return (new OrderCollection($orders))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ]);
    }

    /**
     * Get a particular order
     *
     * @param  Request  $request
     * @param  \App\Models\Order  $order
     * @return void
     */
    public function show(Order $order)
    {
        \Gate::authorize('usable', 'orders');
        return (new OrderResource($order))->additional([
            'status' => 'success',
            'response_code' => HttpStatus::OK,
        ]);
    }
}
