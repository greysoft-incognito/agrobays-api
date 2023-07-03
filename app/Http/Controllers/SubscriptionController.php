<?php

namespace App\Http\Controllers;

use App\Http\Resources\SubscriptionCollection;
use App\Http\Resources\SubscriptionResource;
use App\Models\Cooperative;
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
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $status = $request->status;
        $limit = $request->limit;

        $subs = Auth::user()->subscriptions()->orderBy('id', 'DESC');

        if (is_numeric($limit) && $limit > 0) {
            $subs->limit($limit);
        }

        if ($status && in_array($status, ['active', 'pending', 'complete', 'withdraw', 'closed'])) {
            $subs->where('status', $status);
        }

        if ($p = $request->query('period')) {
            $period = explode('-', $p);
            $subs->whereBetween('created_at', [new Carbon($period[0]), new Carbon($period[1])]);
        }

        $subscriptions = $subs->get();

        $msg = $subscriptions->isEmpty() ? 'You do not have an active subscription' : 'OK';

        $first = $subscriptions->first();
        $last = $subscriptions->last();
        $_period = $subscriptions->isNotEmpty()
            ? ($last->created_at->format('Y/m/d') . '-' . $first->created_at->format('Y/m/d'))
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
    }

    /**
     * Get the subscription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $subscription_id
     * @return \Illuminate\Http\Response
     */
    public function subscription(Request $request, $subscription_id)
    {
        if ($request->cooperative_id) {
            $subscription = Cooperative::whereId($request->cooperative_id)
                                ->orWhere('slug', $request->cooperative_id)
                                ->firstOrFail()
                                ->subscriptions()
                                ->findOrfail($subscription_id);
        } else {
            $subscription = Auth::user()->subscriptions()->findOrfail($subscription_id);
        }

        return (new SubscriptionResource($subscription))
            ->additional([
                'message' => 'OK',
                'status' => 'error',
                'response_code' => 200,
            ]);
    }

    /**
     * Sets the automation mode for the subscription
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $subscription
     * @return \Illuminate\Http\Response
     */
    public function automate(Request $request, $subscription_id)
    {
        $request->validate([
            'interval' => ['nullable', 'in:daily,weekly,monthly,yearly'],
        ]);

        $subscription = Auth::user()->subscriptions()->findOrfail($subscription_id);

        $subscription->interval = $request->interval ? $request->interval : 'manual';
        $subscription->save();

        return (new SubscriptionResource($subscription))
            ->additional([
                'message' => __('You have successfully :0 the :1 automatic savings program for this plan.', [
                    $request->interval ? 'activated' : 'deactivated',
                    $request->interval ? $request->interval . ' ' : '',
                ]),
                'status' => 'error',
                'response_code' => 200,
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
        $msg = 'The requested plan no longer exists.';
        if ($subscription_id !== 'user') {
            if ($request->cooperative_id) {
                /** @var \App\Models\Cooperative */
                $cooperative = Cooperative::whereSlug($request->cooperative_id)
                                ->orWhere('id', $request->cooperative_id)
                                ->firstOrFail();
                /** @var \App\Models\Subscription */
                $sub = $cooperative->subscriptions()->findOrFail($subscription_id);
            } else {
                /** @var \App\Models\Subscription */
                $sub = Auth::user()->subscriptions()->findOrFail($subscription_id);
            }
        } else {
            /** @var \App\Models\Subscription */
            $sub = Auth::user()->subscriptions()->where([
                ['status', '!=', 'complete'],
                ['status', '!=', 'withdraw'],
                ['status', '!=', 'closed'],
            ])->latest()->firstOrFail();
        }

        $bag = $sub->plan->bags()->find($id);

        if (! $bag) {
            $msg = 'The requested food bag is no longer available.';
            $status = 'error';
            $code = 404;
        }

        // Update the user's current subscription's food bag
        if ($bag) {
            unset($msg, $status, $code);
            if ($request->has('cooperative_id')) {
                /** @var \App\Models\CooperativeMembersFoodbag */
                $foodbag = $cooperative->foodbags()->updateOrCreate([
                    'user_id' => Auth::id(),
                    'subscription_id' => $sub->id,
                ], [
                    'food_bag_id' => $bag->id,
                    'approved' => (bool)$cooperative->settings['auto_approve_foodbags'] ?? false,
                ]);
            } else {
                $plan = $sub;
                $plan->food_bag_id = $bag->id;
                $plan->delivery_method = $request->delivery_method ?? 'delivery';
                $plan->save();
            }
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
