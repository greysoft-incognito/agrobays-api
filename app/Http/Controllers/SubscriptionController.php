<?php

namespace App\Http\Controllers;

use App\Models\FoodBag;
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    /**
     * List all the authenticated user's subscriptions.
     *
     * @param  \Illuminate\Http\Client\Request  $request
     * @param  String $action
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $limit = 1, $status = null)
    {
        $subs = Auth::user()->subscriptions()->orderBy('id', 'DESC');

        if (is_numeric($limit) && $limit > 0)
        {
            $subs->limit($limit);
        }

        if ($status !== null && in_array($status, ['active', 'pending', 'complete']))
        {
            $subs->where('status', $status);
        }

        $subscriptions = $subs->get();

        $msg = !$subscriptions ? 'You do not have an active subscription' : 'OK';

        return $this->buildResponse([
            'message' => $msg,
            'status' =>  !$subscriptions ? 'info' : 'success',
            'response_code' => 200,
            'subscriptions' => $subscriptions??[],
        ]);
    }

    /**
     * Display a listing of the user's transactions.
     *
     * @param \Illuminate\Support\Facades\Auth $auth
     * @return \Illuminate\Http\Response
     */
    public function dataTable(Auth $auth, $plan_id = null)
    {
        $model = Subscription::where('user_id', Auth::id());
        if ($plan_id)
        {
            $model->where('plan_id', $plan_id);
        }

        return app('datatables')->eloquent($model)
            ->editColumn('created_at', function(Subscription $item) {
                return $item->created_at->format('Y-m-d H:i');
            })
            ->addColumn('plan', function(Subscription $item) {
                return $item->plan()->title;
            })
            ->addColumn('action', function (Subscription $item) {
                return '<a href="savings/'.$item->plan()->id.'" class="btn btn-xs btn-primary"><i class="ri-file-list-2-fill ri-xl"></i></a>';
            })
            ->removeColumn('updated_at')->toJson();

        // return $this->buildResponse([
        //     'message' => 'OK',
        //     'status' => 'success',
        //     'response_code' => 200,
        //     'transactions' => $auth::user()->transactions()->paginate(15),
        // ]);
    }

    /**
     * List all the authenticated user's subscriptions.
     *
     * @param  \Illuminate\Http\Client\Request  $request
     * @param  String $action
     * @return \Illuminate\Http\Response
     */
    public function subscription(Request $request, $subscription_id = null)
    {
        $subscription = Auth::user()->subscriptions()->find($subscription_id);

        $msg = !$subscription ? 'The subscription you requested no longer exists.' : 'OK';

        return $this->buildResponse([
            'message' => $msg,
            'status' =>  $subscription ? 'success' : 'error',
            'response_code' => $subscription ? 200 : 404,
            'subscription' => $subscription??[],
        ]);
    }

    /**
     * Update the user's foodbag
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateBag(Request $request, $subscription_id = 'user', $id = null)
    {
        $bag = FoodBag::find($id);
        $msg = 'The requested plan no longer exists.';
        if ($subscription_id !== 'user')
        {
            $sub = Subscription::find($subscription_id);
            $ids = $sub ? $sub->plan->bags()->get('id')->values()->toArray() : [];
            $status = 'error';
            $code = 404;
        }
        else
        {
            $sub = Auth::user()->subscription;
            $ids = $sub ? $sub->plan->bags()->get('id')->values()->toArray() : [];
        }

        if ($sub && (!$bag || !in_array($bag->id, Collect($ids[0]??[])->filter(fn($k)=>!empty($k))->values()->toArray())))
        {
            $msg = 'The requested food bag no longer exists.';
            $status = 'error';
            $code = 404;
        }

        // Update the user's current subscription's food bag
        if ($bag)
        {
            unset($msg, $status, $code);
            $plan = $sub;
            $plan->food_bag_id = $bag->id;
            $plan->save();
        }

        return $this->buildResponse([
            'message' => $msg ?? "You have successfully activated the {$bag->title} food bag",
            'status' => $status ?? 'success',
            'response_code' => $code ?? 202,
            'data' => $bag ?? null,
        ]);
    }
}