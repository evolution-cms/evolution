<?php

/*
|--------------------------------------------------------------------------
| A CLI update restores the packages of core/custom/composer.json
|--------------------------------------------------------------------------
|
| The files laid over the site (an archive, composer create-project) bring
| their own composer.lock and vendor, which know nothing of the packages the
| merge plugin adds, so they stopped being autoloaded after a CLI update.
|
| The Composer in core/vendor runs out of the directory it rewrites: when an
| update replaced symfony/finder, symfony/console... it stopped halfway with
| those packages deleted and artisan dead. It now runs only after a dry run
| showed none of its own packages would change; a standalone one goes first.
|
*/

use EvolutionCMS\Services\CustomComposerPackages;

$root = dirname(__DIR__, 4);

function customPackagesCore(array $require, ?array $lock = null, ?array $installed = null): string
{
    $core = sys_get_temp_dir() . '/evo-custom-packages-' . getmypid() . '-' . uniqid() . '/';
    mkdir($core . 'custom', 0777, true);
    mkdir($core . 'vendor/composer', 0777, true);
    file_put_contents($core . 'custom/composer.json', json_encode(['require' => $require]));
    if ($lock !== null) {
        file_put_contents($core . 'composer.lock', json_encode($lock));
    }
    file_put_contents($core . 'vendor/composer/installed.json', json_encode($installed ?? ['packages' => [
        ['name' => 'composer/composer', 'require' => ['php' => '^8.1', 'symfony/finder' => '^7', 'symfony/console' => '^7']],
        ['name' => 'symfony/console', 'require' => ['symfony/string' => '^7']],
        ['name' => 'symfony/finder', 'require' => []],
        ['name' => 'symfony/string', 'require' => []],
        ['name' => 'league/csv', 'require' => []],
    ]]));

    return $core;
}

/**
 * Run restoreCustomPackages() for a core directory.
 *
 * @param array<string, array{0: int, 1: string}> $answers Command fragment => what running it answers.
 */
function restoreCustomPackagesRun(string $root, string $core, ?array $composer, array $answers = [], array $flags = []): array
{
    $probe = tempnam(sys_get_temp_dir(), 'evo_custom_probe_');
    file_put_contents($probe, '<?php'
        . ' define("EVO_CORE_PATH", ' . var_export($core, true) . ');'
        . ' require ' . var_export($root . '/core/vendor/autoload.php', true) . ';'
        . ' require ' . var_export($root . '/install/cli-install.php', true) . ';'
        . ' class RecordingInstall extends InstallEvo {'
        . '   public array $commands = []; public int $discovered = 0; public array $answers = []; public ?array $composer = null;'
        . '   protected function runComposerCommand(string $command): array {'
        . '     $this->commands[] = $command;'
        . '     foreach ($this->answers as $fragment => $answer) { if (str_contains($command, $fragment)) { return $answer; } }'
        . '     return [0, ""];'
        . '   }'
        . '   protected function composerForRestore(): ?array { return $this->composer; }'
        . '   protected function discoverPackages(): void { $this->discovered++; }'
        . ' }'
        . ' $install = (new ReflectionClass(RecordingInstall::class))->newInstanceWithoutConstructor();'
        . ' $install->answers = ' . var_export($answers, true) . ';'
        . ' $install->composer = ' . var_export($composer, true) . ';'
        . ' foreach (' . var_export($flags, true) . ' as $k => $v) { $install->$k = $v; }'
        . ' ob_start(); $install->restoreCustomPackages(); $out = ob_get_clean();'
        . ' echo json_encode(["commands" => $install->commands, "discovered" => $install->discovered, "out" => $out]);');
    $out = trim(evoRunPhp($probe));
    unlink($probe);

    return json_decode($out, true, flags: JSON_THROW_ON_ERROR);
}

function removeCustomPackagesCore(string $core): void
{
    (new Illuminate\Filesystem\Filesystem())->deleteDirectory($core);
}

