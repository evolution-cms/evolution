<?php

/*
| With EVO_SESSION off, TracyServiceProvider starts the native session of a request that has none yet.
| A console command has no cookie to come back with, so it gets $_SESSION in the process and no stored
| session: before, every artisan run (each scheduler tick too) left an empty sess_* file behind.
*/

function nativeSessionAtBoot(bool $console): array
{
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'evo-native-session-' . bin2hex(random_bytes(4));
    mkdir($dir);
    try {
        $out = evoRunPhp(dirname(__DIR__) . '/Mocks/native_session_boot_worker.php', [$dir, $console ? '1' : '0']);
        $result = json_decode($out, true);
        expect($result)->toBeArray("worker output: $out");

        return $result;
    } finally {
        array_map('unlink', glob($dir . DIRECTORY_SEPARATOR . '*') ?: []);
        rmdir($dir);
    }
}

test('a console command gets a session in the process and stores none', function () {
    expect(nativeSessionAtBoot(true))->toBe(['active' => false, 'session' => 'array', 'files' => []]);
});

test('a web request still starts its native session', function () {
    $result = nativeSessionAtBoot(false);

    expect($result['active'])->toBeTrue()
        ->and($result['session'])->toBe('array')
        ->and($result['files'])->toHaveCount(1)
        ->and($result['files'][0])->toStartWith('sess_');
});
