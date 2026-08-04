<?php

use EvolutionCMS\Console\Packages\InstallPackageRequireCommand;
use EvolutionCMS\Console\Packages\RemovePackageRequireCommand;
use Symfony\Component\Console\Tester\CommandTester;

if (!defined('EVO_CORE_PATH')) {
    define('EVO_CORE_PATH', dirname(__DIR__, 3) . '/');
}

function setPackageRequireComposerPath(InstallPackageRequireCommand $command, string $path): void
{
    $reflection = new ReflectionClass(InstallPackageRequireCommand::class);
    $property = $reflection->getProperty('composer');
    $property->setAccessible(true);
    $property->setValue($command, $path);
}

test('package remove require reports missing requirements', function () {
    $composer = tempnam(sys_get_temp_dir(), 'evo-composer-');
    file_put_contents($composer, json_encode([
        'name' => 'evolutioncms/custom',
        'require' => [
            'seiger/stask' => '*',
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    $command = new RemovePackageRequireCommand();
    setPackageRequireComposerPath($command, $composer);

    $tester = new CommandTester($command);
    $exitCode = $tester->execute([
        'key' => 'sCommerce',
        'composer_run' => '0',
    ]);

    $composerData = json_decode((string) file_get_contents($composer), true);

    expect($exitCode)->toBe(1)
        ->and($tester->getDisplay())->toContain('Package requirement not found: sCommerce')
        ->and($composerData['require'])->toHaveKey('seiger/stask');

    @unlink($composer);
});

test('package remove require reports removed requirements', function () {
    $composer = tempnam(sys_get_temp_dir(), 'evo-composer-');
    file_put_contents($composer, json_encode([
        'name' => 'evolutioncms/custom',
        'require' => [
            'Seiger/sCommerce' => '*',
            'seiger/stask' => '*',
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    $command = new RemovePackageRequireCommand();
    setPackageRequireComposerPath($command, $composer);

    $tester = new CommandTester($command);
    $exitCode = $tester->execute([
        'key' => 'sCommerce',
        'composer_run' => '0',
    ]);

    $composerData = json_decode((string) file_get_contents($composer), true);

    expect($exitCode)->toBe(0)
        ->and($tester->getDisplay())->toContain('Removed package requirement: Seiger/sCommerce')
        ->and($composerData['require'])->not->toHaveKey('Seiger/sCommerce')
        ->and($composerData['require'])->toHaveKey('seiger/stask');

    @unlink($composer);
});

test('composer option handling is guarded by command signature', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/Packages/InstallPackageRequireCommand.php');

    expect($source)
        ->toContain("hasCommandOption('no-dev')")
        ->toContain("hasCommandOption('optimize-autoloader')");
});
