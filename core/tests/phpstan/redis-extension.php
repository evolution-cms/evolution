<?php

/**
 * phpredis can lag behind a new PHP release (6.3.0 does not build on 8.6.0). Without it PHPStan does not
 * know the Redis class constants, so their checks are left out then; CI reports the missing extension.
 */
return extension_loaded('redis') ? [] : [
    'parameters' => [
        'ignoreErrors' => [
            [
                'message' => '#^Access to undefined constant Redis::\w+\.$#',
                'path' => dirname(__DIR__, 2) . '/src/Support/RedisSessionHandler.php',
            ],
        ],
    ],
];
