<?php

namespace App\Listeners;

use App\Events\SendingNotification;

class BroadcastNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        // $event->channel
        // $event->notifiable
        // $event->notification
        if ($event->channel == 'database' && ! $event->notification->deliverable) {
            $data = $event->notification->toArray(true);
            $notification = [
                'id' => $event->notification->id ?? null,
                'data' => $data,
                'message' => $data['message'] ?? '',
                'type' => $event->notification->type ?? $data['type'] ?? 'system_notification',
                'read_at' => $event->notification->read_at ?? null,
                'created_at' => $event->notification->created_at ?? now(),
            ];
            broadcast(new SendingNotification($notification, $event->notifiable))->toOthers();
        }
    }
}
