<?php

use EvolutionCMS\Console\Packages\InstallPackageRequireCommand;
use EvolutionCMS\Console\Packages\RemovePackageRequireCommand;
use Illuminate\Container\Container;
use Symfony\Component\Console\Tester\CommandTester;

if (!defined('EVO_CORE_PATH')) {
    define('EVO_CORE_PATH', dirname(__DIR__, 3) . '/');
}

/**
 * Minimal stand-in for the CMS application container. Illuminate\Console\Command
 * resolves its output style and view components through the container and asks it
 * whether the process is a unit test run, which the bare container cannot answer.
 */
final class PackageRequireTestContainer extends Container
{
    public function runningUnitTests(): bool
    {
        return true;
    }
}

function setPackageRequireComposerPath(InstallPackageRequireCommand $command, string $path): void
{
    $reflection = new ReflectionClass(InstallPackageRequireCommand::class);
    $property = $reflection->getProperty('composer');
    $property->setAccessible(true);
    $property->setValue($command, $path);

    $command->setLaravel(new PackageRequireTestContainer());
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
        ->toContain("commandOptionEnabled('no-dev')")
        ->toContain("commandOptionEnabled('optimize-autoloader')")
        ->toContain("commandOptionEnabled('keep-dependencies')")
        ->toContain('return $this->hasCommandOption($name) && (bool) $this->option($name);');
});

test('package install require scopes composer update to the installed package', function () {
    $composer = tempnam(sys_get_temp_dir(), 'evo-composer-');
    file_put_contents($composer, json_encode(['name' => 'evolutioncms/custom', 'require' => []]));

    $command = new InstallPackageRequireCommand();
    setPackageRequireComposerPath($command, $composer);

    $tester = new CommandTester($command);
    $tester->execute([
        'key' => 'evolution-cms/emcp',
        'value' => '*',
        'composer_run' => '0',
        '--no-dev' => true,
    ]);

    expect($command->buildComposerArguments())->toBe([
        'command' => 'update',
        'packages' => ['evolution-cms/emcp'],
        '--with-dependencies' => true,
        '--no-dev' => true,
    ]);

    @unlink($composer);
});

test('package remove require scopes composer update to the removed package', function () {
    $composer = tempnam(sys_get_temp_dir(), 'evo-composer-');
    file_put_contents($composer, json_encode([
        'name' => 'evolutioncms/custom',
        'require' => ['Seiger/sCommerce' => '*', 'seiger/stask' => '*'],
    ]));

    $command = new RemovePackageRequireCommand();
    setPackageRequireComposerPath($command, $composer);

    $tester = new CommandTester($command);
    $tester->execute(['key' => 'sCommerce', 'composer_run' => '0']);

    expect($command->buildComposerArguments())->toBe([
        'command' => 'update',
        'packages' => ['Seiger/sCommerce'],
        '--with-dependencies' => true,
    ]);

    @unlink($composer);
});

test('composer update falls back to a full update when no package was changed', function () {
    $command = new InstallPackageRequireCommand();
    $command->setLaravel(new PackageRequireTestContainer());

    $input = new ReflectionProperty($command, 'input');
    $input->setAccessible(true);
    $input->setValue($command, new \Symfony\Component\Console\Input\ArrayInput(['key' => 'vendor/pkg', 'value' => '*'], $command->getDefinition()));

    expect($command->buildComposerArguments())->toBe(['command' => 'update']);
});

/**
 * Install command whose Composer and boot check are scripted, so the rollback can be
 * exercised without touching the real vendor directory.
 */
final class ScriptedPackageRequireCommand extends InstallPackageRequireCommand
{
    public int $updateExitCode = 0;
    public bool $boots = true;
    public array $composerRuns = [];
    public ?string $lockDuringReinstall = null;

    public function runComposer()
    {
        $this->composerRuns[] = $this->buildComposerArguments();
        // What Composer does to the lock file before it fails.
        file_put_contents($this->composerLock, '{"new":true}');

        return $this->updateExitCode;
    }

