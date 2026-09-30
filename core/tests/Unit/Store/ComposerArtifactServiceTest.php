<?php

use EvolutionCMS\Services\Store\ComposerArtifactService;

/**
 * Build a zip archive from a map of entry name => contents.
 */
function makeArtifactTestZip(array $entries): string
{
    $path = tempnam(sys_get_temp_dir(), 'evo-artifact-') . '.zip';
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($entries as $name => $contents) {
        $zip->addFromString($name, $contents);
    }
    $zip->close();

    return $path;
}

function makeArtifactTestCore(): string
{
    $core = sys_get_temp_dir() . '/evo-artifact-core-' . uniqid();
    mkdir($core . '/custom', 0777, true);

    return $core;
}

function removeArtifactTestDir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}

test('inspect reads a package from composer.json at the archive root', function () {
    $zip = makeArtifactTestZip([
        'composer.json' => json_encode(['name' => 'seiger/sgallery', 'version' => '1.5.2']),
        'src/Gallery.php' => '<?php',
    ]);

    $package = (new ComposerArtifactService(sys_get_temp_dir()))->inspect($zip, 'upload.zip');

    expect($package['name'])->toBe('seiger/sgallery')
        ->and($package['version'])->toBe('1.5.2')
        ->and($package['entry'])->toBe('composer.json');

    @unlink($zip);
});

test('inspect finds composer.json inside the only top-level directory, as in a GitHub download', function () {
    $zip = makeArtifactTestZip([
        'sgallery-1.5.2/composer.json' => json_encode(['name' => 'seiger/sgallery']),
        'sgallery-1.5.2/src/Gallery.php' => '<?php',
    ]);

    $package = (new ComposerArtifactService(sys_get_temp_dir()))->inspect($zip, 'sgallery-v1.5.2.zip');

    expect($package['name'])->toBe('seiger/sgallery')
        ->and($package['version'])->toBe('1.5.2')
        ->and($package['entry'])->toBe('sgallery-1.5.2/composer.json');

    @unlink($zip);
});

test('inspect reports an unknown version as empty instead of guessing', function () {
    $zip = makeArtifactTestZip([
        'composer.json' => json_encode(['name' => 'seiger/sgallery']),
    ]);

    $package = (new ComposerArtifactService(sys_get_temp_dir()))->inspect($zip, 'sgallery.zip');

    expect($package['version'])->toBe('');

    @unlink($zip);
});

test('inspect leaves legacy extras with an install directory to the legacy installer', function () {
    $zip = makeArtifactTestZip([
        'mypackage/composer.json' => json_encode(['name' => 'vendor/mypackage']),
        'mypackage/install/assets/snippets/my.tpl' => '//',
    ]);

    expect((new ComposerArtifactService(sys_get_temp_dir()))->inspect($zip, 'mypackage.zip'))->toBeNull();

    @unlink($zip);
});

test('inspect ignores archives that are not shaped like a composer package', function (array $entries) {
    $zip = makeArtifactTestZip($entries);

    expect((new ComposerArtifactService(sys_get_temp_dir()))->inspect($zip, 'x-1.0.0.zip'))->toBeNull();

    @unlink($zip);
})->with([
    'no composer.json' => [['assets/snippets/x.php' => '<?php']],
    'composer.json below two top-level dirs' => [['a/composer.json' => json_encode(['name' => 'a/b']), 'b/readme.md' => '']],
]);

test('inspect flags a composer package whose composer.json cannot be used', function (string $composerJson) {
    $zip = makeArtifactTestZip(['pkg/composer.json' => $composerJson, 'pkg/src/A.php' => '<?php']);

    $package = (new ComposerArtifactService(sys_get_temp_dir()))->inspect($zip, 'pkg-1.0.0.zip');

    expect($package['invalid'])->toBeTrue()
        ->and($package['entry'])->toBe('pkg/composer.json')
        ->and($package['name'])->toBe('');

    @unlink($zip);
})->with([
    'no name' => [json_encode(['require' => []])],
    'invalid name' => [json_encode(['name' => 'Not A Package'])],
    'broken json' => ['{"autoload": {"psr-4": {"Demo\Hello\\": "src/"}}}'],
]);

test('a valid package is not flagged as invalid', function () {
    $zip = makeArtifactTestZip(['composer.json' => json_encode(['name' => 'seiger/sgallery', 'version' => '1.5.2'])]);

    expect((new ComposerArtifactService(sys_get_temp_dir()))->inspect($zip, 'x.zip')['invalid'])->toBeFalse();

    @unlink($zip);
});

test('store keeps the archive, writes a missing version into it and registers the repository once', function () {
    $core = makeArtifactTestCore();
    file_put_contents($core . '/custom/composer.json', json_encode([
        'name' => 'evolutioncms/custom',
        'require' => ['seiger/sseo' => '*'],
    ]));
    $zip = makeArtifactTestZip([
        'sgallery-main/composer.json' => json_encode(['name' => 'seiger/sgallery', 'type' => 'library']),
    ]);

    $service = new ComposerArtifactService($core);
    $package = $service->inspect($zip, 'sgallery-1.5.2.zip');
    $stored = $service->store($zip, $package);
    $service->store($zip, $package);

    $archive = new ZipArchive();
    $archive->open($stored);
    $composer = json_decode((string) $archive->getFromName('sgallery-main/composer.json'), true);
    $archive->close();

    $custom = json_decode((string) file_get_contents($core . '/custom/composer.json'), true);

    expect(basename($stored))->toBe('seiger-sgallery-1.5.2.zip')
        ->and(dirname($stored))->toBe($core . '/custom/artifacts')
        ->and($composer['version'])->toBe('1.5.2')
        ->and($composer['type'])->toBe('library')
        ->and($custom['require'])->toBe(['seiger/sseo' => '*'])
        ->and($custom['repositories'])->toBe([['type' => 'artifact', 'url' => 'custom/artifacts']]);

    @unlink($zip);
    removeArtifactTestDir($core);
});

test('registerRepository creates custom/composer.json when there is none', function () {
    $core = makeArtifactTestCore();

    (new ComposerArtifactService($core))->registerRepository();

    $custom = json_decode((string) file_get_contents($core . '/custom/composer.json'), true);

    expect($custom['name'])->toBe('evolutioncms/custom')
        ->and($custom['repositories'])->toBe([['type' => 'artifact', 'url' => 'custom/artifacts']]);

    removeArtifactTestDir($core);
});

test('versionFromFileName reads common archive names', function (string $fileName, ?string $expected) {
    expect(ComposerArtifactService::versionFromFileName($fileName))->toBe($expected);
})->with([
    ['sgallery-1.5.2.zip', '1.5.2'],
    ['sgallery-v1.5.2.zip', '1.5.2'],
    ['seiger_sgallery_2.0.zip', '2.0'],
    ['sgallery-2.0.0-beta.1.zip', '2.0.0-beta.1'],
    ['/tmp/x/sgallery-1.0.0.ZIP', '1.0.0'],
    ['sgallery.zip', null],
    ['sgallery-main.zip', null],
    ['sgallery1.5.2.zip', null],
]);
