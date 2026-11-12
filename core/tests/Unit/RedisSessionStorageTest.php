<?php

/*
| The legacy PHP session (EVO_SESSION off) stores in Redis through the ext-redis session handler
| when SESSION_STORAGE=redis. It used the userland dmitry-suffi/redis-session-handler before; the
| keys (PHPREDIS_SESSION:<id>) and the locking stay the same.
*/

test('the save path points at the configured Redis server and database', function () {
    expect(redisSessionSavePath([
        'host' => '10.0.0.5',
        'port' => '6380',
        'timeout' => '2.5',
        'database' => '3',
    ]))->toBe('tcp://10.0.0.5:6380?timeout=2.5&database=3');
});

test('the save path falls back to the defaults of the old handler', function () {
    expect(redisSessionSavePath([]))->toBe('tcp://127.0.0.1:6379?timeout=60&database=0')
        ->and(redisSessionSavePath(['host' => ' ', 'port' => '', 'timeout' => null, 'user' => null, 'password' => null]))
        ->toBe('tcp://127.0.0.1:6379?timeout=60&database=0');
});

test('the save path authenticates with a password, or an ACL user and password', function () {
    expect(redisSessionSavePath(['host' => 'redis', 'password' => 'p&ss=w#rd']))
        ->toBe('tcp://redis:6379?timeout=60&database=0&auth=p%26ss%3Dw%23rd')
        ->and(redisSessionSavePath(['host' => 'redis', 'user' => 'evo', 'password' => 'secret']))
        ->toBe('tcp://redis:6379?timeout=60&database=0&auth%5B0%5D=evo&auth%5B1%5D=secret')
        ->and(redisSessionSavePath(['host' => 'redis', 'user' => 'evo']))
        ->toBe('tcp://redis:6379?timeout=60&database=0');
});

test('the save path accepts sockets, schemes and IPv6 hosts', function () {
    expect(redisSessionSavePath(['host' => '/run/redis/redis.sock', 'port' => '6379']))
        ->toBe('unix:///run/redis/redis.sock?timeout=60&database=0')
        ->and(redisSessionSavePath(['host' => 'unix:///run/redis.sock']))
        ->toBe('unix:///run/redis.sock?timeout=60&database=0')
        ->and(redisSessionSavePath(['host' => 'tls://redis.example.com', 'port' => '6380']))
        ->toBe('tls://redis.example.com:6380?timeout=60&database=0')
        ->and(redisSessionSavePath(['host' => '::1']))
        ->toBe('tcp://[::1]:6379?timeout=60&database=0');
});

test('an unreachable Redis or a missing ext-redis leaves the legacy session on files', function () {
    $root = dirname(__DIR__, 3);
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'evo-redis-session-' . bin2hex(random_bytes(4));
    mkdir($dir);
    $script = $dir . DIRECTORY_SEPARATOR . 'worker.php';
    file_put_contents($script, '<?php' . "\n"
        // startCMSSession() returns at once on CLI
        . 'function is_cli() { return false; }' . "\n"
        . '$_SERVER["SERVER_PORT"] = 80; $_SERVER["REQUEST_TIME"] = time();' . "\n"
        . 'define("SESSION_STORAGE", "redis"); define("EVO_SESSION", false); define("HTTPS_PORT", 443);' . "\n"
        . 'define("SESSION_COOKIE_NAME", "evotest"); define("EVO_BASE_URL", "/");' . "\n"
        . 'putenv("REDIS_HOST=127.0.0.1"); putenv("REDIS_PORT=1"); putenv("REDIS_TIMEOUT=1");' . "\n"
        . 'ini_set("session.save_handler", "files");' . "\n"
        . 'ini_set("session.save_path", ' . var_export($dir, true) . ');' . "\n"
        . 'ini_set("log_errors", "1"); ini_set("error_log", ' . var_export($dir . DIRECTORY_SEPARATOR . 'error.log', true) . ');' . "\n"
        . 'require ' . var_export($root . '/core/vendor/autoload.php', true) . ';' . "\n"
        . 'startCMSSession();' . "\n"
        . '$_SESSION["probe"] = 1;' . "\n"
        . 'echo json_encode(["status" => session_status(), "handler" => ini_get("session.save_handler"), "redis" => extension_loaded("redis")]);' . "\n");

    try {
        $output = evoRunPhp($script, [], $exitCode, false);
        $result = json_decode($output, true);
        $log = (string) @file_get_contents($dir . DIRECTORY_SEPARATOR . 'error.log');
    } finally {
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }

    expect($exitCode)->toBe(0, $output)
        ->and($result)->toBeArray($output)
        ->and($result['status'])->toBe(PHP_SESSION_ACTIVE)
        ->and($result['handler'])->toBe('files');
    if ($result['redis']) {
        expect($log)->toContain('Redis session storage is unavailable');
    } else {
        expect($log)->not->toContain('Redis session storage');
    }
});

test('the core no longer depends on a userland Redis session handler', function () {
    $core = dirname(__DIR__, 2);
    $composer = json_decode((string) file_get_contents($core . '/composer.json'), true);

    expect($composer['require'])->not->toHaveKey('dmitry-suffi/redis-session-handler')
        ->and((string) file_get_contents($core . '/functions/preload.php'))->not->toContain('suffi\\');
});
