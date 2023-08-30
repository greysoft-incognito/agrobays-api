<?php

namespace App\Http\Controllers\v2\User;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionCollection;
use App\Http\Resources\SubscriptionResource;
use App\Models\Cooperative;
use App\Models\Subscription;
use App\Notifications\SubStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $status = $request->status;
        $limit = $request->limit;

        $cooperative = null;
        if ($request->cooperative_id) {
            $cooperative = Cooperative::whereId($request->cooperative_id)
                ->orWhere('slug', $request->cooperative_id)
                ->firstOrFail();
            $subs = $cooperative->subscriptions()->orderBy('id', 'DESC');
        } else {
            $subs = Auth::user()->subscriptions()->orderBy('id', 'DESC');
        }

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

        $msg = $subscriptions->isEmpty()
            ? __(':0 not have an active subscription', [
                $cooperative ? $cooperative->name . ' does' : 'You do'
            ])
            : HttpStatus::message(HttpStatus::OK);

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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        if ($request->cooperative_id) {
            $subscription = Cooperative::whereId($request->cooperative_id)
                ->orWhere('slug', $request->cooperative_id)
                ->firstOrFail()
                ->subscriptions()
                ->findOrfail($id);
        } else {
            $subscription = Auth::user()->subscriptions()->findOrfail($id);
        }

        return (new SubscriptionResource($subscription))
            ->additional([
                'message' => HttpStatus::message(HttpStatus::OK),
                'status' => 'success',
                'response_code' => HttpStatus::OK,
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $subscription = Subscription::find($id);

        if (! $subscription) {
            return $this->responseBuilder([
                'message' => 'You are not subscribed to this plan.',
                'status' => 'error',
                'response_code' => HttpStatus::NOT_FOUND,
            ]);
        }

        if ($subscription->cooperative || $subscription->status === 'closed1') {
            $msg = $subscription->cooperative
                ? 'You cannot terminate an active subscription to a cooperative plan.'
                : 'You already terminated this subscription.';
            return $this->responseBuilder([
                'message' => $msg,
                'status' => 'error',
                'response_code' => HttpStatus::UNPROCESSABLE_ENTITY,
            ]);
        } else {
            $this->authorize('be-owner', [$subscription->user_id]);
        }

        // Check if this is a withdrawal request
        if ($subscription->paid_days > 0) {
            if ($subscription->status === 'closed') {
                return $this->responseBuilder([
                    'message' => 'You already closed this subscription.',
                    'status' => 'error',
                    'response_code' => HttpStatus::UNPROCESSABLE_ENTITY,
                ]);
            } elseif (config('settings.withdraw_to') === 'wallet') {
                $subscription->status = 'closed';
                $subscription->save();
                if ($subscription->saved_amount > 0) {
                    $subscription->user->wallet()->firstOrNew()->topup(
                        'Refunds',
                        $subscription->saved_amount,
                        __('Refunds for :0.', [$subscription->plan->title])
                    );
                    $subscription->user->notify(new SubStatus($subscription, 'closed'));
                }
                $message = __('Your subscription to the :0 has been terminated, :1.', [
                    $subscription->plan->title,
                    'your savings will be withdrawn to your wallet.'
                ]);
            } else {
                $subscription->status = 'withdraw';
                $subscription->save();
                $message = __('You have successfully terminated your saving for the :0, your withdrawal request has been logged and will be proccessed along with the next batch.', [$subscription->plan->title]);
            }
        } else {
            $subscription->delete();
            $message = __('Your subscription to the :0 has been terminated.', [$subscription->plan->title]);
        }

        return (new SubscriptionResource($subscription))
            ->additional([
                'message' => $message,
                'status' => 'success',
                'response_code' => HttpStatus::ACCEPTED,
            ])->response()->setStatusCode(HttpStatus::ACCEPTED);
    }
}
