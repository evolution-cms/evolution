<?php

use EvolutionCMS\Support\InstallerCompletion;
use Symfony\Component\Process\Process;

require_once dirname(__DIR__, 3) . '/src/Support/InstallerCompletion.php';

test('only a fresh completion token from the same address authorizes installer removal', function () {
    $now = 1_800_000_000;
    $token = str_repeat('a', 64);
    $lock = [
        'ip' => '203.0.113.10',
        'timestamp' => $now - 10,
        'token' => $token,
    ];

    expect(InstallerCompletion::matches($lock, $token, '203.0.113.10', $now, 1440))->toBeTrue()
        ->and(InstallerCompletion::matches($lock, str_repeat('b', 64), '203.0.113.10', $now, 1440))->toBeFalse()
        ->and(InstallerCompletion::matches($lock, $token, '203.0.113.11', $now, 1440))->toBeFalse()
        ->and(InstallerCompletion::matches($lock, $token, '203.0.113.10', $now + 1441, 1440))->toBeFalse();
});

test('a generic installer lock is not a removal capability', function () {
    $lockFile = tempnam(sys_get_temp_dir(), 'evo-install-lock-');
    file_put_contents(
        $lockFile,
        "<?php\n\$install_session = 'session-id';\n"
        . "\$install_ip = '203.0.113.10';\n"
        . "\$install_timestamp = 1800000000;\n"
    );

    try {
        expect(InstallerCompletion::readLock($lockFile))->toBeNull();
    } finally {
        unlink($lockFile);
    }
});

test('a completed installer lock exposes only removal authorization fields', function () {
    $lockFile = tempnam(sys_get_temp_dir(), 'evo-install-lock-');
    $token = str_repeat('c', 64);
    file_put_contents(
        $lockFile,
        "<?php\n\$install_session = 'session-id';\n"
        . "\$install_ip = '::1';\n"
        . "\$install_timestamp = 1800000000;\n"
        . "\$install_token = '$token';\n"
    );

    try {
        expect(InstallerCompletion::readLock($lockFile))->toBe([
            'ip' => '::1',
            'timestamp' => 1_800_000_000,
            'token' => $token,
        ]);
    } finally {
        unlink($lockFile);
    }
});

function installerLockFixture(
    string $session,
    string $ip,
    int $timestamp,
    ?string $token = null,
    ?string $grant = null
): array {
    return ['session' => $session, 'ip' => $ip, 'timestamp' => $timestamp, 'token' => $token, 'grant' => $grant];
}

function installerLockPath(): string
{
    $lockFile = tempnam(sys_get_temp_dir(), 'evo-install-lock-');
    unlink($lockFile);

    return $lockFile;
}

test('an installed site keeps the installer shut to every guest', function () {
    $now = 1_800_000_000;
    $grant = hash('sha256', str_repeat('e', 64));

    expect(InstallerCompletion::decide(null, true, 'guest', '198.51.100.7', null, $now, 1440))
        ->toBe(InstallerCompletion::DENY)
        // someone else's live installer session
        ->and(InstallerCompletion::decide(
            installerLockFixture('installer', '198.51.100.7', $now), true, 'guest', '198.51.100.7', null, $now, 1440
        ))->toBe(InstallerCompletion::DENY)
        // an expired lock is not taken over, not even by its own session
        ->and(InstallerCompletion::decide(
            installerLockFixture('guest', '198.51.100.7', $now - 2000), true, 'guest', '198.51.100.7', null, $now, 1440
        ))->toBe(InstallerCompletion::DENY)
        // an administrator's grant: the administrator's address and session are not enough
        ->and(InstallerCompletion::decide(
            installerLockFixture('', '203.0.113.10', $now, null, $grant), true, 'guest', '203.0.113.10', null, $now, 1440
        ))->toBe(InstallerCompletion::DENY)
        ->and(InstallerCompletion::decide(
            installerLockFixture('admin', '203.0.113.10', $now, null, $grant),
            true,
            'admin',
            '203.0.113.10',
            str_repeat('f', 64),
            $now,
            1440
        ))->toBe(InstallerCompletion::DENY);
});

