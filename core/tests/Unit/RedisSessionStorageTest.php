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

const PHPREDIS_LOCK_DEFAULTS = [
    'redis.session.lock_expire' => '0',
    'redis.session.lock_retries' => '100',
    'redis.session.lock_wait_time' => '20000',
];

test('locks expire and waits end with the execution limit', function () {
    expect(redisSessionLockSettings(30, PHPREDIS_LOCK_DEFAULTS))->toBe([
        'redis.session.lock_expire' => '30',
        'redis.session.lock_retries' => '1050', // 21 s, 0.7 of the limit
    ]);
});

test('without an execution limit locks still expire and waits stay bounded', function () {
    expect(redisSessionLockSettings(0, PHPREDIS_LOCK_DEFAULTS))->toBe([
        'redis.session.lock_expire' => '30',
        'redis.session.lock_retries' => '1000', // 20 s
    ])->and(redisSessionLockSettings(-1, []))->toBe([
        'redis.session.lock_expire' => '30',
        'redis.session.lock_wait_time' => '20000',
        'redis.session.lock_retries' => '1000',
    ]);
});

test('a wait never outlasts the lock it waits for', function () {
    expect(redisSessionLockSettings(0, ['redis.session.lock_expire' => '5'] + PHPREDIS_LOCK_DEFAULTS))
        ->toBe(['redis.session.lock_retries' => '250']);
});

test('lock settings an operator changed are kept', function () {
    expect(redisSessionLockSettings(0, [
        'redis.session.lock_expire' => '12',
        'redis.session.lock_retries' => '0',
        'redis.session.lock_wait_time' => '50000',
    ]))->toBe([])
        ->and(redisSessionLockSettings(0, ['redis.session.lock_wait_time' => '100000'] + PHPREDIS_LOCK_DEFAULTS))
        ->toBe(['redis.session.lock_expire' => '30', 'redis.session.lock_retries' => '200']);
});

/*
| Integration: these need ext-redis and a Redis server named by EVO_TEST_REDIS (host:port; CI provides
| one). They use database 15 and remove their keys.
*/

function redisSessionTestServer(): string
{
    $server = (string)getenv('EVO_TEST_REDIS');
    if (!extension_loaded('redis') || $server === '') {
        test()->markTestSkipped('needs ext-redis and EVO_TEST_REDIS=host:port');
    }
    if (PHP_OS_FAMILY === 'Windows') {
        test()->markTestSkipped('starts workers in the background with a POSIX shell');
    }

    return $server;
}

/**
 * Starts a worker in the background through evoRunCommand(); its output goes to <dir>/<name>.out.
 * @return int the worker's process id
 */
function redisSessionTestStart(string $server, string $id, string $dir, string $name, float $hold = 0): int
{
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__) . '/Mocks/redis_session_worker.php');
    foreach ([$server, $id, $dir, (string)$hold] as $arg) {
        $command .= ' ' . escapeshellarg($arg);
    }
    $pid = (int)trim(evoRunCommand($command . ' > ' . escapeshellarg("$dir/$name.out") . ' 2>&1 & echo $!'));
    expect($pid)->toBeGreaterThan(0);

    return $pid;
}

/**
 * Waits until the worker's output holds a line matching $done, killing it past the deadline:
 * a regression must fail the test, not hang the suite. The process itself is not polled, since
 * an orphan nobody reaps stays a zombie in a container without init.
 */
function redisSessionTestWait(int $pid, string $dir, string $name, string $done, float $deadline): string
{
    $start = microtime(true);
    do {
        $output = (string)@file_get_contents("$dir/$name.out");
        if (preg_match($done, $output)) {
            return $output;
        }
        usleep(50000);
    } while (microtime(true) - $start < $deadline);

    evoRunCommand('kill -9 ' . $pid . ' 2>/dev/null');
    expect(false)->toBeTrue("$name still waiting after {$deadline} s: $output");

    return $output;
}

function redisSessionTestResult(int $pid, string $dir, string $name, float $deadline): array
{
    $output = redisSessionTestWait($pid, $dir, $name, '/^\{.*\}$/m', $deadline);
    preg_match_all('/^\{.*\}$/m', $output, $lines);
    $result = json_decode((string)end($lines[0]), true);
    expect($result)->toBeArray($output);

    return $result;
}

