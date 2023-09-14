<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliverableNotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $with = is_array($request->with) ? $request->with : explode(',', $request->with);
        $count_recipients = $this->recipient_ids?->count() ?? 0;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'sent' => $this->sent,
            'count_sent' => $this->count_sent,
            'count_failed' => $this->count_failed,
            'count_pending' => $this->count_pending,
            'count_recipients' => $count_recipients,
            'subject' => $this->subject,
            'message' => $this->message,
            'message_clean' => strip_tags($this->message),
            'recipient_ids' => $this->recipient_ids,
            'user' => $this->when(in_array('user', $with), fn () => new UserBasicDataResource($this->user)),
            'recipients' => $this->when(in_array('recipients', $with), fn () => new UserBasicDataCollection($this->recipients)),
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
