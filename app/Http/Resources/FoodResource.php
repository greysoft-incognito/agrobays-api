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
        // Remove this line and the functionality it supports
        // (Added for backwards compatibility | food.image in app should be replaced by food.image_url)
        $isAdmin = str($request->fullUrl())->contains('admin');
        // ================================================= //

        $quantity = $this->whenNotNull($this->quantity, $this->pivot?->quantity ?? 0);
        $without = str($request->without)->remove(' ')->explode(',');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'unit' => $this->when($request->editing, $this->unit),
            'price' => $this->price ?? 0,
            'type' => str(get_class($this->resource))->afterLast('\\'),
            'image' => $this->when($request->version < 1 || !$isAdmin, $this->image_url),
            'weight' => $this->when($request->editing, $this->weight, ($this->weight ?? 0) . ($this->unit ?? 'kg')),
            'quantity' => $quantity,
            'image_url' => $this->image_url,
            'available' => $this->available,
            'price_total' => round((float) ($this->price ?? 0) * ($quantity ? $quantity : 1), 2),
            'description' => $this->description,
            'responsive_images' => $this->when(
                !$without->contains('responsive_images'),
                $this->responsive_images['image'] ?? new \stdClass()
            ),
            'foodbags' => new FoodBagCollection($this->whenLoaded('foodbags')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function with($request)
    {
        return \App\Services\AppInfo::api();
    }
}
