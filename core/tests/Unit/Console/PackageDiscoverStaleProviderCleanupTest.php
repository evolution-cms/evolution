<?php

use EvolutionCMS\Console\Packages\PackageCommand;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

if (!defined('EVO_CORE_PATH')) {
    define('EVO_CORE_PATH', dirname(__DIR__, 3) . '/');
}

function buildStaleProviderCommand(string $configDir): array
{
    $command = (new ReflectionClass(PackageCommand::class))->newInstanceWithoutConstructor();

    $configProperty = new ReflectionProperty(PackageCommand::class, 'configDir');
    $configProperty->setAccessible(true);
    $configProperty->setValue($command, $configDir);

    $buffer = new BufferedOutput();
    $outputProperty = new ReflectionProperty(PackageCommand::class, 'output');
    $outputProperty->setAccessible(true);
    $outputProperty->setValue($command, new OutputStyle(new ArrayInput([]), $buffer));

    return [$command, $buffer];
}

function removeStaleProviderDir(string $dir): void
{
    foreach (glob($dir . '*.php') ?: [] as $file) {
        unlink($file);
    }
    if (is_dir($dir)) {
        rmdir($dir);
    }
}

beforeEach(function () {
    $this->providersDir = sys_get_temp_dir() . '/evo-stale-providers-' . uniqid() . '/';
    mkdir($this->providersDir, 0775, true);
});

afterEach(function () {
    removeStaleProviderDir($this->providersDir);
});

test('package discovery removes provider config files whose class no longer exists', function () {
    file_put_contents($this->providersDir . 'sCommerceServiceProvider.php', "<?php \nreturn Seiger\sCommerce\sCommerceServiceProvider::class;");
    file_put_contents($this->providersDir . '001_MissingServiceProvider.php', "<?php\n// Priority 1\nreturn Vendor\Missing\MissingServiceProvider::class;");
    file_put_contents($this->providersDir . 'Evolution_Auth.php', "<?php \nreturn EvolutionCMS\Providers\AuthServiceProvider::class;");
    file_put_contents($this->providersDir . 'Broken.php', "<?php\nreturn ['not', 'a', 'class'];");

    [$command, $buffer] = buildStaleProviderCommand($this->providersDir);

    $method = new ReflectionMethod(PackageCommand::class, 'cleanupProviders');
    $method->setAccessible(true);
    $method->invoke($command);

    expect(file_exists($this->providersDir . 'sCommerceServiceProvider.php'))->toBeFalse()
        ->and(file_exists($this->providersDir . '001_MissingServiceProvider.php'))->toBeFalse()
        ->and(file_exists($this->providersDir . 'Evolution_Auth.php'))->toBeTrue()
        ->and(file_exists($this->providersDir . 'Broken.php'))->toBeTrue()
        ->and($buffer->fetch())->toContain('Removed stale provider config sCommerceServiceProvider.php');
});

test('package discovery keeps provider files generated during the current run', function () {
    file_put_contents($this->providersDir . 'FreshServiceProvider.php', "<?php \nreturn Vendor\Fresh\FreshServiceProvider::class;");

    [$command] = buildStaleProviderCommand($this->providersDir);

    $discovered = new ReflectionProperty(PackageCommand::class, 'discoveredProviderFiles');
    $discovered->setAccessible(true);
    $discovered->setValue($command, ['FreshServiceProvider.php' => true]);

    $method = new ReflectionMethod(PackageCommand::class, 'cleanupProviders');
    $method->setAccessible(true);
    $method->invoke($command);

    expect(file_exists($this->providersDir . 'FreshServiceProvider.php'))->toBeTrue();
});

test('configured provider registration skips classes that do not exist', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/AbstractLaravel.php');

    expect($source)
        ->toContain('public function registerConfiguredProviders()')
        ->toContain('class_exists($provider)')
        ->toContain('Skipped missing service provider');
});
