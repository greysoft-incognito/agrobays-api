<?php

namespace App\Listeners;

use Illuminate\Contracts\Auth\MustVerifyEmail;

class SendEmailVerificationNotification
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
        if ($event->user instanceof MustVerifyEmail && ! $event->user->hasVerifiedEmail() && config('settings.verify_email', false)) {
            $event->user->sendEmailVerificationNotification();
        }
    }
}
