<?php

namespace App\Jobs;

use App\Models\DeliverableNotification;
use App\Models\User;
use App\Notifications\DeliverableNotification as NotificationsDeliverableNotification;
use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeliverableNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public DeliverableNotification $deliverable
    ) {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->deliverable->recipients->each(function (User $user) {
            $user->notify(new NotificationsDeliverableNotification(
                $this->deliverable
            ));
        });
    }
}