test('an installed site lets in the granted browser and the session that installed it', function () {
    $now = 1_800_000_000;
    $secret = str_repeat('e', 64);
    $grant = hash('sha256', $secret);

    expect(InstallerCompletion::decide(
        installerLockFixture('', '203.0.113.10', $now, null, $grant), true, 'any', '198.51.100.7', $secret, $now, 1440
    ))->toBe(InstallerCompletion::PROCEED)
        ->and(InstallerCompletion::decide(
            installerLockFixture('installer', '203.0.113.10', $now), true, 'installer', '203.0.113.10', null, $now, 1440
        ))->toBe(InstallerCompletion::PROCEED)
        ->and(InstallerCompletion::decide(
            installerLockFixture('installer', '203.0.113.10', $now), true, 'installer', '198.51.100.7', null, $now, 1440
        ))->toBe(InstallerCompletion::DENY)
        // an expired grant does not open it
        ->and(InstallerCompletion::decide(
            installerLockFixture('', '203.0.113.10', $now - 2000, null, $grant), true, 'any', '203.0.113.10', $secret, $now, 1440
        ))->toBe(InstallerCompletion::DENY);
});

test('a site that is not installed yet keeps the first-visitor installer lock', function () {
    $now = 1_800_000_000;

    expect(InstallerCompletion::decide(null, false, 'first', '198.51.100.7', null, $now, 1440))
        ->toBe(InstallerCompletion::CLAIM)
        ->and(InstallerCompletion::decide(
            installerLockFixture('first', '198.51.100.7', $now - 2000), false, 'second', '198.51.100.8', null, $now, 1440
        ))->toBe(InstallerCompletion::CLAIM)
        ->and(InstallerCompletion::decide(
            installerLockFixture('first', '198.51.100.7', $now), false, 'second', '198.51.100.7', null, $now, 1440
        ))->toBe(InstallerCompletion::DENY)
        ->and(InstallerCompletion::decide(
            installerLockFixture('first', '127.0.0.1', $now), false, 'second', '::1', null, $now, 1440
        ))->toBe(InstallerCompletion::CLAIM);
});

test('the installer is installed exactly when the front controller marker exists', function () {
    $core = sys_get_temp_dir() . '/evo-install-marker-' . bin2hex(random_bytes(4));
    mkdir($core);

    try {
        expect(InstallerCompletion::isInstalled($core))->toBeFalse();
        touch($core . '/.install');
        expect(InstallerCompletion::isInstalled($core . '/'))->toBeTrue();
    } finally {
        @unlink($core . '/.install');
        rmdir($core);
    }
});

test('an administrator grant lives in the browser and is renewed only for that browser', function () {
    $lockFile = installerLockPath();
    $now = 1_800_000_000;

    try {
        $secret = InstallerCompletion::grantToAdministrator($lockFile, '203.0.113.10', null, $now, 1440);
        expect($secret)->toMatch('/\A[0-9a-f]{64}\z/')
            ->and(InstallerCompletion::readSessionLock($lockFile))
            ->toBe(installerLockFixture('', '203.0.113.10', $now, null, hash('sha256', $secret)))
            // the lock file, which sits in the web root, never holds the secret itself
            ->and(file_get_contents($lockFile))->not->toContain($secret);

        // the granted browser enters the installer, which binds the lock to its session
        expect(InstallerCompletion::admit($lockFile, true, 'installer', '203.0.113.10', $secret, $now, 1440))
            ->toBe(InstallerCompletion::PROCEED);

        // renewals keep the session and are throttled
        expect(InstallerCompletion::grantToAdministrator($lockFile, '203.0.113.10', $secret, $now + 30, 1440))
            ->toBe($secret)
            ->and(InstallerCompletion::readSessionLock($lockFile)['timestamp'])->toBe($now);
        expect(InstallerCompletion::grantToAdministrator($lockFile, '203.0.113.10', $secret, $now + 120, 1440))
            ->toBe($secret)
            ->and(InstallerCompletion::readSessionLock($lockFile))
            ->toBe(installerLockFixture('installer', '203.0.113.10', $now + 120, null, hash('sha256', $secret)));

        // another administrator does not break into a running installer session
        expect(InstallerCompletion::grantToAdministrator($lockFile, '198.51.100.7', null, $now + 130, 1440))
            ->toBeNull()
            ->and(InstallerCompletion::readSessionLock($lockFile)['timestamp'])->toBe($now + 120);

        expect(InstallerCompletion::grantToAdministrator($lockFile, '198.51.100.7', null, $now + 2000, 1440))
            ->not->toBe($secret);
    } finally {
        @unlink($lockFile);
    }
});

