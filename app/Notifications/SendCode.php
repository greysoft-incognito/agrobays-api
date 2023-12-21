<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class SendCode extends Notification implements ShouldQueue
{
    use Queueable;

    public $type;
    public $token;

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
        $pref = config('settings.prefered_notification_channels', ['mail', 'sms']);
        $channels = in_array('sms', $pref) && in_array('mail', $pref)
            ? ['mail', TwilioChannel::class]
            : (in_array('sms', $pref)
                ? [TwilioChannel::class]
                : ['mail']
            );

        return collect($channels)->filter(fn ($ch) => $this->type !== 'verify-phone' || $ch !== 'mail')->filter(fn ($ch) => $this->type !== 'verify' || $ch !== TwilioChannel::class)->toArray();
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $link = __(':0/reset-password?token=:1', [ env('FRONTEND_LINK'), urlencode(base64_encode($this->token)) ]);

        $message = [
            'reset' => [
                'name' => $notifiable->firstname,
                'cta' => ['code' => $this->token],
                'message_line1' => __(join(' ', [
                    'You are receiving this email because we received',
                    'a password reset request for your account.',
                    '<br />You can click this <a href=":0">link</a> to reset your account',
                    ' or use the code below when requested.',
                ]), [ $link ]),
                'message_line2' => __(join(' ', [
                    'If you\'re unable to click the link above and unable to use the code',
                    'you can copy and paste this link on your browser address bar: <a href=":0">:0</a>',
                    '<br />The password reset token and code expires in :1 minutes.',
                ]), [ $link, config('settings.token_lifespan', 30) ]),
                'message_line3' => 'If you did not request a password reset, no further action is required.',
                'close_greeting' => __('Regards, <br/>:0', [config('settings.site_name')]),
                'message_help' => 'Please use the code above to recover your account ',
            ],
            'verify' => [
                'name' => $notifiable->firstname,
                'cta' => ['code' => $this->token],
                'message_line1' => __(join(' ', [
                        'You are receiving this email because you created an account on <b>:0</b> and',
                        'we need to verify that you own this email addrress. <br /> use the code below',
                        'to verify your email address.'
                ]), [ config('settings.site_name') ]),
                'message_line2' => __(
                    'This verification code will expire in :0 minutes.',
                    [ config('settings.token_lifespan', 30) ]
                ),
                'message_line3' => join(' ', [
                    'If you do not recognize this activity, no further action',
                    'is required as the associated account will be deleted in few days if left unverified.'
                ]),
                'close_greeting' => __('Regards, <br/>', [config('settings.site_name')]),
                'message_help' => 'Please use the code above to verify your account ',
            ],
        ];

        if (isset($message[$this->type])) {
            return (new MailMessage())->view(
                ['email', 'email-plain'],
                $message[$this->type]
            )
            ->subject(__($this->type === 'reset' ? 'Reset your :0 password.' : 'Verify your account at :0', [
                config('settings.site_name')
            ]));
        }
    }

    /**
     * Get the sms representation of the notification.
     *
     * @param  mixed  $n    notifiable
     * @return \NotificationChannels\Twilio\TwilioSmsMessage
     */
    public function toTwilio($n)
    {
        $message = [
            'reset' => __("Use this code {$this->token} to reset your :0 password, It expires in :1 minutes.", [config('settings.site_name'), config('settings.token_lifespan', 30)]),
            'verify-phone' => __("use this code {$this->token} to verify your :0 phone number, It expires in :1 minutes.", [config('settings.site_name'), config('settings.token_lifespan', 30)]),
        ];

        if (isset($message[$this->type])) {
            $message = __('Hi :0, ', [$n->firstname]) . $message[$this->type];

            return (new TwilioSmsMessage())
                ->content($message);
        }
    }
}
