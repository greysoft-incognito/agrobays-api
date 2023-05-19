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
            'name'=> $this->name,
            'slug'=> $this->slug,
            'image'=> $this->image,
            'category'=> $this->category,
            'description'=> $this->description,
            'calories'=> $this->calories,
            'protein'=> $this->protein,
            'carbohydrates'=> $this->carbohydrates,
            'fat'=> $this->fat,
            'favorite_count'=> $this->favorite_to_users_count,
            'favorited'=> $this->hasBeenFavoritedBy($request->user()),
            'pivot'=> $this->whenPivotLoaded('meal_timetables', function () {
                return [
                    'date'=> $this->pivot->date,
                    'time'=> $this->pivot->time,
                ];
            }),
            'created_at'=> $this->created_at,
            'updated_at'=> $this->updated_at,
        ];
    }
}