    protected function applicationBoots(): bool
    {
        return $this->boots;
    }

    protected function composerProcessCommand(): ?string
    {
        return 'composer';
    }

    protected function runComposerProcess(string $composerCommand, array $arguments): int
    {
        $this->composerRuns[] = $arguments;
        $this->lockDuringReinstall = (string) file_get_contents($this->composerLock);

        return 0;
    }
}

function makeScriptedPackageRequireCommand(string $composer, string $lock): ScriptedPackageRequireCommand
{
    $command = new ScriptedPackageRequireCommand();
    setPackageRequireComposerPath($command, $composer);

    $property = new ReflectionProperty(InstallPackageRequireCommand::class, 'composerLock');
    $property->setAccessible(true);
    $property->setValue($command, $lock);

    return $command;
}

test('a failed composer update restores composer.json and the lock file, then reinstalls from it', function () {
    $composer = tempnam(sys_get_temp_dir(), 'evo-composer-');
    $lock = tempnam(sys_get_temp_dir(), 'evo-lock-');
    $originalComposer = json_encode(['name' => 'evolutioncms/custom', 'require' => ['seiger/sseo' => '*']]);
    file_put_contents($composer, $originalComposer);
    file_put_contents($lock, '{"old":true}');

    $command = makeScriptedPackageRequireCommand($composer, $lock);
    $command->updateExitCode = 2;

    $tester = new CommandTester($command);
    $exitCode = $tester->execute(['key' => 'seiger/sgallery', 'value' => '*', '--no-dev' => true]);

    expect($exitCode)->toBe(2)
        ->and(file_get_contents($composer))->toBe($originalComposer)
        ->and(file_get_contents($lock))->toBe('{"old":true}')
        ->and($command->lockDuringReinstall)->toBe('{"old":true}')
        ->and($command->composerRuns[1])->toBe(['command' => 'install', '--no-dev' => true])
        ->and($tester->getDisplay())->toContain('restoring the previous dependencies');

    @unlink($composer);
    @unlink($lock);
});

test('an update after which the site no longer boots is rolled back as well', function () {
    $composer = tempnam(sys_get_temp_dir(), 'evo-composer-');
    $lock = tempnam(sys_get_temp_dir(), 'evo-lock-');
    file_put_contents($composer, json_encode(['name' => 'evolutioncms/custom', 'require' => []]));
    file_put_contents($lock, '{"old":true}');

    $command = makeScriptedPackageRequireCommand($composer, $lock);
    $command->boots = false;

    $tester = new CommandTester($command);
    $exitCode = $tester->execute(['key' => 'seiger/sgallery', 'value' => '*']);

    $composerData = json_decode((string) file_get_contents($composer), true);

    expect($exitCode)->toBe(1)
        ->and($composerData['require'])->not->toHaveKey('seiger/sgallery')
        ->and(file_get_contents($lock))->toBe('{"old":true}')
        ->and($command->composerRuns[1])->toBe(['command' => 'install'])
        ->and($tester->getDisplay())->toContain('no longer boots');

    @unlink($composer);
    @unlink($lock);
});

test('a successful update keeps the new requirement and lock file', function () {
    $composer = tempnam(sys_get_temp_dir(), 'evo-composer-');
    $lock = tempnam(sys_get_temp_dir(), 'evo-lock-');
    file_put_contents($composer, json_encode(['name' => 'evolutioncms/custom', 'require' => []]));
    file_put_contents($lock, '{"old":true}');

    $command = makeScriptedPackageRequireCommand($composer, $lock);

    $exitCode = (new CommandTester($command))->execute(['key' => 'seiger/sgallery', 'value' => '1.5.2']);

    $composerData = json_decode((string) file_get_contents($composer), true);

    expect($exitCode)->toBe(0)
        ->and($composerData['require'])->toBe(['seiger/sgallery' => '1.5.2'])
        ->and(file_get_contents($lock))->toBe('{"new":true}')
        ->and($command->composerRuns)->toHaveCount(1);

    @unlink($composer);
    @unlink($lock);
});

