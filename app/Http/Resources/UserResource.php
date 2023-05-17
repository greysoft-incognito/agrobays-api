<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            // "last_attempt" => $this->last_attempt,
            // "email_verify_code" => $this->email_verify_code,
            // "phone_verify_code" => $this->phone_verify_code,
            'role' => $this->role,
            'permissions' => $this->permissions,
            'subscription' => new SubscriptionResource($this->subscription),
            'email_verified_at' => $this->email_verified_at,
            'phone_verified_at' => $this->phone_verified_at,
            'payment_method' => $this->when(isset($this->data['payment_method']), [
                'type' => $this->data['payment_method']['type'] ?? null,
                'card_type' => $this->data['payment_method']['card_type'] ?? null,
                'last4' => $this->data['payment_method']['last4'] ?? null,
                'exp_month' => $this->data['payment_method']['exp_month'] ?? null,
                'exp_year' => $this->data['payment_method']['exp_year'] ?? null,
                'auth_date' => $this->data['payment_method']['auth_date'] ?? null,
            ]),
            'last_seen' => $this->last_seen ?? $this->created_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}