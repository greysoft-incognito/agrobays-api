<?php

namespace App\Http\Resources;

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
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'dispatchable' => $this->dispatchable,
            'dispatchable_id' => $this->dispatchable_id,
            'dispatchable_type' => $this->dispatchable_type,
            'reference' => $this->reference,
            'status' => $this->status,
            'type' => $this->type,
            'item_type' => $this->item_type,
            'last_location' => $this->last_location,
            'user' => $this->user,
            'user_id' => $this->user_id,
            'owner' => $this->dispatchable->user ?? null,
        ];
    }
}