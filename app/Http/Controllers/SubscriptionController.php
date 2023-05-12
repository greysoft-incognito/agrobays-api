<?php

namespace App\Http\Controllers;

use App\Http\Resources\SubscriptionCollection;
use App\Http\Resources\SubscriptionResource;
use App\Models\FoodBag;
use App\Models\Subscription;
use Carbon\CarbonImmutable as Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Nette\Utils\Html;

class SubscriptionController extends Controller
{
    /**
     * List all the authenticated user's subscriptions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $limit
     * @param  string  $status
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $limit = 1, $status = null)
    {
        $subs = Auth::user()->subscriptions()->orderBy('id', 'DESC');

        if (is_numeric($limit) && $limit > 0) {
            $subs->limit($limit);
        }

        if ($status !== null && in_array($status, ['active', 'pending', 'complete', 'withdraw', 'closed'])) {
            $subs->where('status', $status);
        }

        if ($p = $request->query('period')) {
            $period = explode('-', $p);
            $from = new Carbon($period[0]);
            $to = new Carbon($period[1]);
            $subs->whereBetween('created_at', [$from, $to]);
        }

        $subscriptions = $subs->get();

        $msg = $subscriptions->isEmpty() ? 'You do not have an active subscription' : 'OK';
        $_period = $subscriptions->isNotEmpty()
            ? ($subscriptions->last()->created_at->format('Y/m/d') . '-' . $subscriptions->first()->created_at->format('Y/m/d'))
            : '';

        return (new SubscriptionCollection($subscriptions))->additional([
            'message' => $msg,
            'status' => $subscriptions->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'period' => $p ? urldecode($p) : $_period,
        ]);
    }

    /**
     * Display a listing of the user's transactions.
     *
     * @param  \Illuminate\Support\Facades\Auth  $auth
     * @return \Illuminate\Http\Response
     */
    public function dataTable(Auth $auth, $plan_id = null)
    {
        $model = Subscription::where('user_id', Auth::id());
        if ($plan_id) {
            $model->where('plan_id', $plan_id);
        }

        return app('datatables')->eloquent($model)
            ->rawColumns(['action'])
            ->editColumn('created_at', function (Subscription $item) {
                return $item->created_at->format('Y-m-d H:i');
            })
            ->addColumn('plan', function (Subscription $item) {
                return $item->plan->title;
            })
            ->editColumn('total_left', function (Subscription $item) {
                return money(num_reformat($item->total_left));
            })
            ->editColumn('total_saved', function (Subscription $item) {
                return money(num_reformat($item->total_saved));
            })
            ->addColumn('action', function (Subscription $item) {
                return implode([
                    Html::el('a', ['onclick' => "hotLink('/savings/plan/" . $item->id . "')", 'href' => 'javascript:void(0)'])->title(__('View Savings'))->setHtml(Html::el('i')->class('ri-eye-fill ri-2x text-primary')),
                ]);
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
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $action
     * @return \Illuminate\Http\Response
     */
    public function subscription(Request $request, $subscription_id = null)
    {
        $subscription = Auth::user()->subscriptions()->find($subscription_id);

        $msg = !$subscription ? 'The subscription you requested no longer exists.' : 'OK';

        return (new SubscriptionResource($subscription))
            ->additional([
                'message' => $msg,
                'status' => $subscription ? 'success' : 'error',
                'response_code' => $subscription ? 200 : 404,
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
        if ($subscription_id !== 'user') {
            $sub = Subscription::find($subscription_id);
            $ids = $sub ? $sub->plan->bags()->get('id')->values()->toArray() : [];
            $status = 'error';
            $code = 404;
        } else {
            $sub = Auth::user()->subscriptions()->where([
                ['status', '!=', 'complete'],
                ['status', '!=', 'withdraw'],
                ['status', '!=', 'closed'],
            ])->latest()->first();
            $ids = $sub ? $sub->plan->bags()->get('id')->values()->toArray() : [];
        }

        if ($sub && (!$bag || !in_array($bag->id, Collect($ids[0] ?? [])->filter(fn ($k) => !empty($k))->values()->toArray()))) {
            $msg = 'The requested food bag no longer exists.';
            $status = 'error';
            $code = 404;
        }

        // Update the user's current subscription's food bag
        if ($bag) {
            unset($msg, $status, $code);
            $plan = $sub;
            $plan->food_bag_id = $bag->id;
            $plan->save();
        }

        return (new SubscriptionResource($sub))
            ->additional([
                'message' => $msg ?? "You have successfully activated the {$bag->title} food bag",
                'status' => $status ?? 'success',
                'response_code' => $code ?? 202,
                'bag' => $bag ?? null,
            ]);
    }
}