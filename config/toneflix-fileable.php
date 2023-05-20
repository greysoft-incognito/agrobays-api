<?php

return [
    'collections' => [
        'feedback' => [
            'path' => 'media/feedback/',
            'default' => 'default.png',
        ],
        'plans' => [
            'path' => 'media/plans/',
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
        ],
    ],
    'image_sizes' => [
        'xs' => '431',
        'sm' => '431',
        'md' => '694',
        'lg' => '720',
        'xl' => '1080',
    ],
    'file_route_secure_middleware' => 'window_auth',
    'file_route_secure' => 'secure/files/{file}',
    'file_route_open' => 'open/files/{file}',
    'image_templates' => [
    ],
    'symlinks' => [
        public_path('media') => storage_path('app/public/media'),
    ],
];