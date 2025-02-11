<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioSmsMessage;

class SubStatus extends Notification //implements ShouldQueue
{
    use Queueable;

    public $item;
    public $action;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($item, $status = 'pending')
    {
        $this->item = $item;
        $this->afterCommit();
        $this->action = [
            'closed' => 'has been closed and your savings have been sent to your provided bank account details.',
            'withdraw' => 'has been changed to withdrawn.',
        ][$status] ?? 'has been changed to ' . str($status)->replace('_', ' ');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $pref = config('settings.prefered_notification_channels', ['mail', 'sms']);

        return in_array('sms', $pref) && in_array('mail', $pref)
            ? ['database', 'mail', TwilioChannel::class]
            : (in_array('sms', $pref)
                ? ['database', TwilioChannel::class]
                : (in_array('mail', $pref)
                    ? ['database', 'mail']
                    : ['database']
                )
            );
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $message = [
            'name' => $notifiable->firstname,
            'message_line1' => __("Your subscription for {$this->item->plan->title} " . $this->action),
            'close_greeting' => 'Regards, <br/>' . config('settings.site_name'),
        ];

        return (new MailMessage())->view(
            ['email', 'email-plain'],
            $message
        )
        ->subject(__(':0 Subscription Updated', [config('settings.site_name')]));
    }

    /**
     * Get the sms representation of the notification.
     *
     * @param  mixed  $n    notifiable
     * @return \NotificationChannels\Twilio\TwilioSmsMessage
     */
    public function toTwilio($n)
    {
        $message = __("Your :0 subscription for {$this->item->plan->title} " . $this->action, [config('settings.site_name')]);

        $message = __('Hi :0, ', [$n->firstname]) . $message;

        return (new TwilioSmsMessage())
            ->content($message);

        return false;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($n)
    {
        return [
            'type' => 'Subscription',
            'title' => 'Subscription Updated',
            'message' => __("Your subscription for {$this->item->plan->title} " . $this->action),
        ];
    }
}