test('an administrator visit replaces an expired lock instead of reviving it', function () {
    $lockFile = installerLockPath();
    $now = 1_800_000_000;
    file_put_contents(
        $lockFile,
        "<?php\n\$install_session = 'guest-session';\n\$install_ip = '203.0.113.10';\n"
        . "\$install_timestamp = " . ($now - 2000) . ";\n"
    );

    try {
        expect(InstallerCompletion::admit($lockFile, true, 'guest-session', '203.0.113.10', null, $now, 1440))
            ->toBe(InstallerCompletion::DENY);

        $secret = InstallerCompletion::grantToAdministrator($lockFile, '203.0.113.10', null, $now, 1440);

        // the guest shares the administrator's address and keeps its old session: still shut out
        expect(InstallerCompletion::admit($lockFile, true, 'guest-session', '203.0.113.10', null, $now + 1, 1440))
            ->toBe(InstallerCompletion::DENY)
            ->and(InstallerCompletion::admit($lockFile, true, 'admin-session', '203.0.113.10', $secret, $now + 1, 1440))
            ->toBe(InstallerCompletion::PROCEED);
    } finally {
        @unlink($lockFile);
    }
});

test('completing the installation keeps the administrator grant', function () {
    $lockFile = installerLockPath();
    $now = time();
    $token = str_repeat('d', 64);

    try {
        $secret = InstallerCompletion::grantToAdministrator($lockFile, '203.0.113.10', null, $now, 1440);
        InstallerCompletion::admit($lockFile, true, 'installer', '203.0.113.10', $secret, $now, 1440);

        expect(InstallerCompletion::writeLock($lockFile, 'installer', '203.0.113.10', $now, $token))->toBeTrue()
            ->and(InstallerCompletion::readLock($lockFile)['token'])->toBe($token)
            ->and(InstallerCompletion::admit($lockFile, true, 'installer', '203.0.113.10', $secret, $now, 1440))
            ->toBe(InstallerCompletion::PROCEED)
            ->and(InstallerCompletion::readLock($lockFile)['token'])->toBe($token)
            ->and(InstallerCompletion::admit($lockFile, true, 'other', '203.0.113.10', null, $now, 1440))
            ->toBe(InstallerCompletion::DENY);
    } finally {
        @unlink($lockFile);
    }
});

test('a refused guest leaves no lock file and an unwritable lock admits nobody', function () {
    $lockFile = installerLockPath();

    expect(InstallerCompletion::admit($lockFile, true, 'guest', '198.51.100.7', null, 1_800_000_000, 1440))
        ->toBe(InstallerCompletion::DENY)
        ->and(is_file($lockFile))->toBeFalse();

    $unwritable = sys_get_temp_dir() . '/evo-missing-' . bin2hex(random_bytes(4)) . '/install.session.php';
    expect(InstallerCompletion::admit($unwritable, false, 'first', '198.51.100.7', null, 1_800_000_000, 1440))
        ->toBeNull()
        ->and(InstallerCompletion::grantToAdministrator($unwritable, '203.0.113.10', null, 1_800_000_000, 1440))
        ->toBeNull();
});

test('concurrent installer requests take a free lock exactly once', function () {
    $lockFile = installerLockPath();
    $class = str_replace('\\', '/', dirname(__DIR__, 3)) . '/src/Support/InstallerCompletion.php';
    $start = microtime(true) + 1.5;
    $processes = [];

    try {
        for ($i = 0; $i < 8; $i++) {
            $code = 'require ' . var_export($class, true) . ';'
                . 'time_sleep_until(' . $start . ');'
                . 'echo EvolutionCMS\Support\InstallerCompletion::admit('
                . var_export($lockFile, true) . ", false, 'session-$i', '198.51.100.7', null, time(), 1440);";
            $process = new Process([PHP_BINARY, '-r', $code]);
            $process->start();
            $processes[] = $process;
        }

        $decisions = [];
        foreach ($processes as $process) {
            $process->wait();
            $decisions[] = trim($process->getOutput());
        }

        $counts = array_count_values($decisions);
        ksort($counts);

        expect($counts)->toBe([
            InstallerCompletion::CLAIM => 1,
            InstallerCompletion::DENY => 7,
        ]);
    } finally {
        @unlink($lockFile);
    }
});

