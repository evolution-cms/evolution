<?php

use EvolutionCMS\Bootstrap\EnvCacheLoader;

test('bootstrap configuration is not cached or reused before installation completes', function () {
    $root = sys_get_temp_dir() . '/evo-bootstrap-install-' . bin2hex(random_bytes(6));
    $cacheDir = $root . '/core/storage/cache';
    mkdir($cacheDir, 0775, true);
    $cachePath = $cacheDir . '/env.php';
    $preInstall = [
        '_evolution_bootstrap_cache' => 3,
        'project_root' => $root,
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

test('cached configuration is rebuilt after a packaged build moves to a new root', function () {
    $base = sys_get_temp_dir() . '/evo-bootstrap-portable-' . bin2hex(random_bytes(6));
    $source = $base . '/source';
    $target = $base . '/target';

    foreach ([$source, $target] as $root) {
        mkdir($root . '/core/storage/cache', 0775, true);
        mkdir($root . '/core/config', 0775, true);
        file_put_contents($root . '/core/.install', 'installed');
        file_put_contents($root . '/core/config/app.php', '<?php return ["name" => "portable"];');
    }

    try {
        EnvCacheLoader::load($source);
        EnvCacheLoader::cacheConfiguration(
            ['filesystems' => ['root' => $source]],
            [['key' => 'app', 'path' => $source . '/core/config/app.php']]
        );

        $cached = require $source . '/core/storage/cache/env.php';
        expect($cached['project_root'])->toBe($source)
            ->and($cached['dynamic_files'])->toBe([
                ['key' => 'app', 'path' => $source . '/core/config/app.php'],
            ]);

        copy($source . '/core/storage/cache/env.php', $target . '/core/storage/cache/env.php');
        EnvCacheLoader::load($target);
        expect(EnvCacheLoader::configuration())->toBeNull();

        EnvCacheLoader::cacheConfiguration(
            ['filesystems' => ['root' => $target]],
            [['key' => 'app', 'path' => $target . '/core/config/app.php']]
        );
        $configuration = EnvCacheLoader::configuration();

        expect($configuration['items']['filesystems']['root'])->toBe($target)
            ->and($configuration['dynamic_files'])->toBe([
                ['key' => 'app', 'path' => $target . '/core/config/app.php'],
            ])
            ->and((require $configuration['dynamic_files'][0]['path'])['name'])->toBe('portable')
            ->and((require $target . '/core/storage/cache/env.php')['project_root'])->toBe($target);
    } finally {
        foreach ([$source, $target] as $root) {
            @unlink($root . '/core/storage/cache/env.php');
            @unlink($root . '/core/config/app.php');
            @unlink($root . '/core/.install');
            @rmdir($root . '/core/storage/cache');
            @rmdir($root . '/core/storage');
            @rmdir($root . '/core/config');
            @rmdir($root . '/core');
            @rmdir($root);
        }
        @rmdir($base);
    }
});
