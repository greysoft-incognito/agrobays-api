<?php

namespace App\Providers;

use App\Events\PhoneVerified;
use App\Listeners\BroadcastNotification;
use App\Listeners\DeliverableNotificationSent;
use App\Listeners\SendEmailVerificationNotification;
use App\Listeners\SendPhoneVerificationNotification;
use App\Listeners\SendVerifiedEmailNotification;
use App\Listeners\SendVerifiedPhoneNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendPhoneVerificationNotification::class,
            SendEmailVerificationNotification::class,
        ],
        Verified::class => [
            SendVerifiedEmailNotification::class,
        ],
        PhoneVerified::class => [
            SendVerifiedPhoneNotification::class,
        ],
        NotificationSending::class => [
            BroadcastNotification::class,
            DeliverableNotificationSent::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
