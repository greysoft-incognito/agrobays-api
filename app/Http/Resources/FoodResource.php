<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FoodResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price ?? 0,
            'unit' => $this->when($request->editing, $this->unit),
            'weight' => $this->when(
                $request->editing,
                $this->weight,
                ($this->weight ?? 0) . ($this->weight_unit ?? 'kg')
            ),
            'quantity' => $this->pivot?->quantity ?? 0,
            'image' => $this->image_url,
            'foodbags' => new FoodBagCollection($this->whenLoaded('foodbags')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function with($request)
    {
        return ['api' => [
            'name' => env('APP_NAME', 'Agrobays API'),
            'version' => env('APP_VERSION', '1.0.6-beta'),
            'author' => 'Greysoft Limited',
            'updated' => now(),
        ]];
    }
}