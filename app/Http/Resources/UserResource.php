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
        $with = collect(is_array($request->with) ? $request->with : str($request->with)->remove(' ')->explode(','));

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
            'city' => is_scalar($this->city) ? $this->city : $this->city['name'] ?? '',
            'state' => is_scalar($this->state) ? $this->state : $this->state['name'] ?? '',
            'country' => is_scalar($this->country) ? $this->country : $this->country['name'] ?? '',
            'address' => $this->address,
            // "last_attempt" => $this->last_attempt,
            // "email_verify_code" => $this->email_verify_code,
            // "phone_verify_code" => $this->phone_verify_code,
            'role' => $this->role,
            'permissions' => $this->permissions,
            'stats' => $this->when($with->contains('stats'), fn() => $this->stats),
            'verified' => $this->verified,
            'subscription' => new SubscriptionResource($this->subscription),
            'email_verified_at' => $this->email_verified_at,
            'phone_verified_at' => $this->phone_verified_at,
            'verification_data' => $this->when($with->contains('verification_data'), $this->verification_data),
            'verification_level' => $this->verification_level,
            'payment_method' => $this->when(isset($this->data['payment_method']), [
                'type' => $this->data['payment_method']['type'] ?? null,
                'card_type' => $this->data['payment_method']['card_type'] ?? null,
                'last4' => $this->data['payment_method']['last4'] ?? null,
                'exp_month' => $this->data['payment_method']['exp_month'] ?? null,
                'exp_year' => $this->data['payment_method']['exp_year'] ?? null,
                'auth_date' => $this->data['payment_method']['auth_date'] ?? null,
            ]),
            'vendor_id' => $this->vendor->id,
            $this->mergeWhen($this->pen_code, function () {
                return [
                    'pen_code' => $this->pen_code,
                    'pen_code_u' => $this->pen_code_u,
                ];
            }),
            'last_seen' => $this->last_seen ?? $this->created_at,
            'referral_code' => $this->referral_code,
            'referrer_code' => $this->referrer?->referral_code,
            'referrer_id' => $this->referrer_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function with($request)
    {
        return \App\Services\AppInfo::api();
    }
}