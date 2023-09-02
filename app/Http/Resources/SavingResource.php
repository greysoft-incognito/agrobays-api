<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SavingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $with = is_array($request->with) ? $request->with : explode(',', $request->with);
        if (!$request->subscription || $request->subscription == 'all' || $request->boolean('subscription')) {
            $with[] = 'subscription';
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'cooperative_id' => $this->whenNotNull($this->subscription?->cooperative_id),
            'subscription_id' => $this->subscription_id,
            'tax' => $this->tax,
            'due' => $this->due,
            'fees' => $this->transaction?->fees ?? 0,
            'amount' => $this->amount,
            'total' => $this->amount,
            'days' => $this->days,
            'qty' => $this->days,
            'status' => $this->status,
            'payment_ref' => $this->payment_ref,
            'user' => $this->when(in_array('user', $with), new UserSlimResource($this->user)),
            'through' => str($this->subscription->paid_days ?? 0)
                ->append('/')
                ->append(($this->subscription->days_left ?? 0) + ($this->subscription->paid_days ?? 0)),
            'date' => $this->created_at->format('Y-m-d H:i'),
            'title' => $this->subscription?->plan?->title,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'items' => [
                [
                    'id' => $this->id,
                    'qty' => $this->days,
                    'name' => $this->subscription?->plan?->title,
                    'price' => $this->amount,
                    'total' => $this->amount,
                ],
            ],
            'transaction' => $this->transaction,
            'subscription' => $this->when(in_array('subscription', $with), $this->subscription),
        ];
    }
}