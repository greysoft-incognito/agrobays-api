<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $with = collect(is_array($request->with) ? $request->with : explode(',', $request->with));

        /** @var \App\Models\CooperativeMembersFoodbag */
        $cooperative_foodbag = $this->cooperative
            ? $this->cooperative->foodbags()->whereSubscriptionId($this->id)->whereUserId($request->user()->id)->first()
            : null;

        /** @var \App\Models\FoodBag */
        $bag = $this->cooperative
            ? $cooperative_foodbag?->foodbag ?? $this->bag
            : $this->bag;

        return [
            'id' => $this->id,
            'fees' => $this->fees_paid,
            'status' => $this->status,
            'user_id' => $this->user_id,
            'plan_id' => $this->plan_id,
            'food_bag_id' => $this->food_bag_id,
            'food_bag_approved' => (bool) $cooperative_foodbag?->approved ?? true,
            $this->mergeWhen($this->cooperative, function () {
                return [
                    'count_owners' => $this->cooperative->foodbags()->whereSubscriptionId($this->id)->count(),
                    'count_approved_owners' => $this->cooperative->foodbags()
                                                    ->isApproved()->whereSubscriptionId($this->id)->count(),
                ];
            }),
            'paid_days' => $this->paid_days,
            'days_left' => $this->days_left,
            'total_left' => $this->total_left,
            'total_saved' => $this->total_saved,
            'left_amount' => $this->left_amount,
            'amount' => $this->plan->amount,
            'saved_amount' => $this->saved_amount,
            'next_amount' => $this->next_amount,
            'fees_left' => ($this->bag?->fees ?? 0) - $this->fees_paid,
            'fees_paid' => $this->fees_paid,
            'fees_split' => $this->fees_split,
            'custom_foodbag' => $this->custom_foodbag,
            'delivery_method' => $this->delivery_method,
            'items' => $request->subscription && !$with->contains('food_items')
                ? new SavingCollection($this->savings)
                : new FoodCollection($this->items),
            'bag' => new FoodBagResource($bag),
            'plan' => new PlanResource($this->plan),
            'user' => $this->when($with->contains('user'), function () {
                return new UserBasicDataResource($this->user);
            }),
            'transaction' => [
                'reference' => str($this->plan->title)->camel() . '-' . $this->id . $this->plan_id,
                'amount' => $this->saved_amount,
                'fees' => $this->fees_paid,
                'updated_at' => $this->updated_at,
                'created_at' => $this->created_at,
            ],
            'interval' => $this->interval,
            'next_date' => $this->next_date,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }

    public function with($request)
    {
        return \App\Services\AppInfo::api();
    }
}