test('composer update asks for minimal changes when the composer knows the option', function () {
    $composer = tempnam(sys_get_temp_dir(), 'evo-composer-');
    file_put_contents($composer, json_encode(['name' => 'evolutioncms/custom', 'require' => []]));

    $command = new InstallPackageRequireCommand();
    setPackageRequireComposerPath($command, $composer);
    (new CommandTester($command))->execute(['key' => 'seiger/sgallery', 'value' => '*', 'composer_run' => '0']);

    expect($command->buildComposerArguments(true))->toBe([
        'command' => 'update',
        'packages' => ['seiger/sgallery'],
        '--with-dependencies' => true,
        '--minimal-changes' => true,
    ]);

    @unlink($composer);
});

test('keep-dependencies leaves the dependencies of an uploaded package alone', function () {
    $composer = tempnam(sys_get_temp_dir(), 'evo-composer-');
    file_put_contents($composer, json_encode(['name' => 'evolutioncms/custom', 'require' => []]));

    $command = new InstallPackageRequireCommand();
    setPackageRequireComposerPath($command, $composer);
    (new CommandTester($command))->execute([
        'key' => 'seiger/sgallery',
        'value' => '1.5.2',
        'composer_run' => '0',
        '--keep-dependencies' => true,
    ]);

    expect($command->buildComposerArguments())->toBe([
        'command' => 'update',
        'packages' => ['seiger/sgallery'],
    ]);

    @unlink($composer);
});

test('composer arguments become an escaped non-interactive command line', function () {
    $command = new InstallPackageRequireCommand();

    $line = $command->buildComposerShellArguments([
        'command' => 'update',
        'packages' => ['seiger/sgallery', 'vendor/with space'],
        '--with-dependencies' => true,
        '--no-dev' => true,
        '--optimize-autoloader' => false,
    ]);

    expect($line)->toBe(implode(' ', [
        escapeshellarg('update'),
        escapeshellarg('seiger/sgallery'),
        escapeshellarg('vendor/with space'),
        '--with-dependencies',
        '--no-dev',
        '--no-interaction',
        '--no-ansi',
    ]));
});

test('minimal changes is used only from composer 2.7 on', function (?string $version, bool $expected) {
    expect((new InstallPackageRequireCommand())->supportsMinimalChanges($version))->toBe($expected);
})->with([
    [null, false],
    ['2.6.6', false],
    ['2.7.0', true],
    ['2.8.12', true],
    ['3.0', true],
]);

test('composer version is read from the version banner', function (string $output, ?string $expected) {
    expect(InstallPackageRequireCommand::parseComposerVersion($output))->toBe($expected);
})->with([
    ['Composer version 2.8.12 2025-09-19 13:41:59', '2.8.12'],
    ["PHP version 8.4.1\nComposer version 2.7.0 2024-02-08", '2.7.0'],
    ['Composer 2.2.18', '2.2.18'],
    ['command not found', null],
]);

test('autoload registration rebuilds the autoloader instead of updating every package', function () {
    $command = new \EvolutionCMS\Console\Packages\InstallPackageAutoloadCommand();

    expect($command->buildComposerArguments(true))->toBe(['command' => 'dump-autoload']);
});

test('composer runs in-process only when no process can be started', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/Packages/InstallPackageRequireCommand.php');
    $runComposer = substr($source, strpos($source, 'public function runComposer()'), 1200);

    expect($runComposer)
        ->toContain('$composerCommand = $this->composerProcessCommand();')
        ->toContain('if ($composerCommand === null) {')
        ->toContain('return $this->runComposerProcess($composerCommand, $arguments);')
        ->and(substr_count($source, 'new Application()'))->toBe(1)
        ->and($source)->toContain('if (!ExecWithFallback::anyAvailable()) {');
});
