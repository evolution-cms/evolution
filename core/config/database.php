<?php return [
    'default' => 'default',
    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DATABASE', 1),
            'read_timeout' => env('REDIS_TIMEOUT', 60),
            'context' => [
                'auth' => [env('REDIS_USER', null), env('REDIS_PASS', null)],
            ],
        ],
    ],
];
