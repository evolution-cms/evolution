<?php

use EvolutionCMS\Bootstrap\EnvCacheLoader;

test('writing a missing bootstrap cache leaves no PHP warning', function (bool $withEnvFile) {
    $root = sys_get_temp_dir() . '/evo-bootstrap-permissions-' . bin2hex(random_bytes(6));
    $cacheDir = $root . '/core/storage/cache';
    $cachePath = $cacheDir . '/env.php';
    mkdir($cacheDir, 0775, true);
    touch($root . '/core/.install');
    if ($withEnvFile) {
        file_put_contents($root . '/.env', "# Empty environment\n");
    }

    try {
        // Let PHP record suppressed warnings, as it does before the CMS installs its error handler.
        set_error_handler(static fn (): bool => false);
        try {
            error_clear_last();
            EnvCacheLoader::load($root);
            if (!$withEnvFile) {
                EnvCacheLoader::cacheConfiguration(['probe' => true], []);
            }
            $lastError = error_get_last();
        } finally {
            restore_error_handler();
            error_clear_last();
        }

        expect($lastError)->toBeNull()
            ->and(is_file($cachePath))->toBeTrue();
        $cache = require $cachePath;
        if ($withEnvFile) {
            expect($cache)->toBe([]);
        } else {
            expect($cache['configuration'])->toBe(['probe' => true]);
        }
    } finally {
        EnvCacheLoader::invalidate($root);
        if ($withEnvFile) {
            unlink($root . '/.env');
        }
        unlink($root . '/core/.install');
        rmdir($cacheDir);
        rmdir($root . '/core/storage');
        rmdir($root . '/core');
        rmdir($root);
    }
})->with([
    'configuration without a .env file' => [false],
    'environment from a .env file' => [true],
]);
