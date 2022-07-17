<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class Dispatched extends Notification
{
    use Queueable;

    protected $shortUrl;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($item, $status = null)
    {
        $this->item = $item;
        $this->status = $status;
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
        $item = $this->item;
        $type = $this->item instanceof Order ? 'order' : ($this->item instanceof Subscription ? 'bag' : 'package');
        $status = $this->status ?? (($item->status === 'pending') ? 'shipped' : $item->status);
        $package = $type === 'order' ? 'fruit order' : 'food bag';
        $handler_phone = $item->user->phone ?? '';

        $link = env('FRONTEND_LINK')."/track/order/{$item->reference}";

        $line1 = [
            'shipped' => "Your {$package} with REF: {$item->reference} is on it's way to you, we will let you know when it's available, you will need the code below to confirm when you receive your order.",
            'confirmed' => "Your {$package} with REF: {$item->reference} has been confirmed and will be dispatched soon. Track your package <a href=\"{$link}\">Here</a>",
            'dispatched' => "Your {$package} with REF: {$item->reference} has been dispatched and will be delivered soon, your handler will call you from {$handler_phone}, please keep your phone reachable.",
            'delivered' => 'Congratulations, you package has been delivered, thanks for engaging our services.',
            'assigned' => "You have been assigned to deliver {$package} package with REF: {$item->reference} to {$item->dispatchable->user->fullname} ({$item->dispatchable->user->phone}), you are required to visit the dispatch facility for further instructions.",
        ];

        $message = [
            'order' => [
                'name' => $item->dispatchable->user->firstname,
                'cta' => $status === 'shipped' ? ['code' => $item->code] : null,
                'message_line1' => $line1[$status] ?? 'Your package has been shipped and will be delivered soon.',
                'close_greeting' => __('Regards, <br/>:0', [config('settings.site_name')]),
                'message_help' => $status === 'shipped'
                    ? 'Don\'t give this code to the dispatch rider till you have received your package.'
                    : 'You can call our help lines or email us if you encounter any challenges.',
            ],
            'bag' => [
                'name' => $item->dispatchable->user->firstname,
                'cta' => $status === 'shipped' ? ['code' => $item->code] : null,
                'message_line1' => $line1[$status] ?? 'Your package has been shipped and will be delivered soon.',
                'close_greeting' => __('Regards, <br/>:0', [config('settings.site_name')]),
                'message_help' => $status === 'shipped'
                    ? 'Don\'t give this code to the dispatch rider till you have received your package.'
                    : 'You can call our help lines or email us if you encounter any challenges.',
            ],
        ];

        return (new MailMessage)->view(
            ['email', 'email-plain'], $message[$type] ?? $message['order']
        )
        ->subject(__($type === 'order' ? "Order {$status} - :0" : "Food Bag {$status} - :0", [config('settings.site_name')]));
    }

    /**
     * Get the sms representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \NotificationChannels\Twilio\TwilioSmsMessage
     */
    public function toTwilio($notifiable)
    {
        $item = $this->item;
        $type = $this->item instanceof Order ? 'order' : ($this->item instanceof Subscription ? 'bag' : 'package');
        $status = $this->status ?? (($item->status === 'pending') ? 'shipped' : $item->status);
        $package = $type === 'order' ? 'fruit order' : 'food bag';
        $handler_phone = $item->user->phone ?? '';

        $link = env('FRONTEND_LINK')."/track/order/{$item->reference}";

        $text = [
            'shipped' => "Your {$package} is on it's way, you will need this code {$item->code}, to confirm when you receive your order.",
            'confirmed' => "Your {$package} is confirmed and will be dispatched soon. Track package with this link {$link}.",
            'dispatched' => "Your {$package} has been dispatched, your handler will call you from {$handler_phone}, please keep your phone reachable.",
            'delivered' => 'Congratulations, you package has been delivered, thanks for using our services.',
            'assigned' => "A {$package} package with REF: {$item->reference} is assigned to you for {$item->dispatchable->user->fullname} ({$item->dispatchable->user->phone}), please visit the dispatch facility for further instructions.",
        ];

        $message = __('Hi :0, ', [$item->dispatchable->user->firstname]).($text[$status]);

        return (new TwilioSmsMessage())
            ->content($message);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $item = $this->item;
        $type = $this->item instanceof Order ? 'order' : ($this->item instanceof Subscription ? 'bag' : 'package');
        $status = $this->status ?? (($item->status === 'pending') ? 'shipped' : $item->status);
        $package = $type === 'order' ? 'fruit order' : 'food bag';
        $handler_phone = $item->user->phone ?? '';

        $link = "/track/order/{$item->reference}";
        if ($status !== 'confirmed') {
            // dd(ModelsNotification::where('notifiable_types', 'App\Models\Dispatch')->where('notifiable_id', $item->id)->where('data->shortUrl', '!=', NULL)->first());

            // (new \Cuttly)->delete($this->shortUrl);
        }

        $text = [
            'shipped' => "Your {$package} is on it's way, you will need this code {$item->code}, to confirm when you receive your order.",
            'confirmed' => "Your {$package} is confirmed and will be dispatched soon. Track package with <a href=\"{$link}\">this link</a>.",
            'dispatched' => "Your {$package} has been dispatched, your handler will call you from {$handler_phone}, please keep your phone reachable.",
            'delivered' => 'Congratulations, you package has been delivered, thanks for using our services.',
            'assigned' => "A {$package} package with REF: {$item->reference} is assigned to you for {$item->dispatchable->user->fullname} ({$item->dispatchable->user->phone}), please visit the dispatch facility for further instructions.",
        ];

        return [
            'type' => $type,
            'title' => Str::of($type)->ucfirst()->append(" {$status}"),
            'message' => $text[$status],
        ];
    }
}