<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CooperativeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /**
         * @var \App\Models\User $user
         */
        $user = auth()->user();
        $is_admin = in_array($user?->role, ['admin', 'manager']);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email ?? '',
            'phone' => $this->phone ?? '',
            'classification' => $this->classification ?? '',
            'website' => $this->website ?? '',
            'address' => $this->address ?? '',
            'count_members' => $this->members()->isAccepted()->count(),
            'count_pending_members' => $this->members()->isAccepted(false)->count(),
            'count_member_requests' => $this->members()->isRequesting()->count(),
            'count_plans' => $this->subscriptions()->currentStatus('!closed')->count(),
            'location' => collect([
                $this->address,
                $this->lga,
                $this->state,
                'Nigeria',
            ])->filter()->implode(', '),
            'lga' => $this->lga,
            'state' => $this->state,
            'about' => $this->about ?? '',
            'avatar' => $this->files['image'],
            'cover' => $this->files['cover'],
            'by_you' => $user->id ? $this->user_id === auth()->id() : false,
            'meta' => $this->meta,
            'publishing' => $this->when($is_admin, $this->publishing),
            'wallet_bal' => $this->when($is_admin, $this->wallet_balance),
            'user' => $this->when($is_admin, new UserBasicDataResource($this->user)),
            'abilities' => $this->abilities,
            'verified' => $this->verified,
            'settings' => $this->settings,
            'is_active' => $this->is_active,
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
