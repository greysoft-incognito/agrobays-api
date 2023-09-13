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
        $message = [
            'name' => $notifiable->firstname,
            'message_line1' => $this->deliverable->message,
            'close_greeting' => 'Regards, <br/>'.config('settings.site_name'),
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
        return [
            'type' => 'Notification',
            'title' => $this->deliverable->subject,
            'message' => strip_tags($this->deliverable->message),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => null,
            'type' => 'Notification',
            'title' => $this->deliverable->subject,
            'message' => strip_tags($this->deliverable->message),
            'read_at' => null,
            'created_at' => now(),
        ]);
    }
}
