<?php

namespace App\Http\Resources;

use App\Models\Subscription;
use Illuminate\Http\Resources\Json\JsonResource;

class DispatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $v = $request->version;

        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'reference' => $this->reference,
            'status' => $this->status,
            'type' => $this->type,
            'item_type' => $this->item_type,
            'last_location' => $this->last_location,
            $this->mergeWhen($v < 2, function () {
                return [
                    'user_id' => $this->user_id,
                    'dispatchable' => $this->dispatchable,
                    'dispatchable_id' => $this->dispatchable_id,
                    'dispatchable_type' => $this->dispatchable_type,
                    'user' => new UserSlimResource($this->user),
                    'owner' => new UserSlimResource($this->dispatchable->user),
                ];
            }),
            $this->mergeWhen($v > 1, function () {
                $data = [
                    'item' => $this->dispatchable instanceof Subscription
                        ? new SubscriptionResource($this->dispatchable)
                        : ($this->dispatchable instanceof Subscription
                            ? new OrderResource($this->dispatchable)
                            : $this->dispatchable
                        ),
                    'user' => new UserSlimResource($this->dispatchable->user),
                    'handler' => new UserSlimResource($this->user),
                ];

                return $data;
            }),
        ];
    }

    public function with($request)
    {
        return \App\Services\AppInfo::api();
    }
}
