<?php

namespace App\Listeners;

use Illuminate\Notifications\Events\NotificationSending;

class DeliverableNotificationSent
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
     * @param  \Illuminate\Notifications\Events\NotificationSending  $event
     * @return void
     */
    public function handle(NotificationSending $event)
    {
        if (isset($event->notification->deliverable)) {
            $event->notification->deliverable->count_sent++;
            $event->notification->deliverable->saveQuietly();
        }
    }
}