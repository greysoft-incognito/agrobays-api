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
    'prefered_notification_channels' => [
        'mail',
    ],

    // 'prefered_notification_channels' => ['sms', 'mail'],

    'keep_successful_queue_logs' => true,
    'slack_debug' => false,
    'slack_logger' => false,
    'verify_email' => false,
    'verify_phone' => false,
    'token_lifespan' => 30,
    'shipping_fee' => 5000,
    'paid_shipping' => false,
    'feedback_system' => true,
    'referral_system' => true,
    'referral_bonus' => 1000.0,
    'referral_mode' => 3,
    'require_org_approval' => false,
    'foodbag_locktime' => 0.5,
    'withdraw_to' => 'wallet',
    'frontend_link' => 'http://localhost:8080',
    'payment_verify_url' => env('PAYMENT_VERIFY_URL', 'http://localhost:8080/payment/verify'),
    'default_banner' => env('ASSETS_URL', 'http://localhost:8080').'/media/default_banner.png',
    'paystack_public_key' => env('PAYSTACK_PUBLIC_KEY', 'pk_'),
    'ipinfo_access_token' => env('IPINFO_ACCESS_TOKEN', 'a349_'),
    'trx_prefix' => 'AGB-',
    'contact_phone' => '+234 813 000 0001',
    'contact_email' => 'hi@greysoft.ng',
    'contact_address' => '31 Gwari Avenue, Barnawa, Kaduna',
    'office_address' => '31 Gwari Avenue, Barnawa, Kaduna',
    'last_setting_time' => '2023-09-09 03:57:21',
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
