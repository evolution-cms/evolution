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
function redisSessionTestStart(
    string $server,
    string $id,
    string $dir,
    string $name,
    float $hold = 0,
    string $lockExpire = '',
    string $value = 'first',
    string $compression = ''
): int {
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__) . '/Mocks/redis_session_worker.php');
    foreach ([$server, $id, $dir, (string)$hold, $lockExpire, $value, $compression] as $arg) {
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

function redisSessionTestConnect(string $server): Redis
{
    [$host, $port] = explode(':', $server) + [1 => '6379'];
    $redis = new Redis();
    $redis->connect($host, (int)$port, 2);
    $redis->select(15);

    return $redis;
}

/**
 * The Redis::COMPRESSION_* value of a redis.session.compression method, skipping the test when
 * this ext-redis is built without it.
 */
function redisSessionTestCompression(string $method): int
{
    $constant = Redis::class . '::COMPRESSION_' . strtoupper($method);
    if (!defined($constant)) {
        test()->markTestSkipped("ext-redis is built without $method compression");
    }

    return (int)constant($constant);
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

    // "user": ext-redis behind EvolutionCMS\Support\RedisSessionHandler
    expect($first['handler'])->toBe('user')
        ->and($first['lock'])->toBe(['expire' => '30', 'retries' => '1000', 'wait' => '20000'])
        ->and($second['handler'])->toBe('user')
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

function redisSessionTestLog(string $dir): string
{
    return (string)@file_get_contents($dir . DIRECTORY_SEPARATOR . 'error.log');
}

test('a live request that keeps its session past the lock expiry still writes it', function () {
    $server = redisSessionTestServer();
    $dir = $this->redisSessionDir;
    $holder = redisSessionTestStart($server, $this->redisSessionId, $dir, 'holder', 4, '2');
    redisSessionTestWait($holder, $dir, 'holder', '/^locked$/m', 30);

    // the handler re-takes the lock under the secret phpredis gives it
    [$host, $port] = explode(':', $server) + [1 => '6379'];
    $redis = new Redis();
    $redis->connect($host, (int)$port, 2);
    $redis->select(15);
    expect($redis->get('PHPREDIS_SESSION:' . $this->redisSessionId . '_LOCK'))->toBe(gethostname() . '|' . $holder);

    $first = redisSessionTestResult($holder, $dir, 'holder', 30);
    $lockAfter = $redis->exists('PHPREDIS_SESSION:' . $this->redisSessionId . '_LOCK');

    $reader = redisSessionTestStart($server, $this->redisSessionId, $dir, 'reader');
    $fresh = redisSessionTestResult($reader, $dir, 'reader', 30);

    expect($first['handler'])->toBe('user')
        ->and($first['lock']['expire'])->toBe('2')
        ->and(redisSessionTestLog($dir))->not->toContain('lock not held')->not->toContain('lock expired')
        ->and($lockAfter)->toBe(0)
        ->and($fresh['handler'])->toBe('user')
        ->and($fresh['opened'])->toBeLessThan(1.0)
        ->and($fresh['session']['holder'] ?? null)->toBe('first');
});

test('a request whose expired lock another request took does not overwrite the session it changed', function (string $compression) {
    $server = redisSessionTestServer();
    if ($compression !== 'none') {
        redisSessionTestCompression($compression);
    }
    $dir = $this->redisSessionDir;
    $holder = redisSessionTestStart($server, $this->redisSessionId, $dir, 'holder', 5, '2', 'first', $compression);
    redisSessionTestWait($holder, $dir, 'holder', '/^locked$/m', 30);
    usleep(2500000);

    // the lock has expired: the next request takes it at once and changes the session
    $other = redisSessionTestStart($server, $this->redisSessionId, $dir, 'other', 0.2, '2', 'second', $compression);
    $second = redisSessionTestResult($other, $dir, 'other', 30);
    $first = redisSessionTestResult($holder, $dir, 'holder', 30);

    $reader = redisSessionTestStart($server, $this->redisSessionId, $dir, 'reader', 0, '', 'first', $compression);
    $fresh = redisSessionTestResult($reader, $dir, 'reader', 30);

    expect($second['opened'])->toBeLessThan(1.0)
        ->and($second['session']['holder'] ?? null)->toBe('second')
        ->and(redisSessionTestLog($dir))->toContain('session lock not held')
        ->and($fresh['session']['holder'] ?? null)->toBe('second');
})->with(['none', 'lzf', 'zstd', 'lz4']);

test('a live request keeps a compressed session it changed past the lock expiry', function (string $compression) {
    $server = redisSessionTestServer();
    $type = redisSessionTestCompression($compression);
    $dir = $this->redisSessionDir;
    $id = $this->redisSessionId;

    // an earlier request leaves a non-empty session, stored compressed
    $seed = redisSessionTestStart($server, $id, $dir, 'seed', 0.1, '2', 'seeded', $compression);
    expect(redisSessionTestResult($seed, $dir, 'seed', 30)['session']['holder'] ?? null)->toBe('seeded');
    $redis = redisSessionTestConnect($server);
    $stored = (string)$redis->get('PHPREDIS_SESSION:' . $id);
    $redis->setOption(Redis::OPT_COMPRESSION, $type);
    $seeded = $redis->_uncompress($stored);
    $redis->setOption(Redis::OPT_COMPRESSION, Redis::COMPRESSION_NONE);
    expect($seeded)->toContain('holder|s:6:"seeded"')
        ->and($stored)->not->toBe($seeded);

    // this request reads it, changes it and keeps it past the 2 s lock, with nobody else around
    $holder = redisSessionTestStart($server, $id, $dir, 'holder', 4, '2', 'changed', $compression);
    $first = redisSessionTestResult($holder, $dir, 'holder', 30);

    $reader = redisSessionTestStart($server, $id, $dir, 'reader', 0, '', 'first', $compression);
    $fresh = redisSessionTestResult($reader, $dir, 'reader', 30);

    expect($first['handler'])->toBe('user')
        ->and($first['session']['holder'] ?? null)->toBe('changed')
        ->and(redisSessionTestLog($dir))->not->toContain('lock not held')->not->toContain('lock expired')
        ->and($redis->exists('PHPREDIS_SESSION:' . $id . '_LOCK'))->toBe(0)
        ->and($fresh['session']['holder'] ?? null)->toBe('changed');
})->with(['lzf', 'zstd', 'lz4']);

test('the lock check reads a stored session as phpredis does', function (string $compression) {
    $type = redisSessionTestCompression($compression);
    $redis = redisSessionTestConnect(redisSessionTestServer());
    $data = 'holder|s:5:"first";' . str_repeat('x', 200);
    $redis->setOption(Redis::OPT_COMPRESSION, $type);
    $stored = $redis->_compress($data);
    $redis->setOption(Redis::OPT_COMPRESSION, Redis::COMPRESSION_NONE);

    expect($stored)->not->toBe($data)
        ->and(EvolutionCMS\Support\RedisSessionHandler::uncompress($redis, $stored, $type))->toBe($data)
        // stored before compression was enabled: phpredis reads it as it is
        ->and(EvolutionCMS\Support\RedisSessionHandler::uncompress($redis, $data, $type))->toBe($data)
        ->and(EvolutionCMS\Support\RedisSessionHandler::uncompress($redis, '', $type))->toBe('')
        ->and(EvolutionCMS\Support\RedisSessionHandler::uncompress($redis, $stored, Redis::COMPRESSION_NONE))->toBe($stored)
        // the connection is left as it was: the lock script's arguments go out as they are
        ->and($redis->getOption(Redis::OPT_COMPRESSION))->toBe(Redis::COMPRESSION_NONE);
})->with(['lzf', 'zstd', 'lz4']);

test('the session compression is read as phpredis reads redis.session.compression', function () {
    if (!extension_loaded('redis')) {
        test()->markTestSkipped('needs ext-redis');
    }
    $previous = (string)ini_get('redis.session.compression');
    $read = static function (string $setting): int {
        ini_set('redis.session.compression', $setting);

        return EvolutionCMS\Support\RedisSessionHandler::compression();
    };

    try {
        $expected = static fn(string $name): int => defined(Redis::class . '::COMPRESSION_' . $name)
            ? (int)constant(Redis::class . '::COMPRESSION_' . $name)
            : Redis::COMPRESSION_NONE;
        expect($read('none'))->toBe(Redis::COMPRESSION_NONE)
            ->and($read(''))->toBe(Redis::COMPRESSION_NONE)
            ->and($read('brotli'))->toBe(Redis::COMPRESSION_NONE)
            ->and($read('LZF'))->toBe($expected('LZF'))
            ->and($read('zstd'))->toBe($expected('ZSTD'))
            ->and($read('lz4'))->toBe($expected('LZ4'));
    } finally {
        ini_set('redis.session.compression', $previous);
    }
});

test('the core no longer depends on a userland Redis session handler', function () {
    $core = dirname(__DIR__, 2);
    $composer = json_decode((string) file_get_contents($core . '/composer.json'), true);

    expect($composer['require'])->not->toHaveKey('dmitry-suffi/redis-session-handler')
        ->and((string) file_get_contents($core . '/functions/preload.php'))->not->toContain('suffi\\');
});
