<?php

namespace App\Notifications;

use App\Models\Cooperative;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class FollowRequest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * This will determine the type of message to send
     *
     * @var string follow|join|accept|approve|decline
     */
    protected string $type;

    protected bool $pending;

    protected User $follower;

    protected User|Cooperative $following;

    protected array $messages;

    /**
     * Create a new notification instance.
     *
     * @param  \App\Models\User  $follower
     * @param  \App\Models\User|\App\Models\Cooperative  $following
     * @param  string  $type
     *  - User: [follow|accept] (Dont send notification when user rejects request)
     *  - Cooperative: [join|approve|decline]
     * @return void
     */
    public function __construct(User $follower, User|Cooperative $following, string $type = 'follow')
    {
        $this->type = $type;
        $this->follower = $follower;
        $this->following = $following;

        $this->pending = $follower->hasRequestedToFollow($following);

        $this->messages = [
            'join' => [
                'subject' => __($this->pending ? 'New member request' : 'New member'),
                'message' => __($this->pending
                     ? ':0 is requesting to become a member of :1.'
                     : ':0 is now a member of :1.', [
                         $this->follower->fullname,
                         $this->following->name ?? $this->following->title ?? '-',
                     ]),
            ],
            'approve' => [
                'subject' => __('Member request approved'),
                'message' => __('Your request to become a :0 :1 has been approved.', [
                    $this->follower->type == 'mentor' ? 'mentor in' : 'member of',
                    $this->following->name ?? $this->following->title ?? '-',
                ]),
            ],
            'decline' => [
                'subject' => __('Member request declined'),
                'message' => __('Your request to become a :0 :1 has been declined.', [
                    $this->follower->type == 'mentor' ? 'mentor in' : 'member of',
                    $this->following->name ?? $this->following->title ?? '-',
                ]),
            ],
        ];

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
            'message_line1' => __(':message:action', [
                'message' => $this->messages[$this->type]['message'] ?? '',
                'action' => $this->pending
                    ? __(' Login to accept or decline the request.')
                    : '',
            ]),
            'close_greeting' => 'Regards, <br/>'.config('settings.site_name'),
        ];

        return (new MailMessage())->view(
            ['email', 'email-plain'],
            $message
        )->subject($this->messages[$this->type]['subject'] ?? 'Member request');
    }

    /**
     * Get the sms representation of the notification.
     *
     * @param  mixed  $notifiable    notifiable
     * @return \NotificationChannels\Twilio\TwilioSmsMessage
     */
    public function toTwilio($notifiable)
    {
        $message = __(':message:action', [
            'message' => $this->messages[$this->type]['message'] ?? '',
            'action' => $this->pending
                ? __(' Login to accept or decline the request.')
                : '',
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
        $following_class = get_class($this->following);

        $route = [
            Cooperative::class => route('cooperatives.accept.follower', [
                $this->following->id, $this->follower->id, ':status',
            ]),
        ];

        if ($following_class === Cooperative::class) {
            $canAct = $this->following->members()
                ->forUser($this->follower)
                ->isAccepted(true)
                ->where(function (Builder $q) {
                    $q->whereJsonContains('abilities', 'manage_members');
                    $q->orWhereJsonContains('abilities', 'all');
                })->exists();
        } else {
            $canAct = false;
        }

        $action_url = parse_url($route[$following_class] ?? '', PHP_URL_PATH);

        $image_url = parse_url($this->follower->image_url, PHP_URL_PATH);

        $notification_array = [
            'icon' => 'person_add',
            'type' => 'follow_request',
            'image' => $image_url,
            'action' => $action_url,
            'message' => $this->messages[$this->type]['message'],
            'title' => $this->messages[$this->type]['subject'],
            'actions' => $canAct ? ['accepted' => 'Accept', 'declined' => 'Decline'] : [],
            'request' => [
                'id' => $this->following->id,
                'type' => $following_class,
                'user_id' => $notifiable->id,
                'sender_id' => $this->follower->id,
            ],
        ];

        return $notification_array;
    }
}
