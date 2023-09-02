<?php

namespace App\Http\Controllers\Admin\Cooperative;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\MembersFoodbagCollection;
use App\Http\Resources\MembersFoodbagResource;
use App\Http\Resources\SubscriptionCollection;
use App\Http\Resources\SubscriptionResource;
use App\Models\Cooperative;
use Carbon\CarbonImmutable as Carbon;
use Illuminate\Http\Request;

class CooperativeSubscriptionController extends Controller
{
    /**
     * List all the authenticated user's subscriptions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cooperative  $cooperative
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Cooperative $cooperative)
    {
        $status = $request->status;
        $limit = $request->limit;

        $subs = $cooperative->subscriptions()->orderBy('id', 'DESC');

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
            ? ($last->created_at->format('Y/m/d').'-'.$first->created_at->format('Y/m/d'))
            : '';

        return (new SubscriptionCollection($subscriptions))->additional([
            'message' => $msg,
            'status' => $subscriptions->isEmpty() ? 'info' : 'success',
            'response_code' => HttpStatus::OK,
            'period' => $p ? urldecode($p) : $_period,
        ]);
    }

    /**
     * List all the authenticated user's subscriptions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cooperative  $cooperative
     * @param  int  $subscription_id
     * @return \Illuminate\Http\Response
     */
    public function subscription(Request $request, Cooperative $cooperative, $subscription_id)
    {
        $subscription = $cooperative->subscriptions()->findOrfail($subscription_id);

        return (new SubscriptionResource($subscription))
            ->additional([
                'message' => 'OK',
                'status' => 'error',
                'response_code' => HttpStatus::OK,
            ]);
    }

    /**
     * List all the members of the cooperative who are owners of the subscription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cooperative  $cooperative
     * @param  int  $subscription_id
     * @return \Illuminate\Http\Response
     */
    public function owners(Request $request, Cooperative $cooperative, $subscription_id)
    {
        $query = $cooperative->foodbags()->where('subscription_id', $subscription_id);

        if ($request->has('status')) {
            $query->where('approved', $request->status == 'approved');
        }

        if ($request->has('paginate')) {
            $owners = $query->paginate($request->paginate);
        } else {
            if ($request->has('limit') && $request->limit > 0) {
                $query->limit($request->limit);
            }
            $owners = $query->get();
        }

        return (new MembersFoodbagCollection($owners))
            ->additional([
                'message' => 'OK',
                'status' => 'error',
                'response_code' => HttpStatus::OK,
            ]);
    }

    /**
     * List all the authenticated user's subscriptions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cooperative  $cooperative
     * @param  int  $subscription_id
     * @param  int  $foodbag_id
     * @return \Illuminate\Http\Response
     */
    public function approveFoodbag(Request $request, Cooperative $cooperative, $subscription_id, $foodbag_id)
    {
        if ($request->has('items')) {
            $request->validate([
                'items' => 'required|array',
                'items.*.id' => 'required|integer|exists:cooperative_members_foodbags,id',
                'items.*.approved' => 'required|boolean',
            ], [
                'items.required' => 'Please select at least one item to update status.',
                'items.*.id.required' => 'Selected item #:position is missing the id parameter.',
                'items.*.id.exists' => 'Selected item #:position does not exist.',
                'items.*.approved.required' => 'Selected item #:position is missing the approved parameter.',
                'items.*.approved.boolean' => 'Selected item #:position\'s approved parameter must be true or false.',
            ]);
            collect($request->items)->each(function ($item) use ($cooperative) {
                $foodbag = $cooperative->foodbags()->find($item['id']);
                if ($foodbag && (bool) $item['approved'] != $foodbag->approved) {
                    $foodbag->approved = (bool) $item['approved'];
                    $foodbag->save();
                }
            });
            $foodbag = $cooperative->foodbags()->find($request->items[0]['id']);

            $message = 'The selected foodbags status have been updated for the ":0" plan.';
        } else {
            $request->validate([
                'approved' => 'nullable|boolean',
            ], [
                'approved.boolean' => 'The approved field must be true or false.',
            ]);

            $foodbag = $cooperative->foodbags()->findOrfail($foodbag_id);

            if ($request->boolean('approved') == $foodbag->approved) {
                $message = ':0\'s foodbag has already been :2 for the ":1" plan.';
            } else {
                $message = ':0\'s foodbag has been :2 for the ":1" plan.';

                $foodbag->approved = $request->boolean('approved');
                $foodbag->save();
            }
        }

        if ($request->has('items')) {
            return (new MembersFoodbagCollection(
                $cooperative->foodbags()->whereIn('id', collect($request->items)->pluck('id'))->get()
            ))
                ->additional([
                    'message' => __($message, [
                        $foodbag->subscription->plan->title ?? null,
                    ]),
                    'status' => 'success',
                    'response_code' => HttpStatus::OK,
                ]);
        }

        return (new MembersFoodbagResource($foodbag))
            ->additional([
                'message' => __($message, [
                    $foodbag->user->fullname ?? null,
                    $foodbag->subscription->plan->title ?? null,
                    $request->approved ? 'approved' : 'rejected',
                ]),
                'status' => 'success',
                'response_code' => HttpStatus::OK,
            ]);
    }
}
