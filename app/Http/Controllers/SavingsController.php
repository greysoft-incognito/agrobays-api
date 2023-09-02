<?php

namespace App\Http\Controllers;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Resources\FoodBagCollection;
use App\Http\Resources\FoodBagResource;
use App\Http\Resources\PlanCollection;
use App\Http\Resources\SavingCollection;
use App\Models\Cooperative;
use App\Models\FoodBag;
use App\Models\Plan;
use App\Models\Saving;
use App\Models\Subscription;
use App\Notifications\SubStatus;
use Carbon\CarbonImmutable as Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavingsController extends Controller
{
    /**
     * Display a listing of the user's savings
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Support\Facades\Auth  $auth
     * @param  int  $sub_id
     * @param  int  $limit
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Auth $auth, $subscription = null)
    {
        $limit = $request->limit;
        $status = $request->status;

        if ($request->cooperative_id) {
            $query = Saving::query();
            $query->whereHas('subscription', function (Builder $q) use ($request) {
                $q->whereHas('cooperative', function (Builder $q) use ($request) {
                    $q->whereSlug($request->cooperative_id)->orWhere('id', $request->cooperative_id);
                });
            });
        } else {
            $query = $auth::user()->savings()->whereDoesntHave('subscription', function (Builder $q) {
                $q->whereHas('cooperative');
            });
        }

        if ($subscription) {
            $query->where('subscription_id', $subscription);
        }

        if (in_array($status, ['rejected', 'pending', 'complete'])) {
            $query->where('status', $status);
        }

        if ($p = $request->get('period')) {
            $period = explode('-', $p);
            $query->whereBetween('created_at', [new Carbon($period[0]), new Carbon($period[1])]);
        }

        if ($limit > 0 && ! $request->paginate) {
            $query->limit($limit);
        }

        $query->orderBy('id', 'DESC');

        /** @var \App\Models\Saving */
        $savings = $request->boolean('paginate')
            ? $query->paginate($request->get('limit', 30))
            : $query->get();

        $msg = $savings->isEmpty() ? 'You have not made any savings.' : 'OK';
        $last = $savings->last();
        $first = $savings->first();

        $_period = $savings->isNotEmpty()
            ? ($last->created_at->format('Y/m/d').'-'.$first->created_at->format('Y/m/d'))
            : '';

        return (new SavingCollection($savings))->additional([
            'message' => $msg,
            'status' => $savings->isEmpty() ? 'info' : 'success',
            'response_code' => HttpStatus::OK,
            'period' => $p ? urldecode($p) : $_period,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function plans()
    {
        $plans = Plan::all();

        return (new PlanCollection($plans))->additional([
            'message' => $plans->isEmpty() ? 'There are no saving plans for now.' : 'OK',
            'status' => $plans->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
        ])->response()->setStatusCode(200);
    }

    /**
     * Get a particular fruit bay plan by it's {id} or {slug}
     *
     * @param  Request  $request
     * @param  string|int  $plan
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function getPlan(Request $request, $plan = 'user')
    {
        if ($plan === 'user') {
            $plan = Auth::user()->subscriptions()->where([
                ['status', '!=', 'complete'],
                ['status', '!=', 'withdraw'],
                ['status', '!=', 'closed'],
            ])->latest()->first()->plan;
        } else {
            $plan = Plan::whereId($plan)->orWhere(['slug' => $plan])->first();
        }

        return $this->buildResponse([
            'message' => ! $plan ? 'The requested plan is no longer available' : 'OK',
            'status' => ! $plan ? 'error' : 'success',
            'response_code' => ! $plan ? 404 : 200,
            'plan' => $plan,
        ]);
    }

    /**
     * Get the food bags for the selected plan
     * Pass the food_bag id to get a particular food bag
     *
     * @param  Request  $request
     * @param  string|int  $plan
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function getBags(Request $request, $plan = 'user', $id = null)
    {
        if ($plan === 'user') {
            $plan = Auth::user()->subscriptions()->where([
                ['status', '!=', 'complete'],
                ['status', '!=', 'withdraw'],
                ['status', '!=', 'closed'],
            ])->latest()->first()->plan;
        } else {
            $plan = Plan::whereId($plan)->orWhere(['slug' => $plan])->first();
        }

        if (! $plan) {
            return $this->buildResponse([
                'message' => 'The requested plan no longer exists.',
                'status' => 'error',
                'response_code' => 404,
            ]);
        } elseif ($id && ! ($bag = $plan->bags()->find($id))) {
            return $this->buildResponse([
                'message' => 'The requested food bag no longer exists.',
                'status' => 'info',
                'response_code' => 404,
            ]);
        }

        if ($id) {
            return (new FoodBagResource($bag))->additional([
                'message' => 'OK',
                'status' => 'success',
                'response_code' => 200,
            ])->response()->setStatusCode(200);
        } else {
            return (new FoodBagCollection($plan->bags))->additional([
                'message' => 'OK',
                'status' => 'success',
                'response_code' => 200,
            ])->response()->setStatusCode(200);
        }
    }

    /**
     * Subscribe to a new saving's plan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $id = null)
    {
        $plan = Plan::find($id);

        $checkQuery = Subscription::where('user_id', Auth::id())->whereDoesntHave('allSavings', function (Builder $query) {
            $query->where('status', 'complete');
        });

        /** @var \App\Models\Subscription */
        $query = $request->user()->subscriptions();

        if ($request->cooperative_id) {
            $checkQuery->whereCooperativeId($request->cooperative_id);

            /** @var \App\Models\Cooperative */
            $cooperative = Cooperative::find($request->cooperative_id);
            $this->authorize('manage', [$cooperative, 'manage_plans']);

            /** @var \App\Models\Subscription */
            $query = $cooperative->subscriptions();
        }

        $planActiveNoSavings = $checkQuery->exists();

        if (! $plan) {
            return $this->responseBuilder([
                'message' => 'The requested plan no longer exists.',
                'status' => 'error',
                'response_code' => 404,
            ]);
        } elseif ($planActiveNoSavings) {
            return $this->responseBuilder([
                'message' => 'You need to make at least one savings on all your existing subscriptions before you can subscribe to another plan.',
                'status' => 'info',
                'response_code' => 406,
            ]);
        } elseif ($query->where([['plan_id', '=', $id], ['status', '=', 'active']])->exists()) {
            return $this->responseBuilder([
                'message' => 'You are already active on this plan, but you can subscribe to another plan.',
                'status' => 'info',
                'response_code' => 406,
            ]);
        }

        // Delete user's current subscription
        if ($request->cooperative_id) {
            Subscription::where('cooperative_id', $request->cooperative_id)->where('status', 'pending')->delete();
        } else {
            Subscription::where('user_id', Auth::id())->where('status', 'pending')->delete();
        }

        // Create the new plan
        $userPlan = new Subscription();
        $userPlan->user_id = Auth::id();
        $userPlan->plan_id = $plan->id;
        $userPlan->food_bag_id = $plan->bags()->first()->id ?? FoodBag::first()->id;

        if ($request->cooperative_id) {
            $userPlan->cooperative_id = $request->cooperative_id;
        }
        $userPlan->save();

        return $this->responseBuilder([
            'message' => __('You have successfully activated the :0:1', [
                $plan->title,
                $request->cooperative_id ? ' for '.$userPlan->cooperative->name : null,
            ]),
            'status' => 'success',
            'response_code' => 201,
            'data' => $userPlan,
        ]);
    }

    /**
     * Terminate new saving's plan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function terminate(Request $request, $id = null)
    {
        // dd($request->route());

        /** @var \App\Models\Subscription */
        $sub = Subscription::find($request->subscription_id ?? $request->plan_id);

        if (! $sub) {
            return $this->responseBuilder([
                'message' => 'You are not subscribed to this plan.',
                'status' => 'error',
                'response_code' => 404,
            ]);
        }

        if ($sub?->cooperative || $sub->status === 'closed') {
            $msg = $sub->cooperative
                ? 'You cannot terminate an active subscription to a cooperative plan.'
                : 'You already terminated this subscription.';

            return $this->responseBuilder([
                'message' => $msg,
                'status' => 'error',
                'response_code' => 406,
            ]);
        } else {
            $this->authorize('be-owner', [$sub->user_id]);
        }

        if (config('settings.withdraw_to') === 'wallet') {
            $sub->status = 'closed';
            $sub->save();
            $sub->user->wallet()->firstOrNew()->topup(
                'Refunds',
                $sub->saved_amount,
                __('Refunds for :0.', [$sub->plan->title])
            );
            $sub->user->notify(new SubStatus($sub, 'closed'));
            $message = __('Your subscription to the :0 has been terminated, :1.', [
                $sub->plan->title,
                'your savings will be withdrawn to your wallet.',
            ]);
        } else {
            $sub->status = 'withdraw';
            $sub->save();
            $message = __('You have successfully terminated your saving for the :0, your withdrawal request has been logged and will be proccessed along with the next batch.', [$sub->plan->title]);
        }

        return $this->buildResponse([
            'message' => $message,
            'status' => 'success',
            'response_code' => 201,
            'data' => $sub,
        ]);
    }
}
