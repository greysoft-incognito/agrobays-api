<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
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
            "id" => $this->id,
            "user_id" => $this->user_id,
            "plan_id" => $this->plan_id,
            "food_bag_id" => $this->food_bag_id,
            "paid_days" => $this->paid_days,
            "days_left" => $this->days_left,
            "total_saved" => $this->total_saved,
            "total_left" => $this->total_left,
            "saved_amount" => $this->saved_amount,
            "left_amount" => $this->left_amount,
            "status" => $this->status,
            "fees_split" => $this->fees_split,
            "fees_paid" => $this->fees_paid,
            "fees_left" => ($this->bag?->fees ??0) - $this->fees_paid,
            "items" => new FoodCollection($this->items),
            "bag" => new FoodBagResource($this->bag),
            "plan" => new PlanResource($this->plan),
            "updated_at" => $this->updated_at,
            "created_at" => $this->created_at,
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