function redisSessionTestCleanup(string $server, string $id, string $dir): void
{
    [$host, $port] = explode(':', $server) + [1 => '6379'];
    $redis = new Redis();
    if (@$redis->connect($host, (int)$port, 2)) {
        $redis->select(15);
        $redis->del('PHPREDIS_SESSION:' . $id, 'PHPREDIS_SESSION:' . $id . '_LOCK');
    }
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
        @unlink($file);
    }
    @rmdir($dir);
}

beforeEach(function () {
    $this->redisSessionId = 'evotest' . bin2hex(random_bytes(8));
    $this->redisSessionDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'evo-redis-lock-' . bin2hex(random_bytes(4));
    mkdir($this->redisSessionDir);
});

afterEach(function () {
    if (getenv('EVO_TEST_REDIS') && extension_loaded('redis')) {
        redisSessionTestCleanup((string)getenv('EVO_TEST_REDIS'), $this->redisSessionId, $this->redisSessionDir);
    } else {
        @rmdir($this->redisSessionDir);
    }
});

test('concurrent requests on one Redis session run one after the other', function () {
    $server = redisSessionTestServer();
    $dir = $this->redisSessionDir;
    $holder = redisSessionTestStart($server, $this->redisSessionId, $dir, 'holder', 1.5);
    redisSessionTestWait($holder, $dir, 'holder', '/^locked$/m', 30);

    $waiter = redisSessionTestStart($server, $this->redisSessionId, $dir, 'waiter');
    $second = redisSessionTestResult($waiter, $dir, 'waiter', 30);
    $first = redisSessionTestResult($holder, $dir, 'holder', 30);

    expect($first['handler'])->toBe('redis')
        ->and($first['lock'])->toBe(['expire' => '30', 'retries' => '1000', 'wait' => '20000'])
        ->and($second['handler'])->toBe('redis')
        ->and($second['opened'])->toBeGreaterThan(1.0)
        ->and($second['session']['holder'] ?? null)->toBe('first');
});

test('without an execution limit a killed request leaves an expiring lock and waiters give up in time', function () {
    $server = redisSessionTestServer();
    $dir = $this->redisSessionDir;
    $holder = redisSessionTestStart($server, $this->redisSessionId, $dir, 'holder', 120);
    redisSessionTestWait($holder, $dir, 'holder', '/^locked$/m', 30);
    // killed, no shutdown runs: nothing releases the lock
    evoRunCommand('kill -9 ' . $holder, $killed);
    expect($killed)->toBe(0);

    [$host, $port] = explode(':', $server) + [1 => '6379'];
    $redis = new Redis();
    $redis->connect($host, (int)$port, 2);
    $redis->select(15);
    $ttl = $redis->ttl('PHPREDIS_SESSION:' . $this->redisSessionId . '_LOCK');
    expect($ttl)->toBeGreaterThan(0)->toBeLessThanOrEqual(30);

    // the lock outlives the 20 s budget, so the waiter falls back instead of waiting it out
    $waiter = redisSessionTestStart($server, $this->redisSessionId, $dir, 'waiter');
    $result = redisSessionTestResult($waiter, $dir, 'waiter', 45);

    expect($result['opened'])->toBeGreaterThan(15)->toBeLessThan(30)
        ->and($result['handler'])->toBe('files')
        ->and($result['status'])->toBe(PHP_SESSION_ACTIVE)
        ->and((string)@file_get_contents($this->redisSessionDir . DIRECTORY_SEPARATOR . 'error.log'))
        ->toContain('the session stayed locked');
});

test('the core no longer depends on a userland Redis session handler', function () {
    $core = dirname(__DIR__, 2);
    $composer = json_decode((string) file_get_contents($core . '/composer.json'), true);

    expect($composer['require'])->not->toHaveKey('dmitry-suffi/redis-session-handler')
        ->and((string) file_get_contents($core . '/functions/preload.php'))->not->toContain('suffi\\');
});
