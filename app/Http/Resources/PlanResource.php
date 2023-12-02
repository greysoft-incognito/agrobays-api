<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
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
        $with = is_array($request->with) ? $request->with : explode(',', $request->with);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'title' => $this->title,
            'amount' => $this->amount,
            'status' => $this->status,
            'duration' => $this->duration,
            'image_url' => $this->image_url,
            'description' => $this->description,
            'customizable' => $this->customizable,
            $this->mergeWhen($v >= 2, [
                'foodbags' => $this->when(in_array('foodbags', $with), function () {
                    return new FoodBagCollection($this->food_bag);
                }),
            ]),
            'food_bags' => $this->when($v < 2, function () {
                return new FoodBagCollection($this->food_bag);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function with($request)
    {
        return \App\Services\AppInfo::api();
    }
}
