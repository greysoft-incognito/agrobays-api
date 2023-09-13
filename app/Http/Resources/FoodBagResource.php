<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FoodBagResource extends JsonResource
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
            'plan_id' => $this->plan_id,
            'fees' => $this->fees,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image,
            'image_url' => $this->image,
            'price' => $this->price,
            'weight' => $this->weight . ($this->weight_unit ?? 'kg'),
            $this->mergeWhen($v >= 2, [
                'foods' => $this->when(in_array('foods', $with), function () {
                    return new FoodCollection($this->whenLoaded('foods'));
                }),
            ]),
            'foods' => $this->when($v < 2, function () {
                return new FoodCollection($this->whenLoaded('foods'));
            }),
            'plan' => $this->whenLoaded('plan'),
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
