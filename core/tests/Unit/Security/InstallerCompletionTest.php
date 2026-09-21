<?php

use EvolutionCMS\Support\InstallerCompletion;

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
