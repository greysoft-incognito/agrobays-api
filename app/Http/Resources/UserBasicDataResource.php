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
        $user = $request->user();
        $prived = in_array($request->role ?? $user?->role, ['admin', 'manager']) || $user?->id === $this->id;
        $dispatch = in_array('dispatch', [$user?->role, $request->role]);
        $with = collect(is_array($request->with) ? $request->with : str($request->with)->remove(' ')->explode(','));

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
            'dispatches' => $this->when($dispatch, $this->dispatches),
            'wallet_balance' => $this->when($prived, $this->wallet_balance),
            'verification_data' => $this->when($with->contains('verification_data'), $this->verification_data),
            'verification_level' => $this->verification_level,
            $this->mergeWhen($this->pen_code && $prived, function () {
                return [
                    'pen_code' => $this->pen_code,
                    'pen_code_u' => $this->pen_code_u,
                ];
            }),
        ];
    }

    public function with($request)
    {
        return \App\Services\AppInfo::api();
    }
}