$standalone = ['command' => 'composer', 'standalone' => true];
$vendorCopy = ['command' => "'php' 'core/vendor/bin/composer'", 'standalone' => false];
$composer210 = ['--version' => [0, 'Composer version 2.10.3 2026-08-27 13:34:23']];

test('a standalone Composer restores the packages without moving what it does not have to', function () use ($root, $standalone, $composer210) {
    $core = customPackagesCore(['php' => '^8.3', 'league/csv' => '^9.0', 'Seiger/sTask' => '*']);

    try {
        $run = restoreCustomPackagesRun($root, $core, $standalone, $composer210);

        expect($run['commands'])->toHaveCount(2)
            ->and($run['commands'][1])->toStartWith('composer update ' . escapeshellarg('league/csv') . ' ' . escapeshellarg('seiger/stask'))
            // core's own requirements (illuminate/*, symfony/*) keep the versions the release ships
            ->and($run['commands'][1])->toContain('--with-dependencies --minimal-changes --no-dev')
            ->and($run['commands'][1])->not->toContain('--with-all-dependencies')
            ->and($run['commands'][1])->not->toContain('--dry-run')
            ->and($run['commands'][1])->toContain('--working-dir=' . escapeshellarg($core))
            ->and($run['discovered'])->toBe(1)
            ->and($run['out'])->toContain('Custom Composer packages restored');
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('the Composer of core/vendor runs after a dry run that leaves its own packages alone', function () use ($root, $vendorCopy, $composer210) {
    $core = customPackagesCore(['league/csv' => '^9.0']);

    try {
        $run = restoreCustomPackagesRun($root, $core, $vendorCopy, $composer210 + [
            '--dry-run' => [0, "Package operations: 1 install, 0 updates, 0 removals\n  - Installing league/csv (9.28.0)"],
        ]);

        expect($run['commands'])->toHaveCount(3)
            ->and($run['commands'][1])->toEndWith('--dry-run')
            ->and($run['commands'][2])->not->toContain('--dry-run')
            ->and($run['discovered'])->toBe(1);
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('the Composer of core/vendor never replaces the packages it runs on', function () use ($root, $vendorCopy, $composer210) {
    $core = customPackagesCore(['league/csv' => '^9.0', 'symfony/finder' => '7.4.17']);

    try {
        $run = restoreCustomPackagesRun($root, $core, $vendorCopy, $composer210 + [
            '--dry-run' => [0, "  - Installing league/csv (9.28.0)\n  - Downgrading symfony/finder (v7.4.20 => v7.4.17)\n  - Upgrading symfony/string (v7.4.1 => v7.4.2)"],
        ]);

        // The version check and the dry run, nothing that writes.
        expect($run['commands'])->toHaveCount(2)
            ->and($run['discovered'])->toBe(0)
            ->and($run['out'])->toContain('symfony/finder, symfony/string')
            ->and($run['out'])->toContain('standalone Composer')
            ->and($run['out'])->toContain('cd core && composer update league/csv symfony/finder --with-dependencies --no-dev');
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('a dry run that fails stops the restore too', function () use ($root, $vendorCopy, $composer210) {
    $core = customPackagesCore(['league/csv' => '^9.0']);

    try {
        $run = restoreCustomPackagesRun($root, $core, $vendorCopy, $composer210 + [
            '--dry-run' => [2, 'Your requirements could not be resolved to an installable set of packages.'],
        ]);

        expect($run['commands'])->toHaveCount(2)
            ->and($run['out'])->toContain('could not be resolved');
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('a Composer before 2.7 is not given --minimal-changes', function () use ($root, $standalone) {
    $core = customPackagesCore(['league/csv' => '^9.0']);

    try {
        $run = restoreCustomPackagesRun($root, $core, $standalone, ['--version' => [0, 'Composer version 2.5.8 2023-06-09']]);

        expect($run['commands'][1])->toContain('--with-dependencies --no-dev')
            ->and($run['commands'][1])->not->toContain('--minimal-changes');
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('a CLI update without custom packages runs no Composer', function () use ($root, $standalone) {
    $core = customPackagesCore(['php' => '^8.3']);

    try {
        expect(restoreCustomPackagesRun($root, $core, $standalone)['commands'])->toBe([]);
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('--skipComposer=y leaves the packages alone and says how to restore them', function () use ($root, $standalone) {
    $core = customPackagesCore(['league/csv' => '^9.0']);

    try {
        $run = restoreCustomPackagesRun($root, $core, $standalone, [], ['skipComposer' => 'y']);

        expect($run['commands'])->toBe([])
            ->and($run['out'])->toContain('composer update league/csv --with-dependencies');
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('what the update prints reaches the terminal', function () use ($root) {
    $source = (string) file_get_contents($root . '/install/cli-install.php');
    $prompt = substr($source, strpos($source, 'public function checkRemoveInstall()'), 400);

    // initEvo() includes index.php, which opens an output buffer; it used to be
    // discarded here, and the restore warning with its command along with it.
    expect($prompt)->toContain('ob_end_flush();')
        ->and($prompt)->not->toContain('ob_end_clean();');
});

test('the CLI update restores the packages before it migrates', function () use ($root) {
    $source = (string) file_get_contents($root . '/install/cli-install.php');
    $update = substr($source, strpos($source, 'public function update()'));

    expect(strpos($update, '$this->restoreCustomPackages();'))->toBeLessThan(strpos($update, "Console::call('migrate'"));
});

test('locked versions of the custom packages are read from the lock file', function () {
    $core = customPackagesCore(['league/csv' => '^9.0'], [
        'packages' => [['name' => 'League/Csv', 'version' => '9.21.0'], ['name' => 'other/pkg', 'version' => '1.0.0']],
        'packages-dev' => [],
    ]);
    $packages = new CustomComposerPackages($core);

    try {
        expect($packages->constraints())->toBe(['league/csv' => '^9.0'])
            ->and($packages->lockedVersions(['league/csv']))->toBe(['league/csv' => '9.21.0'])
            ->and($packages->updateArguments(['league/csv' => '^9.0'], ['league/csv' => '9.21.0']))
            ->toStartWith(escapeshellarg('league/csv:9.21.0') . ' --with-all-dependencies');
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('the packages Composer runs on are composer/composer and all it requires', function () {
    $core = customPackagesCore(['league/csv' => '^9.0']);

    try {
        expect((new CustomComposerPackages($core))->composerRuntimePackages())
            ->toBe(['composer/composer', 'symfony/finder', 'symfony/console', 'symfony/string']);
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('the packages an update changes are read from its output', function () {
    $output = "Loading composer repositories with package information\n"
        . "Lock file operations: 1 install, 1 update, 0 removals\n"
        . "  - Locking league/csv (9.28.0)\n"
        . "Package operations: 1 install, 2 updates, 1 removal\n"
        . "  - Installing league/csv (9.28.0)\n"
        . "  - Downgrading Symfony/Finder (v7.4.20 => v7.4.17)\n"
        . "  - Upgrading symfony/console (v7.4.19 => v7.4.21)\n"
        . "  - Removing psr/log (3.0.2)\n";

    expect(CustomComposerPackages::changedPackages($output))
        ->toBe(['league/csv', 'symfony/finder', 'symfony/console', 'psr/log']);
});

test('a broken custom composer.json is reported, not skipped', function () {
    $core = customPackagesCore([]);
    file_put_contents($core . 'custom/composer.json', '{broken');

    try {
        expect(fn () => (new CustomComposerPackages($core))->constraints())->toThrow(RuntimeException::class);
    } finally {
        removeCustomPackagesCore($core);
    }
});
