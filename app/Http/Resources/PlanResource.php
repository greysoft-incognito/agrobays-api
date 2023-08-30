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

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'duration' => $this->duration,
            'icon' => $this->icon,
            'amount' => $this->amount,
            'status' => $this->status,
            'image_url' => $this->image_url,
            $this->mergeWhen($v >= 2, [
                'food_bags' => $this->when($request->with_foodbags, function () {
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
        return ['api' => [
            'name' => env('APP_NAME', 'Agrobays API'),
            'version' => config('api.api_version'),
            'app_version' => config('api.app_version'),
            'author' => 'Greysoft Limited',
            'updated' => now(),
        ]];
    }
}
