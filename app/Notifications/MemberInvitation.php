<?php

namespace App\Notifications;

use App\Models\Cooperative;
use App\Models\ModelMember;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class MemberInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    protected $member;

    protected $sender;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(ModelMember $member, User $sender)
    {
        $this->member = $member;
        $this->sender = $sender;
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
        $pref = config('settings.prefered_notification_channels', ['mail']);
        $channels = in_array('sms', $pref) && in_array('mail', $pref)
            ? ['mail', TwilioChannel::class]
            : (in_array('sms', $pref)
                ? [TwilioChannel::class]
                : ['mail']);

        return collect($channels)
            ->merge(['database'])
            ->all();
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
            'message_line1' => __(':0 has invited you to become a member of :1. Please login to respond.', [
                $this->sender->fullname,
                $this->member->model->title ?? $this->member->model->name ?? '-',
            ]),
            'close_greeting' => 'Regards, <br/>'.config('settings.site_name'),
        ];

        return (new MailMessage())->view(
            ['email', 'email-plain'],
            $message
        )->subject(__('New invitation to become a member of :0', [
            $this->member->model->title ?? $this->member->model->name ?? '-',
        ]));
    }

    /**
     * Get the sms representation of the notification.
     *
     * @param  mixed  $notifiable    notifiable
     * @return \NotificationChannels\Twilio\TwilioSmsMessage
     */
    public function toTwilio($notifiable)
    {
        dd($notifiable);
        $message = __(':0 has invited you to become a member of :1. Please login to respond.', [
            $this->sender->fullname,
            $this->member->model->title ?? $this->member->model->name ?? '-',
        ]);

        return (new TwilioSmsMessage())->content($message);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        if (is_bool($notifiable)) {
            return [];
        }

        $bases = [
            Cooperative::class => 'cooperatives',
        ];
        $base = $bases[$this->member->model_type] ?? 'cooperatives';

        $action_url = parse_url(
            route($base.'.members.invitations.status', [$this->member->model_id, ':status']),
            PHP_URL_PATH
        );

        $image_url = parse_url($this->member->model->image_url, PHP_URL_PATH);

        $notification_array = [
            'icon' => 'attach_email',
            'type' => 'member_invitation',
            'image' => $image_url,
            'action' => $action_url,
            'message' => __(':0 is inviting you to become a member of :1.', [
                $this->sender->fullname,
                $this->member->model->title ?? $this->member->model->name ?? '-',
            ]),
            'title' => __('Invitation to become a member of :0', [
                $this->member->model->title ?? $this->member->model->name ?? '-',
            ]),
            'actions' => $notifiable->id == $this->member->user->id
                ? ['accepted' => 'Accept', 'rejected' => 'Reject']
                : [],
            'request' => [
                'id' => $this->member->id,
                'type' => $this->member->model_type,
                'user_id' => $this->member->user->id,
                'sender_id' => $this->sender->id,
            ],
        ];

        return $notification_array;
    }
}
