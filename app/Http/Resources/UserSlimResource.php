<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserSlimResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'username' => $this->username,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'fullname' => $this->fullname,
            'avatar' => $this->avatar,
            'image_url' => $this->image_url,
            'wallet_balance' => $this->wallet_balance,
            'bank' => $this->bank,
            // "image" => $this->image,
            'gender' => $this->gender,
            'nextofkin' => $this->nextofkin,
            'nextofkin_phone' => $this->nextofkin_phone,
            'nextofkin_relationship' => $this->nextofkin_relationship,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'address' => $this->address,
            'role' => $this->role,
            'permissions' => $this->permissions,
            'email_verified_at' => $this->email_verified_at,
            'phone_verified_at' => $this->phone_verified_at,
            'last_seen' => $this->last_seen ?? $this->created_at,
            'referral_code' => $this->referral_code,
            'referrer_id' => $this->referrer_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
