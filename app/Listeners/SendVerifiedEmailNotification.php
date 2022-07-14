<?php

namespace App\Listeners;

use App\Notifications\SendVerified;

class SendVerifiedEmailNotification
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
        $event->user->notify(new SendVerified);
    }
}
