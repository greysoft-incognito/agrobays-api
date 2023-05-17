<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioSmsMessage;

class AutoSavingsMade extends Notification implements ShouldQueue
{
    use Queueable;

    public $sub;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($sub)
    {
        $this->sub = $sub;
        $this->afterCommit();
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
            'message_line1' => __('Your payment method has been automatically charged :amount for :plan :interval savings.', [
                'amount' => money($this->sub->lastSaving->amount),
                'plan' => $this->sub->plan->title,
                'interval' => $this->sub->interval,
            ]),
            'close_greeting' => 'Regards, <br/>'.config('settings.site_name'),
        ];

        return (new MailMessage)->view(
            ['email', 'email-plain'],
            $message
        )
            ->subject(__(':0 Automatic Savings Program', [config('settings.site_name')]));
    }

    /**
     * Get the sms representation of the notification.
     *
     * @param  mixed  $n    notifiable
     * @return \NotificationChannels\Twilio\TwilioSmsMessage
     */
    public function toTwilio($n)
    {
        $message = __('Your payment method has been automatically charged :amount for :plan :interval savings.', [
            'amount' => money($this->sub->lastSaving->amount),
            'plan' => $this->sub->plan->title,
            'interval' => $this->sub->interval,
        ]);

        $message = __('Hi :0, :1', [$n->firstname, $message]);

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
            'title' => 'Automatic Savings Program',
            'message' => __('Your payment method has been automatically charged :amount for :plan :interval savings.', [
                'amount' => money($this->sub->lastSaving->amount),
                'plan' => $this->sub->plan->title,
                'interval' => $this->sub->interval,
            ]),
        ];
    }
}