test('a session lock written with quotes in it reads back verbatim', function () {
    $lockFile = installerLockPath();

    try {
        InstallerCompletion::admit($lockFile, false, "a'b\\", '::1', null, 5, 1440);
        expect(InstallerCompletion::readSessionLock($lockFile))
            ->toBe(installerLockFixture("a'b\\", '::1', 5));
    } finally {
        @unlink($lockFile);
    }
});

test('a lock file is parsed, never executed, so a cached copy cannot hide a claim', function () {
    $lockFile = installerLockPath();
    file_put_contents(
        $lockFile,
        "<?php\nthrow new RuntimeException('executed');\n"
        . "\$install_session = 'it\'s';\n"
        . "\$install_ip = '203.0.113.10';\n"
        . "\$install_timestamp = 42;\n"
    );

    try {
        expect(InstallerCompletion::readSessionLock($lockFile))
            ->toBe(installerLockFixture("it's", '203.0.113.10', 42));

        // rewritten twice within the same second: the second read sees the second write
        InstallerCompletion::admit($lockFile, false, 'first', '203.0.113.10', null, 5000, 1440);
        expect(InstallerCompletion::readSessionLock($lockFile)['session'])->toBe('first');
        InstallerCompletion::admit($lockFile, false, 'first', '203.0.113.10', null, 5000, 1440);
        InstallerCompletion::admit($lockFile, false, 'second', '203.0.113.11', null, 5000, 1440);
        expect(InstallerCompletion::readSessionLock($lockFile)['session'])->toBe('first');
    } finally {
        @unlink($lockFile);
    }
});

/**
 * The grant cookie a browser sends with a request: RFC 6265 path matching of the stored copies.
 *
 * @param array<string, string> $jar cookie path => value
 */
function installerGrantCookieFor(array $jar, string $requestPath): ?string
{
    foreach ($jar as $path => $value) {
        if (str_starts_with($requestPath, $path)) {
            return $value;
        }
    }

    return null;
}

test('the grant cookie reaches the manager, so working in the manager keeps the grant alive', function (
    string $baseUrl,
    string $managerDir
) {
    $lockFile = installerLockPath();
    $now = 1_800_000_000;
    $base = rtrim($baseUrl, '/') . '/';
    $managerRequest = $base . $managerDir . '/index.php';
    $installerRequest = $base . 'install/index.php';

    try {
        $secret = InstallerCompletion::grantToAdministrator($lockFile, '203.0.113.10', null, $now, 1440);
        $jar = array_fill_keys(InstallerCompletion::grantCookiePaths($baseUrl, $managerDir), $secret);

        expect(installerGrantCookieFor($jar, $base . 'index.php'))->toBeNull();

        // twenty minutes in the manager, then the installer after the first grant's lifetime
        for ($minute = 5; $minute <= 20; $minute += 5) {
            $sent = installerGrantCookieFor($jar, $managerRequest);
            expect(InstallerCompletion::grantToAdministrator($lockFile, '203.0.113.10', $sent, $now + $minute * 60, 1440))
                ->toBe($secret);
        }

        expect(InstallerCompletion::readSessionLock($lockFile)['timestamp'])->toBe($now + 1200)
            ->and(InstallerCompletion::admit(
                $lockFile,
                true,
                'installer',
                '203.0.113.10',
                installerGrantCookieFor($jar, $installerRequest),
                $now + 1500,
                1440
            ))->toBe(InstallerCompletion::PROCEED);
    } finally {
        @unlink($lockFile);
    }
})->with([
    'site at the root' => ['/', 'manager'],
    'site in a folder, renamed manager' => ['/site/', 'admin'],
]);
