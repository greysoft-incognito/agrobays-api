<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ModelMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $permissions = $this->model->permissions ?? $this->abilities;

        $isAdmin = $this->abilities?->count() > 0 || auth()->user()->role == 'admin';

        return [
            'id' => $this->id,
            'email' => $this->user->email,
            'phone' => $this->when($isAdmin, $this->user->phone),
            'address' => $this->user->address,
            'username' => $this->user->username,
            'fullname' => $this->user->fullname,
            'admin' => $isAdmin,
            'creator' => $this->model->user->id == $this->user->id,
            'avatar' => $this->user->image_url,
            'pending' => $this->pending,
            'user_id' => $this->user->id,
            'abilities' => $this->abilities?->contains('all') ? $permissions : $this->abilities,
            'created_at' => $this->user->created_at,
        ];
    }
}
