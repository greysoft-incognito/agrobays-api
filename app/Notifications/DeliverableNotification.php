<?php

namespace App\Notifications;

use App\Models\DeliverableNotification as ModelsDeliverableNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeliverableNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        public readonly ModelsDeliverableNotification $deliverable
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        if ($this->deliverable->type == 'inapp') {
            return ['database'];
        } elseif ($this->deliverable->type == 'broadcast') {
            return ['broadcast'];
        }

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
        $text = str($this->deliverable->message)->replaceMatches('/\{([^}]*)\}/', ":$1")->toString();

        $message = [
            'hide_name' => true,
            'message_line1' => __($text, $notifiable->basic_data?->toArray()),
            'close_greeting' => 'Regards, <br/>' . config('settings.site_name'),
        ];

        return (new MailMessage())->view(
            ['email', 'email-plain'],
            $message
        )->subject($this->deliverable->subject);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $text = str($this->deliverable->message)
            ->stripTags()->replaceMatches('/\{([^}]*)\}/', ":$1")
            ->replaceMatches('/\n\t/', " ")
            ->toString();

        return [
            'type' => 'Notification',
            'title' => $this->deliverable->subject,
            'message' => __($text, $notifiable->basic_data?->toArray()),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        $text = str($this->deliverable->message)
            ->stripTags()->replaceMatches('/\{([^}]*)\}/', ":$1")
            ->replaceMatches('/\n\t/', " ")
            ->toString();

        return new BroadcastMessage([
            'id' => null,
            'type' => 'Notification',
            'title' => $this->deliverable->subject,
            'message' => __($text, $notifiable->basic_data?->toArray()),
            'read_at' => null,
            'created_at' => now(),
        ]);
    }
}
