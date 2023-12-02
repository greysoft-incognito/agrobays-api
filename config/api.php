<?php

return [
    // The current api version
    'api_version' => env('API_VERSION', '1.0.0'),

    // APP Options
    'app_version' => env('APP_VERSION', '1.0.0'),
    'app_upgrade' => env('APP_UPGRADE', false),
    // 'app_update_url' => env('APP_UPDATE_URL', 'https://play.google.com/store/apps/details?id=com.greysoft.agrobays'),
    'app_update_url' => env('APP_UPDATE_URL', 'market://details?id=com.greysoft.agrobays'),

    // IOS Options
    'ios_version' => env('IOS_VERSION', '1.0.0'),
    'ios_upgrade' => env('IOS_UPGRADE', false),
    'ios_update_url' => env('IOS_UPDATE_URL', 'itms-apps://itunes.apple.com/ng/app/agrobays-agrofoods/id6463464628'),
    // 'ios_update_url' => env('IOS_UPDATE_URL', 'https://apps.apple.com/us/app/agrobays-agrofoods/id1531375279'),
];
