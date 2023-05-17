<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $isAdmin = auth()->user()?->role === 'admin';
        $canShow = (bool) str($request->route()->getName())->contains(['show', 'store']) || $request->can_show;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'thread_id' => $this->thread_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'title' => $this->title,
            'type' => $this->type,
            'image_url' => $this->image_url,
            'origin' => $this->origin,
            'message' => $this->message,
            'priority' => $this->priority,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'replies' => $this->when(
                ! $isAdmin || $canShow,
                new FeedbackCollection($this->replies()->limit($request->input('limit', 15))->get())
            ),
            'issue_url' => $this->when($isAdmin && $canShow, $this->issue_url),
            'thread' => $this->replies()->count(),
            'user' => $this->whenLoaded('user', new UserSlimResource($this->user)),
        ];
    }
}
