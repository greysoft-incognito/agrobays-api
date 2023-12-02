<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FruitbayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $with = str($request->with)->remove(' ')->explode(',');
        $without = str($request->without)->remove(' ')->explode(',');
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'bag' => $this->bag,
            'name' => $this->name,
            'fees' => $this->fees,
            'unit' => $this->unit,
            'price' => $this->price,
            'type' => str(get_class($this->resource))->afterLast('\\'),
            'weight' => $this->when($with->contains('weight'), fn () => $this->weight . $this->unit, $this->weight),
            'quantity' => 0,
            'image_url' => $this->media_file,
            'responsive_images' => $this->when(
                !$without->contains('responsive_images'),
                fn () => $this->responsive_images['image'] ?? new \stdClass()
            ),
            'no_fees' => $this->no_fees,
            'available' => $this->available,
            'description' => $this->description,
            'fruit_bay_category_id' => $this->fruit_bay_category_id,
            'category' => $this->when(
                !$without->contains('category'),
                fn () => $this->whenLoaded('category', new FruitbayCategoryResource($this->category))
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function with($request)
    {
        return \App\Services\AppInfo::api();
    }
}
