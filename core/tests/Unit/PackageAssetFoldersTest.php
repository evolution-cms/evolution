<?php

use EvolutionCMS\Support\PackageAssetFolders;
use Illuminate\Filesystem\Filesystem;

function packageAssetsSite(): string
{
    $site = sys_get_temp_dir() . '/evo-package-assets-' . getmypid() . '-' . uniqid();
    mkdir($site . '/assets/plugins', 0777, true);
    mkdir($site . '/vendor/acme/editor/public', 0777, true);

    return str_replace('\\', '/', $site);
}

test('the folders packages publish into are found among the publish targets', function () {
    $site = packageAssetsSite();
    $assets = $site . '/assets';

    try {
        $folders = PackageAssetFolders::folders([
            $site . '/vendor/acme/editor/public/editor.js' => $assets . '/plugins/acme.editor/editor.js',
            $site . '/vendor/acme/editor/public/theme/a.css' => $assets . '/plugins/acme.editor/theme/a.css',
            'symlink:' . $site . '/vendor/acme/ui/ui.css' => $assets . '/modules/acme-ui/ui.css',
            $site . '/vendor/acme/editor/public' => $assets . '/snippets/acme-snippet',
            $site . '/vendor/acme/editor/noimage.png' => $assets . '/plugins/noimage.png',
            $site . '/vendor/acme/gallery/noimage.png' => $assets . '/site/noimage.png',
            $site . '/vendor/acme/editor/config.php' => $site . '/core/custom/config/acme.php',
            $site . '/vendor/acme/editor/evil.js' => $assets . '/plugins/../evil.js',
            $site . '/vendor/acme/editor/x.js' => str_replace('/', '\\', $assets . '/plugins/acme.windows/x.js'),
        ], $assets);

        expect($folders)->toBe([
            $assets . '/plugins/acme.editor',
            $assets . '/modules/acme-ui',
            $assets . '/snippets/acme-snippet',
            $assets . '/plugins/acme.windows',
        ]);
    } finally {
        (new Filesystem())->deleteDirectory($site);
    }
});

test('each package folder gets a .gitignore that ignores it, created along with the folder', function () {
    $site = packageAssetsSite();
    $assets = $site . '/assets';

    try {
        $written = PackageAssetFolders::ignore([
            $site . '/vendor/acme/editor/public/editor.js' => $assets . '/plugins/acme.editor/editor.js',
            'symlink:' . $site . '/vendor/acme/ui/ui.css' => $assets . '/modules/acme-ui/ui.css',
        ], $assets);

        expect($written)->toBe([$assets . '/plugins/acme.editor/.gitignore', $assets . '/modules/acme-ui/.gitignore'])
            ->and(file_get_contents($assets . '/plugins/acme.editor/.gitignore'))->toBe(PackageAssetFolders::GITIGNORE)
            ->and(PackageAssetFolders::GITIGNORE)->toEndWith("\n*\n")
            ->and(is_dir($assets . '/modules/acme-ui'))->toBeTrue();
    } finally {
        (new Filesystem())->deleteDirectory($site);
    }
});

test('a folder that has a .gitignore keeps it, and a linked folder is left alone', function () {
    $site = packageAssetsSite();
    $assets = $site . '/assets';
    mkdir($assets . '/plugins/acme.editor');
    file_put_contents($assets . '/plugins/acme.editor/.gitignore', "custom\n");
    mkdir($site . '/elsewhere');
    $linked = @symlink($site . '/elsewhere', $assets . '/plugins/acme.linked');

    try {
        $written = PackageAssetFolders::ignore([
            $site . '/vendor/acme/editor/public/editor.js' => $assets . '/plugins/acme.editor/editor.js',
            $site . '/vendor/acme/linked/a.js' => $assets . '/plugins/acme.linked/a.js',
        ], $assets);

        expect($written)->toBe([])
            ->and(file_get_contents($assets . '/plugins/acme.editor/.gitignore'))->toBe("custom\n");
        if ($linked) {
            expect(file_exists($site . '/elsewhere/.gitignore'))->toBeFalse();
        }
    } finally {
        if ($linked) {
            @unlink($assets . '/plugins/acme.linked');
        }
        (new Filesystem())->deleteDirectory($site);
    }
});

test('every artisan run ignores the package folders once all providers have booted', function () {
    $console = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Console.php');
    $hook = 'Support\PackageAssetFolders::ignore(\Illuminate\Support\ServiceProvider::pathsToPublish(), public_path(\'assets\'));';

    expect($console)->toContain($hook)
        ->and(strpos($console, '$laravel->loadDeferredProviders();'))->toBeLessThan(strpos($console, $hook))
        ->and(strpos($console, $hook))->toBeLessThan(strpos($console, 'parent::bootstrap();'));
});
