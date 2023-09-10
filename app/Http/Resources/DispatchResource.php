<?php

namespace App\Http\Resources;

use App\Models\FoodBag;
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
            'user' => $this->user,
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
            $this->mergeWhen($v > 1, function () {
                $data = [
                    'item' => $this->dispatchable instanceof Subscription
                        ? new SubscriptionResource($this->dispatchable)
                        : ($this->dispatchable instanceof Subscription
                            ? new OrderResource($this->dispatchable)
                            : $this->dispatchable
                     ),
                    'user' => new UserBasicDataResource($this->dispatchable->user),
                    'handler' => new UserBasicDataResource($this->user),
                ];

                return $data;
            }),
        ];
    }

    public function with($request)
    {
        return ['api' => [
            'name' => env('APP_NAME', 'Agrobays API'),
            'version' => config('api.api_version'),
            'app_version' => config('api.app_version'),
            'author' => 'Greysoft Limited',
            'updated' => now(),
        ]];
    }
}
