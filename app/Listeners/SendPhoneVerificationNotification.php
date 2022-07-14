<?php

namespace App\Listeners;

class SendPhoneVerificationNotification
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
        if (config('settings.verify_phone', false) && ! $event->user->hasVerifiedPhone()) {
            $event->user->sendPhoneVerificationNotification();
        }
    }
}
