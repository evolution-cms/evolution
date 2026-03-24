<?php

use EvolutionCMS\AbstractLaravel;
use EvolutionCMS\Core;
use Illuminate\Config\Repository;

function buildConfigLoaderApp(): AbstractLaravel
{
    return (new ReflectionClass(Core::class))->newInstanceWithoutConstructor();
}

function invokeLoadConfiguration(AbstractLaravel $app, Repository $config, string $dir): void
{
    $method = new ReflectionMethod(AbstractLaravel::class, 'loadConfiguration');
    $method->setAccessible(true);
    $method->invoke($app, $config, $dir);
}

function writePhpConfigFile(string $path, string $content): void
{
    $directory = dirname($path);
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    file_put_contents($path, $content);
}

function deleteConfigTestTree(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    if (is_file($path) || is_link($path)) {
        unlink($path);
        return;
    }

    foreach (scandir($path) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        deleteConfigTestTree($path . DIRECTORY_SEPARATOR . $item);
    }

    rmdir($path);
}

beforeEach(function () {
    $this->tempDirs = [];
});

afterEach(function () {
    foreach ($this->tempDirs as $dir) {
        deleteConfigTestTree($dir);
    }
});

function makeTempConfigRoot(object $testCase, string $suffix): string
{
    $root = sys_get_temp_dir() . '/evo-config-' . $suffix . '-' . bin2hex(random_bytes(5));
    mkdir($root, 0775, true);
    $testCase->tempDirs[] = $root;

    return $root;
}

test('loadConfiguration reads valid split custom settings files', function () {
    $root = makeTempConfigRoot($this, 'valid');
    $configRoot = $root . '/custom/config';
    $namespace = "EvolutionCMS\\Main\\Controllers\\";

    writePhpConfigFile(
        $configRoot . '/cms/settings/ControllerNamespace.php',
        '<?php return ' . var_export($namespace, true) . ';' . PHP_EOL
    );

    $config = new Repository([]);

    invokeLoadConfiguration(buildConfigLoaderApp(), $config, $configRoot);

    expect($config->get('cms.settings.ControllerNamespace'))->toBe($namespace)
        ->and($config->get('cms.settings'))->toBe(['ControllerNamespace' => $namespace]);
});

test('loadConfiguration skips invalid custom config files without killing bootstrap', function () {
    $root = makeTempConfigRoot($this, 'broken-custom');
    $configRoot = $root . '/custom/config';

    writePhpConfigFile(
        $configRoot . '/cms/settings/ControllerNamespace.php',
        <<<'PHP'
<?php return "EvolutionCMS\Main\Controllers\";
PHP
        . PHP_EOL
    );

    $config = new Repository([
        'cms' => [
            'settings' => [
                'site_name' => 'Demo',
            ],
        ],
    ]);

    invokeLoadConfiguration(buildConfigLoaderApp(), $config, $configRoot);

    expect($config->get('cms.settings.site_name'))->toBe('Demo')
        ->and($config->get('cms.settings.ControllerNamespace'))->toBeNull();
});

test('loadConfiguration still throws for invalid non-custom config files', function () {
    $root = makeTempConfigRoot($this, 'broken-core');
    $configRoot = $root . '/config';

    writePhpConfigFile(
        $configRoot . '/app.php',
        <<<'PHP'
<?php return ["name" => "Broken";
PHP
        . PHP_EOL
    );

    $config = new Repository([]);

    expect(fn () => invokeLoadConfiguration(buildConfigLoaderApp(), $config, $configRoot))
        ->toThrow(ParseError::class);
});
