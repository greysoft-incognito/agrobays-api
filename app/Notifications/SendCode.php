<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendCode extends Notification //implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($token = null, $type = 'reset')
    {
        $this->type = $type;
        $this->token = $token;
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
        return ['mail'];
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
            'reset' => [
                'name' => $notifiable->firstname,
                'cta' => ['code' => $this->token],
                'message_line1' => 'You are receiving this email because we received a password reset request for your account.',
                'message_line2' => 'This password reset code will expire in 30 minutes.',
                'message_line3' => 'If you did not request a password reset, no further action is required.',
                'close_greeting' => __('Regards, <br/>:0', [config('settings.site_name')]),
                'message_help' => 'Please use the code above to recover your account ',
            ],
            'verify' => [
                'name' => $notifiable->firstname,
                'cta' => ['code' => $this->token],
                'message_line1' => __('You are receiving this email because you created an account on <b>:0</b> and we need to verify that you own this email addrress. <br /> use the code below to verify your email address.', [config('settings.site_name')]),
                'message_line2' => 'This verification code will expire in 30 minutes.',
                'message_line3' => 'If you do not recognize this activity, no further action is required as the associated account will be deleted in few days if left unverified.',
                'close_greeting' => __('Regards, <br/>', [config('settings.site_name')]),
                'message_help' => 'Please use the code above to verify your account ',
            ]
        ];
        return (new MailMessage)->view(
            ['email', 'email-plain'], $message[$this->type]
        )
        ->subject(__($this->type === 'reset' ? 'Reset your :0 password.' : 'Verify your account at :0', [config('settings.site_name')]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
