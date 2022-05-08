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
    public function __construct($status = null)
    {
        $this->status = $status;
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $n    notifiable
     * @return array
     */
    public function via()
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $n    notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($n)
    {
        $type = $n->dispatchable instanceof Order ? 'order' : ($n->dispatchable instanceof Subscription ? 'bag' : 'package');
        $status = $this->status ?? (($n->status === 'pending') ? 'shipped' : $n->status);
        $package = $type === 'order' ? "fruit order" : "food bag";
        $handler_phone = $n->user->phone ?? '';
        $fl = env('FRONTEND_LINK');
        $line1 = [
            'shipped' => "Your {$package} with REF: {$n->reference} is on it's way to you, we will let you know when it's available, you will need the code below to confirm when you receive your order.",
            'confirmed' => "Your {$package} with REF: {$n->reference} has been confirmed and will be dispatched soon. Track your package <a href=\"{$fl}/track/order/{$n->reference}\">Here</a>",
            'dispatched' => "Your {$package} with REF: {$n->reference} has been dispatched and will be delivered soon, your handler will call you from {$handler_phone}, please keep your phone reachable.",
            'delivered' => 'Congratulations, you package has been delivered, thanks for engaging our services.',
            'assigned' => "You have been assigned to deliver {$package} package with REF: {$n->reference} to {$n->dispatchable->user->fullname} ({$n->dispatchable->user->phone}), you are required to visit the dispatch facility for further instructions.",
        ];

        $message = [
            'order' => [
                'name' => $n->dispatchable->user->firstname,
                'cta' => $status === 'shipped' ? ['code' => $n->code] : null,
                'message_line1' => $line1[$this->status??$status]??'Your package has been shipped and will be delivered soon.',
                'close_greeting' => __('Regards, <br/>:0', [config('settings.site_name')]),
                'message_help' => $status === 'shipped'
                    ? 'Don\'t give this code to the dispatch rider till you have received your package.'
                    : 'You can call our help lines or email us if you encounter any challenges.',
            ],
            'bag' => [
                'name' => $n->dispatchable->user->firstname,
                'cta' => $status === 'shipped' ? ['code' => $n->code] : null,
                'message_line1' => $line1[$this->status??$status]??'Your package has been shipped and will be delivered soon.',
                'close_greeting' => __('Regards, <br/>:0', [config('settings.site_name')]),
                'message_help' => $status === 'shipped'
                    ? 'Don\'t give this code to the dispatch rider till you have received your package.'
                    : 'You can call our help lines or email us if you encounter any challenges.',
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
     * @param  mixed  $n    notifiable
     * @return array
     */
    public function toArray($n)
    {
        return [
            //
        ];
    }
}
