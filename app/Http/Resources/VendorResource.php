<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
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

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'blocked' => $this->blocked,
            'id_type' => $this->id_type,
            'username' => $this->username,
            'verified' => $this->verified,
            'business_name' => $this->business_name,
            'business_email' => $this->business_email,
            'business_phone' => $this->business_phone,
            'business_city' => $this->business_city,
            'business_state' => $this->businessstatee,
            'business_country' => $this->businesscountrye,
            'business_address' => $this->business_address,
            'verification_level' => $this->verification_level,
            'image_url' => $this->files['image'],
            'id_image_url' => $this->files['id_image'],
            'verification_data' => $this->when($with->contains('verification_data'), $this->verification_data),
            'user' => $this->when(
                $with->contains('user_full') && $request->user()->id == $this->user_id,
                fn () => new UserResource($this->user),
                $this->when(
                    $with->contains('user'),
                    fn () => new UserBasicDataResource($this->user)
                )
            ),
        ];
    }
}
