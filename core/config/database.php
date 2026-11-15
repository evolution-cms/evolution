<?php return [
    'default' => 'default',
    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            // Sent with AUTH. Not as a `context`: Laravel 13 hands that to phpredis as a TLS stream
            // context, and the connection then fails even without credentials.
            'username' => env('REDIS_USER', null),
            'password' => env('REDIS_PASSWORD', env('REDIS_PASS', null)),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DATABASE', 1),
            'read_timeout' => env('REDIS_TIMEOUT', 60),
        ],
    ],
];
