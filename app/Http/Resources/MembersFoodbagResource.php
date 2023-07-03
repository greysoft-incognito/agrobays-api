<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MembersFoodbagResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'cooperative_id' => $this->cooperative_id,
            'subscription_id' => $this->subscription_id,
            'approved' => $this->approved,
            'user' => new UserBasicDataResource($this->user),
            'foodbag' => new FoodBagResource($this->foodbag),
        ];
    }
}
