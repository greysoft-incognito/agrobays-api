<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Dispatch;
use Illuminate\Http\Request;
use Carbon\CarbonImmutable as Carbon;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display a listing of all dispatches based on the status.
     *
     * @param \Illuminate\Http\Request  $request
     * @param  String $type
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $limit = '15', $status = null)
    {
        $query = Auth::user()->orders()->orderBy('id', 'DESC');

        $query->where('payment', 'complete');

        if (is_numeric($limit) && $limit > 0)
        {
            $query->limit($limit);
        }

        if ($p = $request->query('period'))
        {
            $period = explode('-', $p);
            $from = new Carbon($period[0]);
            $to = new Carbon($period[1]);
            $query->whereBetween('created_at', [$from, $to]);
        }

        $orders = $query->get();

        $msg = $orders->isEmpty() ? 'You do not have any orders' : 'OK';
        $_period = $orders->isNotEmpty()
            ? ($orders->last()->created_at->format('Y/m/d') . '-' . $orders->first()->created_at->format('Y/m/d'))
            : "";
        return $this->buildResponse([
            'message' => $msg,
            'status' =>  $orders->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'orders' => $orders??[],
            'period' => $p ? urldecode($p) : $_period
        ]);
    }

    /**
     * Get a particular order
     *
     * @param Request $request
     * @param string $id
     * @return void
     */
    public function getOrder(Request $request, $id)
    {
        $order = Auth::user()->orders()->find($id);

        $msg = !$order ? 'The order you requested no longer exists.' : 'OK';

        return $this->buildResponse([
            'message' => $msg,
            'status' =>  $order ? 'success' : 'error',
            'response_code' => $order ? 200 : 404,
            'order' => $order??[],
        ]);
    }

    /**
     * Display a listing of all dispatches for the authenticated user.
     *
     * @param \Illuminate\Http\Request  $request
     * @param  String $type
     * @return \Illuminate\Http\Response
     */
    public function dispatches(Request $request, $limit = '15')
    {
        $query = Dispatch::where('user_id', '!=', null)->whereRelation('dispatchable.user', 'id', Auth::id());

        if (is_numeric($limit) && $limit > 0)
        {
            $query->limit($limit);
        }

        if ($p = $request->query('period'))
        {
            $period = explode('-', $p);
            $from = new Carbon($period[0]);
            $to = new Carbon($period[1]);
            $query->whereBetween('created_at', [$from, $to]);
        }

        $items = $query->get();

        $msg = $items->isEmpty() ? 'You do not have any dispatched orders' : 'OK';
        $_period = $items->isNotEmpty()
            ? ($items->last()->created_at->format('Y/m/d') . '-' . $items->first()->created_at->format('Y/m/d'))
            : "";
        return $this->buildResponse([
            'message' => $msg,
            'status' =>  $items->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'items' => $items??[],
            'period' => $p ? urldecode($p) : $_period
        ]);
    }

    /**
     * Get a particular dispatch
     *
     * @param Request $request
     * @param string $id
     * @return void
     */
    public function getDispatch(Request $request, $id)
    {
        $query = Dispatch::where('user_id', '!=', null)->whereRelation('dispatchable.user', 'id', Auth::id());

        $item = $query->with(['dispatchable', 'user', 'dispatchable.user'])->find($id);

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
}
