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
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'bag' => $this->bag,
            'name' => $this->name,
            'price' => $this->price,
            'image_url' => $this->image_url,
            'description' => $this->description,
            'fruit_bay_category_id' => $this->fruit_bay_category_id,
            'category' => $this->whenLoaded('category', new FruitbayCategoryResource($this->category)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function with($request)
    {
        return ['api' => [
            'name' => env('APP_NAME', 'Agrobays API'),
            'version' => env('API_VERSION', '1.0.6-beta'),
            'author' => 'Greysoft Limited',
            'updated' => now(),
        ]];
    }
}