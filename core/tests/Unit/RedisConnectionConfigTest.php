<?php

use Illuminate\Container\Container;
use Illuminate\Redis\Connectors\PhpRedisConnector;
use Illuminate\Redis\RedisManager;

/*
| core/config/database.php holds the Redis connection Laravel uses for SESSION_DRIVER=redis and
| CACHE_DRIVER=redis. Credentials go in as username/password, which Laravel sends with AUTH: a
| `context` is handed to phpredis as a TLS stream context from Laravel 13 on, and the plain TCP
| connection then fails, with or without credentials.
*/

function redisConnectionConfig(array $env): array
{
    $names = ['REDIS_HOST', 'REDIS_PORT', 'REDIS_USER', 'REDIS_PASS', 'REDIS_PASSWORD', 'REDIS_DATABASE', 'REDIS_TIMEOUT'];
    $saved = [];
    foreach ($names as $name) {
        $saved[$name] = getenv($name);
        putenv(array_key_exists($name, $env) ? $name . '=' . $env[$name] : $name);
    }
    try {
        return (require dirname(__DIR__, 2) . '/config/database.php')['redis']['default'];
    } finally {
        foreach ($saved as $name => $value) {
            putenv($value === false ? $name : $name . '=' . $value);
        }
    }
}

/** The arguments the connector passes to Redis::connect(). */
function redisConnectArguments(array $config): array
{
    $client = new class {
        public array $arguments = [];

        public function connect(...$arguments): bool
        {
            $this->arguments = $arguments;

            return true;
        }
    };
    $connector = new class extends PhpRedisConnector {
        public function connectWith($client, array $config): void
        {
            $this->establishConnection($client, $config);
        }
    };
    $connector->connectWith($client, $config);

    return $client->arguments;
}

test('an ACL user and password reach Laravel as username and password', function () {
    $config = redisConnectionConfig(['REDIS_USER' => 'evo', 'REDIS_PASS' => 's3cr&t']);

    expect($config['username'])->toBe('evo')
        ->and($config['password'])->toBe('s3cr&t')
        ->and($config)->not->toHaveKey('context');
});

test('REDIS_PASSWORD still sets the password, ahead of REDIS_PASS', function () {
    expect(redisConnectionConfig(['REDIS_PASSWORD' => 'one', 'REDIS_PASS' => 'two'])['password'])->toBe('one')
        ->and(redisConnectionConfig([])['password'])->toBeNull()
        ->and(redisConnectionConfig([])['username'])->toBeNull();
});

test('phpredis gets no stream context, so the connection stays plain TCP', function (array $env) {
    $arguments = redisConnectArguments(redisConnectionConfig($env + ['REDIS_HOST' => 'redis']));

    expect($arguments[0])->toBe('redis');
    foreach (array_slice($arguments, 5) as $argument) {
        expect(is_array($argument) && isset($argument['stream']))->toBeFalse();
    }
})->with([
    'no credentials' => [[]],
    'ACL user' => [['REDIS_USER' => 'evo', 'REDIS_PASS' => 'secret']],
    'password only' => [['REDIS_PASSWORD' => 'secret']],
])->skip(!extension_loaded('redis'), 'needs ext-redis');

test('Laravel connects to Redis with the shipped configuration', function () {
    [$host, $port] = array_pad(explode(':', (string)getenv('EVO_TEST_REDIS'), 2), 2, '6379');
    $config = redisConnectionConfig(['REDIS_HOST' => $host, 'REDIS_PORT' => $port, 'REDIS_TIMEOUT' => '5']);
    $redis = new RedisManager(new Container(), 'phpredis', ['default' => $config]);

    expect($redis->connection('default')->ping())->not->toBeFalse();
})->skip(!getenv('EVO_TEST_REDIS') || !extension_loaded('redis'), 'needs ext-redis and EVO_TEST_REDIS=host:port');
