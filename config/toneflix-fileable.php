<?php

return [
    'collections' => [
        'avatar' => [
            'size' => [400, 400],
            'path' => 'avatars/',
            'default' => 'default.png',
        ],
        'banner' => [
            'size' => [1200, 600],
            'path' => 'media/banners/',
            'default' => 'default.png',
        ],
        'feedback' => [
            'path' => 'media/feedback/',
            'default' => 'default.png',
        ],
        'plans' => [
            'path' => 'media/plans/',
            'default' => 'default.png',
            'size' => [400, 400],
        ],
        'products' => [
            'path' => 'uploads/images/',
            'default' => 'default.png',
            'size' => [400, 400],
        ],
        'private' => [
            'files' => [
                'path' => 'files/',
                'secure' => false,
            ],
            'images' => [
                'path' => 'files/images/',
                'default' => 'default.png',
                'secure' => true,
            ],
            'docs' => [
                'path' => 'files/docs/',
                'default' => 'default.png',
                'secure' => true,
            ],
        ],
    ],
    'image_sizes' => [
        'xs' => '431',
        'sm' => '431',
        'md' => '694',
        'lg' => '720',
        'xl' => '1080',
        'xs-square' => '431x431',
        'sm-square' => '431x431',
        'md-square' => '694x694',
        'lg-square' => '720x720',
        'xl-square' => '1080x1080',
    ],
    'file_route_secure_middleware' => 'web',
    'file_route_secure' => 'secure/files/{file}',
    'file_route_open' => 'open/files/{file}',
    'image_templates' => [
    ],
    'symlinks' => [
        public_path('media') => storage_path('app/public/media'),
        public_path('avatars') => storage_path('app/public/avatars'),
    ],
];
