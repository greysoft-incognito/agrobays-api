<?php

namespace App\Http\Resources;

use App\Models\Order;
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
        $with = str($request->with)->remove(' ')->explode(',');
        $without = str($request->without)->remove(' ')->explode(',');

        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'reference' => $this->reference,
            'status' => $this->status,
            'type' => $this->type,
            'item_type' => $this->item_type,
            'code' => $this->code,
            'last_location' => $this->last_location,
            'extra_data' => $this->when($with->contains('extra_data'), $this->extra_data),
            $this->mergeWhen($v < 2, function () {
                return [
                    'user_id' => $this->user_id,
                    'dispatchable' => $this->dispatchable,
                    'dispatchable_id' => $this->dispatchable_id,
                    'dispatchable_type' => $this->dispatchable_type,
                    'user' => new UserBasicDataResource($this->user),
                    'owner' => new UserBasicDataResource($this->dispatchable->user),
                ];
            }),
            $this->mergeWhen($v > 1, function () use ($with, $without) {
                $data = [
                    'item' => $this->when(
                        !$without->contains('item'),
                        fn () => $this->dispatchable instanceof Subscription
                        ? new SubscriptionResource($this->dispatchable)
                        : ($this->dispatchable instanceof Order
                            ? new OrderResource($this->dispatchable)
                            : $this->dispatchable
                        )
                    ),
                    'user' => $this->when(
                        !$without->contains('user'),
                        fn () => new UserBasicDataResource($this->dispatchable->user)
                    ),
                    'vendor' => $this->when(
                        $with->contains('vendor'),
                        fn () => new VendorResource($this->vendor)
                    ),
                    'handler' => $this->whenLoaded('user', fn () => new UserBasicDataResource($this->user)),
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
