<?php

/**
 * Without ext-redis PHPStan leaves out the Redis constants of RedisSessionHandler instead of failing,
 * and CI says so in a warning.
 */
function phpstanRedisExtensionConfig(): array
{
    return require dirname(__DIR__) . '/phpstan/redis-extension.php';
}

test('the Redis constants are ignored only without ext-redis', function () {
    $config = phpstanRedisExtensionConfig();

    if (extension_loaded('redis')) {
        expect($config)->toBe([]);

        return;
    }
    $ignored = $config['parameters']['ignoreErrors'];
    expect($ignored)->toHaveCount(1)
        ->and(realpath($ignored[0]['path']))->toBe(realpath(dirname(__DIR__, 2) . '/src/Support/RedisSessionHandler.php'))
        ->and(preg_match($ignored[0]['message'], 'Access to undefined constant Redis::COMPRESSION_NONE.'))->toBe(1)
        ->and(preg_match($ignored[0]['message'], 'Access to undefined constant Redis::OPT_COMPRESSION.'))->toBe(1)
        ->and(preg_match($ignored[0]['message'], 'Access to undefined constant RedisCluster::OPT_COMPRESSION.'))->toBe(0)
        ->and(preg_match($ignored[0]['message'], 'Call to an undefined method Redis::foo().'))->toBe(0);
});

test('PHPStan loads the rule and CI warns about a missing ext-redis', function () {
    $root = dirname(__DIR__, 3);
    if (!is_file($root . '/phpstan.neon') || !is_file($root . '/.github/workflows/ci.yml')) {
        test()->markTestSkipped('No phpstan.neon or .github in this tree.');
    }
    $ci = (string) file_get_contents($root . '/.github/workflows/ci.yml');

    expect((string) file_get_contents($root . '/phpstan.neon'))->toContain("includes:\n    - core/tests/phpstan/redis-extension.php\n")
        ->and(substr_count($ci, "php -r 'exit(extension_loaded(\"redis\") ? 0 : 1);' || echo \"::warning title=No ext-redis on PHP \${{ matrix.php }}::"))->toBe(2);
});
