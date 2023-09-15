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
        $isAdmin = str($request->fullUrl())->contains('admin');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price ?? 0,
            'price_total' => (float) ($this->price ?? 0) * ($this->pivot?->quantity ?? 0),
            'unit' => $this->when($request->editing, $this->unit),
            'weight' => $this->when(
                $request->editing,
                $this->weight,
                ($this->weight ?? 0) . ($this->unit ?? 'kg')
            ),
            'quantity' => $this->pivot?->quantity ?? 0,
            'image' => $this->when($request->version < 1 || !$isAdmin, $this->image_url),
            'image_url' => $this->image_url,
            'responsive_images' => $this->responsive_images['image'] ?? new \stdClass(),
            'foodbags' => new FoodBagCollection($this->whenLoaded('foodbags')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
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