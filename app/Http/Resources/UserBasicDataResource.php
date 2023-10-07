<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserBasicDataResource extends JsonResource
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
            'username' => $this->username,
            'lastname' => $this->lastname,
            'firstname' => $this->firstname,
            'fullname' => $this->fullname,
            'avatar' => $this->avatar,
            'image_url' => $this->image_url,
            'gender' => $this->gender,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'last_seen' => $this->last_seen,
            'referral_code' => $this->referral_code,
            'permissions' => $this->when($request->withPermissions, $this->permissions),
            'dispatches' => $this->when($request->role === 'dispatch', $this->dispatches),
        ];
    }

    public function with($request)
    {
        \App\Services\AppInfo::api();
    }
}
