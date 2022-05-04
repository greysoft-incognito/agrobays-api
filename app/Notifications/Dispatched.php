<?php

namespace App\Notifications;

use App\Models\FoodBag;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Dispatched extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($dispatch = null)
    {
        $this->dispatch = $dispatch;
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via()
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
        $type = $notifiable->dispatchable instanceof Order ? 'order' : ($notifiable->dispatchable instanceof FoodBag ? 'bag' : 'package');
        $status = ($notifiable->status === 'pending') ? 'shipped' : $notifiable->status;
        $message = [
            'order' => [
                'name' => $notifiable->dispatchable->user->firstname,
                'cta' => ['code' => $notifiable->code],
                'message_line1' => 'Your fruit order is on it\'s way to you, we will let you know when it\'s available, you will need the code below to confirm when you receive your order.',
                'close_greeting' => __('Regards, <br/>:0', [config('settings.site_name')]),
                'message_help' => 'Don\'t give this code to the dispatch rider till you have received your package.',
            ],
            'bag' => [
                'name' => $notifiable->dispatchable->user->firstname,
                'cta' => ['code' => $notifiable->code],
                'message_line1' => 'Your food bag is on it\'s way to you, we will let you know when it\'s available, you will need the code below to confirm when you receive your bag.',
                'close_greeting' => __('Regards, <br/>:0', [config('settings.site_name')]),
                'message_help' => 'Don\'t give this code to the dispatch rider till you have received your package.',
            ]
        ];
        return (new MailMessage)->view(
            ['email', 'email-plain'], $message[$type]??$message['order']
        )
        ->subject(__($type === 'order' ? "Order {$status} - :0" : "Food Bag {$status} - :0", [config('settings.site_name')]));
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
