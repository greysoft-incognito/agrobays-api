<?php

namespace App\Listeners;

use App\Notifications\SendVerified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use NotificationChannels\Twilio\TwilioChannel;

class SendVerifiedPhoneNotification
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
        $event->user->notify(new SendVerified, TwilioChannel::class);
    }
}