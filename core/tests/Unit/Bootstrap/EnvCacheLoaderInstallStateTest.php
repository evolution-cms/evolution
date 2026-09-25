<?php

use EvolutionCMS\Bootstrap\EnvCacheLoader;

test('bootstrap configuration is not cached or reused before installation completes', function () {
    $root = sys_get_temp_dir() . '/evo-bootstrap-install-' . bin2hex(random_bytes(6));
    $cacheDir = $root . '/core/storage/cache';
    mkdir($cacheDir, 0775, true);
    $cachePath = $cacheDir . '/env.php';
    $preInstall = [
        '_evolution_bootstrap_cache' => 2,
        'env_path' => null,
        'environment' => [],
        'configuration' => ['database' => ['connections' => []]],
        'dynamic_files' => [],
    ];
    file_put_contents($cachePath, '<?php return ' . var_export($preInstall, true) . ';');

    try {
        EnvCacheLoader::load($root);
        expect(EnvCacheLoader::configuration())->toBeNull();

        EnvCacheLoader::cacheConfiguration(['database' => ['connections' => ['default' => ['driver' => 'sqlite']]]], []);
        expect((require $cachePath)['configuration'])->toBe($preInstall['configuration']);

        file_put_contents($root . '/core/.install', (string) time());
        EnvCacheLoader::invalidate($root);
        EnvCacheLoader::load($root);
        EnvCacheLoader::cacheConfiguration(['database' => ['connections' => ['default' => ['driver' => 'sqlite']]]], []);

        expect(EnvCacheLoader::configuration()['items']['database']['connections']['default']['driver'])->toBe('sqlite')
            ->and((require $cachePath)['configuration']['database']['connections']['default']['driver'])->toBe('sqlite');
    } finally {
        @unlink($root . '/core/.install');
        @unlink($cachePath);
        @rmdir($cacheDir);
        @rmdir($root . '/core/storage');
        @rmdir($root . '/core');
        @rmdir($root);
    }
});
