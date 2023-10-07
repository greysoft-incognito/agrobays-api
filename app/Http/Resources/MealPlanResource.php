<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MealPlanResource extends JsonResource
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
            'slug' => $this->slug,
            'image' => $this->image,
            'image_url' => $this->images['image'],
            'category' => $this->category,
            'description' => $this->description,
            'calories' => $this->calories,
            'protein' => $this->protein,
            'carbohydrates' => $this->carbohydrates,
            'fat' => $this->fat,
            'favorite_count' => $this->favoriters()->count(),
            'favorited' => $this->hasBeenFavoritedBy($request->user()),
            'days' => $this->timetable()->forUser($request->user())->pluck('date'),
            'date' => $this->whenNotNull($this->date),
            'saved' => $this->is_saved,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function with($request)
    {
        \App\Services\AppInfo::api();
    }
}
