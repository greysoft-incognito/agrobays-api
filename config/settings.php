<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Site specific Configuration
    |--------------------------------------------------------------------------
    |
    | These settings determine how the site is to be run or managed
    |
    */
    'site_name' => 'Agrobays AgroFoods',
    'currency_symbol' => '₦',
    'currency' => 'NGN',
    'use_queue' => true,
    'keep_successful_queue_logs' => true,
    'slack_debug' => false,
    'slack_logger' => true,
    'payment_verify_url' => env('PAYMENT_VERIFY_URL', 'http://localhost:8080/payment/verify'),
    'default_banner' => env('ASSETS_URL', 'http://localhost:8080') . '/media/default_banner.png',
    'paystack_public_key' => env('PAYSTACK_PUBLIC_KEY', 'pk_'),
    'trx_prefix' => 'AGB-',
    'contact_address' => 'http://domain.com',
    'permissions' => [
        'manage_guests' => 'View, manage and get notifications for guest and bootcamp registrations',
        'manage_users' => 'Create, view, manage and get notifications for user access',
        'manage_mailing' => 'Create, View, manage and get notifications for mailing lists',
        'change_config' => 'Change site configuration',
        'manage_queues' => 'View and delete scheduled tasks and queues',
        'manage_correspondence' => 'View, manage and get notifications for contact messages',
        'manage_system' => 'View and manage main system processes including configurations.',
        'update_permisions' => 'Determine and update what permissions and privileges users have access to',
        'plans_and_subscriptions' => 'Create and manage subscriptions and subscription plans',
        'hahaha_ahahah' => 'LOL Haha!',
    ],
    /*
    |---------------------------------------------------------------------------------
    | Message templates
    |---------------------------------------------------------------------------------
    | Variables include {username}, {name}, {firstname}, {lastname}, {site_name}, {message}, {reserved}
    |
     */
    'messages' => [
        'variables' => 'Available Variables: {username}, {name}, {firstname}, {lastname}, {site_name}, {message}, {reserved}. (Some variables may not apply to some actions)',
        'greeting' => 'Hello {username},',
        'mailing_list_sub' => 'You are receiving this email because you recently subscribed to our mailing list, what this means is that you will get every information about {site_name} before anyone else does. Thanks for your support!',
        'mailing_list_sub_admin' => '{name} has just joined the mailing list.',
        'mailing_list_exit' => 'We are sad to see you go, but we are okay with it, we hope to see you again.',
        'mailing_list_exit_admin' => '{name} has just left the mailing list.',
        'contact_message' => 'Thank you for contacting us, we will look in to your query and respond if we find the need to do so.',
        'contact_message_admin' => "{name} has just sent a message: \n {message}",
    ],
];