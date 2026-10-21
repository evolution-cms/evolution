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
| They come back at the versions the last restore noted, after a dry run
| showed nothing else would change; other versions need --updateCustomPackages=y.
| The Composer in core/vendor runs out of the directory it rewrites: it never
| replaces its own packages, however it was selected. A restore that fails
| leaves the update incomplete: exit code 1, install/ kept.
|
*/

use EvolutionCMS\Services\CustomComposerPackages;

$root = dirname(__DIR__, 4);

function customPackagesCore(array $require, ?array $lock = null, ?array $installed = null, ?array $record = null, array $coreRequire = []): string
{
    $core = sys_get_temp_dir() . '/evo-custom-packages-' . getmypid() . '-' . uniqid() . '/';
    mkdir($core . 'custom', 0777, true);
    mkdir($core . 'vendor/composer', 0777, true);
    file_put_contents($core . 'custom/composer.json', json_encode(['require' => $require]));
    file_put_contents($core . 'composer.json', json_encode(['require' => $coreRequire ?: new stdClass()]));
    if ($lock !== null) {
        file_put_contents($core . 'composer.lock', json_encode($lock));
    }
    if ($record !== null) {
        file_put_contents($core . CustomComposerPackages::RECORD_FILE, json_encode(['packages' => $record]));
    }
    file_put_contents($core . 'vendor/composer/installed.json', json_encode($installed ?? ['packages' => [
        ['name' => 'composer/composer', 'require' => ['php' => '^8.1', 'symfony/finder' => '^7', 'symfony/console' => '^7']],
        ['name' => 'symfony/console', 'require' => ['symfony/string' => '^7']],
        ['name' => 'symfony/finder', 'require' => []],
        ['name' => 'symfony/string', 'require' => []],
    ]]));

    return $core;
}

/**
 * Run restoreCustomPackages() for a core directory.
 *
 * @param array<string, array{0: int, 1: string}> $answers Command fragment => what running it answers, first match wins.
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
        . ' ob_start(); $ok = $install->restoreCustomPackages(); $out = ob_get_clean();'
        . ' echo json_encode(["ok" => $ok, "commands" => $install->commands, "discovered" => $install->discovered, "out" => $out]);');
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
$noted = [
    'league/csv' => ['version' => '9.18.0', 'dependencies' => []],
    'seiger/stask' => ['version' => '1.0.10', 'dependencies' => ['seiger/score' => '2.0.0']],
];

test('the packages come back at the versions the last restore noted', function () use ($root, $standalone, $composer210, $noted) {
    $core = customPackagesCore(['php' => '^8.3', 'league/csv' => '^9.0', 'Seiger/sTask' => '*'], null, null, $noted);

    try {
        $run = restoreCustomPackagesRun($root, $core, $standalone, $composer210 + [
            '--dry-run' => [0, "  - Installing league/csv (9.18.0)\n  - Installing seiger/stask (1.0.10)\n  - Installing seiger/score (2.0.0)"],
        ]);

        expect($run['ok'])->toBeTrue()
            ->and($run['commands'])->toHaveCount(3)
            ->and($run['commands'][1])->toContain('--dry-run')
            ->and($run['commands'][2])->toStartWith('composer update ' . implode(' ', array_map('escapeshellarg', ['league/csv:9.18.0', 'seiger/stask:1.0.10', 'seiger/score:2.0.0'])))
            // core's own requirements (illuminate/*, symfony/*) keep the versions the release ships
            ->and($run['commands'][2])->toContain('--with-dependencies --minimal-changes --no-dev')
            ->and($run['commands'][2])->not->toContain('--with-all-dependencies')
            ->and($run['commands'][2])->not->toContain('--dry-run')
            ->and($run['commands'][2])->toContain('--working-dir=' . escapeshellarg($core))
            ->and($run['discovered'])->toBe(1)
            ->and($run['out'])->toContain('Custom Composer packages restored');
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('without noted versions Composer chooses them only with consent', function () use ($root, $standalone, $composer210) {
    $core = customPackagesCore(['league/csv' => '^9.0']);
    $answers = $composer210 + ['--dry-run' => [0, '  - Installing league/csv (9.28.0)']];

    try {
        $refused = restoreCustomPackagesRun($root, $core, $standalone, $answers);
        $accepted = restoreCustomPackagesRun($root, $core, $standalone, $answers, ['updateCustomPackages' => 'y']);

        expect($refused['ok'])->toBeFalse()
            ->and($refused['commands'])->toHaveCount(2)
            ->and($refused['discovered'])->toBe(0)
            ->and($refused['out'])->toContain('league/csv would be installed at 9.28.0 (the version the site ran is not known)')
            ->and($refused['out'])->toContain('--updateCustomPackages=y')
            ->and($accepted['ok'])->toBeTrue()
            ->and($accepted['commands'])->toHaveCount(3)
            ->and($accepted['discovered'])->toBe(1);
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('a dry run that moves a package the release ships is refused', function () use ($root, $standalone, $composer210, $noted) {
    $core = customPackagesCore(['league/csv' => '^9.0'], null, null, $noted);

    try {
        $run = restoreCustomPackagesRun($root, $core, $standalone, $composer210 + [
            '--dry-run' => [0, "  - Installing league/csv (9.18.0)\n  - Upgrading illuminate/contracts (v12.70.0 => v12.70.1)"],
        ]);

        expect($run['ok'])->toBeFalse()
            ->and($run['commands'])->toHaveCount(2)
            ->and($run['out'])->toContain('illuminate/contracts v12.70.0 => v12.70.1');
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('noted versions that no longer fit are refused with what Composer would choose', function () use ($root, $standalone, $composer210, $noted) {
    $core = customPackagesCore(['league/csv' => '^9.0'], null, null, $noted);

    try {
        $run = restoreCustomPackagesRun($root, $core, $standalone, $composer210 + [
            'league/csv:9.18.0' => [2, 'Your requirements could not be resolved to an installable set of packages.'],
            '--dry-run' => [0, '  - Installing league/csv (9.30.0)'],
        ]);

        expect($run['ok'])->toBeFalse()
            ->and($run['commands'])->toHaveCount(3)
            ->and($run['out'])->toContain('do not fit this version')
            ->and($run['out'])->toContain('league/csv 9.18.0 => 9.30.0')
            // The manual command is the decision to let Composer choose.
            ->and($run['out'])->toContain('cd core && composer update league/csv --with-dependencies --no-dev');
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('the Composer of core/vendor never replaces the packages it runs on, not even with consent', function () use ($root, $vendorCopy, $composer210, $noted) {
    $core = customPackagesCore(['league/csv' => '^9.0'], null, null, $noted);

    try {
        $run = restoreCustomPackagesRun($root, $core, $vendorCopy, $composer210 + [
            '--dry-run' => [0, "  - Installing league/csv (9.18.0)\n  - Downgrading symfony/finder (v7.4.20 => v7.4.17)\n  - Upgrading symfony/string (v7.4.1 => v7.4.2)"],
        ], ['updateCustomPackages' => 'y']);

        // The version check and the dry run, nothing that writes.
        expect($run['ok'])->toBeFalse()
            ->and($run['commands'])->toHaveCount(2)
            ->and($run['discovered'])->toBe(0)
            ->and($run['out'])->toContain('symfony/finder, symfony/string')
            ->and($run['out'])->toContain('standalone Composer')
            ->and($run['out'])->toContain('cd core && composer update league/csv:9.18.0 --with-dependencies --no-dev');
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('a dry run that fails stops the restore too', function () use ($root, $vendorCopy, $composer210) {
    $core = customPackagesCore(['league/csv' => '^9.0']);

    try {
        $run = restoreCustomPackagesRun($root, $core, $vendorCopy, $composer210 + [
            '--dry-run' => [2, 'Your requirements could not be resolved to an installable set of packages.'],
        ], ['updateCustomPackages' => 'y']);

        expect($run['ok'])->toBeFalse()
            ->and($run['commands'])->toHaveCount(2)
            ->and($run['out'])->toContain('could not be resolved');
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('a Composer before 2.7 is not given --minimal-changes', function () use ($root, $standalone, $noted) {
    $core = customPackagesCore(['league/csv' => '^9.0'], null, null, $noted);

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
        $run = restoreCustomPackagesRun($root, $core, $standalone);

        expect($run['ok'])->toBeTrue()
            ->and($run['commands'])->toBe([]);
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('packages the kept lock already holds and vendor has need no Composer', function () use ($root, $standalone) {
    $core = customPackagesCore(
        ['league/csv' => '^9.0'],
        ['packages' => [['name' => 'league/csv', 'version' => '9.18.0']], 'packages-dev' => []],
        ['packages' => [['name' => 'league/csv', 'require' => []]]]
    );

    try {
        $run = restoreCustomPackagesRun($root, $core, $standalone);

        expect($run['ok'])->toBeTrue()
            ->and($run['commands'])->toBe([]);
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('--skipComposer=y leaves the packages alone and says how to restore them', function () use ($root, $standalone) {
    $core = customPackagesCore(['league/csv' => '^9.0']);

    try {
        $run = restoreCustomPackagesRun($root, $core, $standalone, [], ['skipComposer' => 'y']);

        expect($run['ok'])->toBeTrue()
            ->and($run['commands'])->toBe([])
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

test('the CLI update restores the packages last and fails when it cannot', function () use ($root) {
    $source = (string) file_get_contents($root . '/install/cli-install.php');
    $update = substr($source, strpos($source, 'public function update()'));
    $update = substr($update, 0, strpos($update, 'public function restoreCustomPackages()'));

    expect(strpos($update, "Console::call('migrate', ['--force' => true]);"))->toBeLessThan(strpos($update, '$this->restoreCustomPackages()'))
        ->and($update)->toContain("if (!\$this->restoreCustomPackages()) {")
        ->and($update)->toContain("throw new RuntimeException('Update incomplete")
        // Thrown before the install folder is removed; runCliInstall() turns it into exit code 1.
        ->and(strpos($update, "throw new RuntimeException('Update incomplete"))->toBeLessThan(strpos($update, '$this->removeInstall();'))
        ->and($source)->toContain("} catch (RuntimeException \$exception) {\n        error(\$exception->getMessage());\n\n        return 1;");
});

/*
| The policy itself
*/

test('the versions the site runs are the custom packages and what only they need', function () {
    $core = customPackagesCore(['league/csv' => '^9.0', 'seiger/stask' => '*', 'illuminate/support' => '^12'], ['packages' => [
        ['name' => 'League/Csv', 'version' => '9.21.0', 'require' => ['php' => '^8.1', 'league/helper' => '^1']],
        ['name' => 'league/helper', 'version' => '1.2.0', 'require' => ['illuminate/contracts' => '^12']],
        ['name' => 'illuminate/contracts', 'version' => 'v12.70.0'],
        ['name' => 'illuminate/support', 'version' => 'v12.70.0'],
        ['name' => 'other/pkg', 'version' => '1.0.0'],
    ], 'packages-dev' => []], null, [
        'seiger/stask' => ['version' => '1.0.10', 'dependencies' => ['seiger/score' => '2.0.0']],
    ], ['illuminate/contracts' => '^12', 'illuminate/support' => '^12']);
    $packages = new CustomComposerPackages($core);

    try {
        expect($packages->lockedVersions(['league/csv']))->toBe(['league/csv' => '9.21.0'])
            // From the lock where it lists them, else from the record; the core keeps what it requires.
            ->and($packages->previousVersions(['league/csv', 'seiger/stask', 'illuminate/support']))->toBe([
                'league/csv' => '9.21.0',
                'league/helper' => '1.2.0',
                'seiger/stask' => '1.0.10',
                'seiger/score' => '2.0.0',
            ]);
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('what the release ships, or the core requires, is never pinned', function () {
    $core = customPackagesCore([], ['packages' => [['name' => 'psr/log', 'version' => '3.0.2']], 'packages-dev' => []], null, null, ['illuminate/support' => '^12']);

    try {
        expect((new CustomComposerPackages($core))->pins(
            ['league/csv', 'new/pkg', 'illuminate/support'],
            ['league/csv' => '9.18.0', 'psr/log' => '2.0.0', 'league/helper' => '1.2.0']
        ))->toBe(['league/csv' => '9.18.0', 'new/pkg' => '', 'league/helper' => '1.2.0']);
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('a custom package the release ships at a refused version is left to Composer', function () {
    $core = customPackagesCore([], ['packages' => [
        ['name' => 'psr/log', 'version' => '3.0.2'],
        ['name' => 'symfony/finder', 'version' => 'v7.4.14'],
    ], 'packages-dev' => []]);

    try {
        expect((new CustomComposerPackages($core))->pins(
            ['psr/log', 'symfony/finder'],
            [],
            ['psr/log' => '^2.0', 'symfony/finder' => '^7.4']
        ))->toBe(['psr/log' => '']);
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('a custom constraint on a package the core requires is checked against the lock too', function () {
    // The merge plugin runs with "replace", so ^6.0 replaces the core's 7.*.
    $core = customPackagesCore([], ['packages' => [
        ['name' => 'symfony/process', 'version' => 'v7.4.21'],
        ['name' => 'illuminate/support', 'version' => 'v12.70.0'],
    ], 'packages-dev' => []], null, null, ['symfony/process' => '7.*', 'illuminate/support' => '^12']);

    try {
        expect((new CustomComposerPackages($core))->pins(
            ['symfony/process', 'illuminate/support'],
            [],
            ['symfony/process' => '^6.0', 'illuminate/support' => '^12.0']
        ))->toBe(['symfony/process' => '']);
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('a copy of the custom composer.json still finds the site\'s local repositories', function () {
    $live = customPackagesCore(['acme/hello' => '^1.0']);
    mkdir($live . 'custom/packages/hello', 0777, true);
    mkdir($live . 'custom/more', 0777, true);
    mkdir($live . 'artifacts', 0777, true);
    $staging = sys_get_temp_dir() . '/evo-staging-' . getmypid() . '-' . uniqid() . '/unpacked/core/';
    mkdir($staging . 'custom', 0777, true);
    $json = [
        'require' => ['acme/hello' => '^1.0'],
        'repositories' => [
            ['type' => 'path', 'url' => 'packages/*'],
            'hello' => ['type' => 'path', 'url' => 'packages/hello', 'options' => ['symlink' => true]],
            ['type' => 'artifact', 'url' => 'artifacts'],
            ['type' => 'composer', 'url' => 'https://packages.example.com'],
            ['type' => 'path', 'url' => '/srv/shared/pkg'],
        ],
        'extra' => ['merge-plugin' => ['include' => ['more/composer.json'], 'require' => ['required.json']]],
    ];
    file_put_contents($live . 'custom/required.json', '{}');

    try {
        $rebased = (new CustomComposerPackages($live))->rebasedCustomComposerJson($json, $staging);
        // Resolved the way the merge plugin does it: "custom/" in front of the path, from the core directory.
        $resolve = fn (string $path) => realpath($staging . 'custom/' . $path);

        expect($resolve($rebased['repositories']['hello']['url']))->toBe(realpath($live . 'custom/packages/hello'))
            ->and($rebased['repositories']['hello']['options'])->toBe(['symlink' => true])
            ->and(glob($staging . 'custom/' . $rebased['repositories'][0]['url']))->toHaveCount(1)
            ->and(realpath($rebased['repositories'][1]['url']))->toBe(realpath($live . 'artifacts'))
            ->and($rebased['repositories'][2]['url'])->toBe('https://packages.example.com')
            ->and($rebased['repositories'][3]['url'])->toBe('/srv/shared/pkg')
            ->and($resolve(dirname($rebased['extra']['merge-plugin']['include'][0])))->toBe(realpath($live . 'custom/more'))
            // A file that must exist, as a list or as a single path.
            ->and($resolve($rebased['extra']['merge-plugin']['require'][0]))->toBe(realpath($live . 'custom/required.json'));

        $json['extra']['merge-plugin'] = ['require' => 'required.json'];
        $single = (new CustomComposerPackages($live))->rebasedCustomComposerJson($json, $staging);
        expect($single['extra']['merge-plugin']['require'])->toBeString()
            ->and($resolve($single['extra']['merge-plugin']['require']))->toBe(realpath($live . 'custom/required.json'));
    } finally {
        removeCustomPackagesCore($live);
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory(dirname($staging, 2));
    }
});

test('with consent, the fallback lets Composer move a shipped custom package too', function () {
    $core = customPackagesCore([], ['packages' => [['name' => 'symfony/finder', 'version' => 'v7.4.14']], 'packages-dev' => []]);
    $commands = [];
    $run = function (string $command) use (&$commands): array {
        $commands[] = $command;
        if (str_contains($command, 'league/csv:9.18.0')) {
            return [2, 'Root composer.json requires symfony/finder 7.4.0, it is locked to v7.4.14.'];
        }

        return [0, "Lock file operations: 1 install, 1 update, 0 removals\n  - Locking league/csv (9.18.0)\n  - Downgrading symfony/finder (v7.4.14 => v7.4.0)\nInstalling dependencies from lock file\n"];
    };
    $packages = new CustomComposerPackages($core);

    try {
        $refused = $packages->plan($run, 'composer', ['league/csv' => '9.18.0'], ['league/csv', 'symfony/finder'], true, false, false);
        $accepted = $packages->plan($run, 'composer', ['league/csv' => '9.18.0'], ['league/csv', 'symfony/finder'], true, true, false);

        expect($refused['ok'])->toBeFalse()
            ->and($refused['reason'])->toBe('changes')
            ->and($refused['pinsFit'])->toBeFalse()
            ->and($refused['changes'])->toBe(['symfony/finder v7.4.14 => v7.4.0'])
            ->and($commands[1])->toContain(escapeshellarg('league/csv') . ' ' . escapeshellarg('symfony/finder') . ' --with-dependencies')
            ->and($accepted['ok'])->toBeTrue()
            ->and($accepted['arguments'])->toStartWith(escapeshellarg('league/csv') . ' ' . escapeshellarg('symfony/finder'));
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('a restore notes the versions for the next update, beside the custom composer.json', function () {
    $core = customPackagesCore(['league/csv' => '^9.0', 'gone/pkg' => '*'], ['packages' => [
        ['name' => 'league/csv', 'version' => '9.21.0', 'require' => ['league/helper' => '^1']],
        ['name' => 'league/helper', 'version' => '1.2.0'],
    ], 'packages-dev' => []]);
    $packages = new CustomComposerPackages($core);

    try {
        $packages->recordInstalled(['league/csv', 'gone/pkg']);

        expect($packages->recordedVersions())->toBe(['league/csv' => ['version' => '9.21.0', 'dependencies' => ['league/helper' => '1.2.0']]])
            ->and(CustomComposerPackages::RECORD_FILE)->toStartWith('custom/');
    } finally {
        removeCustomPackagesCore($core);
    }
});

test('the restore never hands Composer --with-all-dependencies', function () {
    $arguments = (new CustomComposerPackages(sys_get_temp_dir()))->updateArguments(['league/csv' => '9.21.0', 'new/pkg' => ''], true);

    expect($arguments)->toStartWith(escapeshellarg('league/csv:9.21.0') . ' ' . escapeshellarg('new/pkg') . ' --with-dependencies --minimal-changes')
        ->and($arguments)->not->toContain('--with-all-dependencies');
});

test('only installing the pinned versions passes without consent', function () {
    $operations = CustomComposerPackages::operations("Lock file operations: 2 installs, 1 update, 0 removals\n"
        . "  - Locking league/csv (9.18.0)\n"
        . "Package operations: 3 installs, 1 update, 1 removal\n"
        . "  - Installing league/csv (9.18.0)\n"
        . "  - Installing league/helper (v1.3.0)\n"
        . "  - Installing new/dependency (1.0.0)\n"
        . "  - Installing unknown/custom (2.0.0)\n"
        . "  - Upgrading Illuminate/Contracts (v12.70.0 => v12.70.1)\n"
        . "  - Removing psr/log (3.0.2)\n");

    expect($operations['league/csv'])->toBe(['op' => 'install', 'from' => '', 'to' => '9.18.0'])
        ->and($operations['illuminate/contracts'])->toBe(['op' => 'change', 'from' => 'v12.70.0', 'to' => 'v12.70.1'])
        ->and(CustomComposerPackages::unexpectedChanges($operations, ['league/csv' => 'v9.18.0', 'league/helper' => '1.2.0', 'unknown/custom' => ''], ['league/csv', 'unknown/custom']))
        ->toBe([
            'league/helper 1.2.0 => v1.3.0',
            'unknown/custom would be installed at 2.0.0 (the version the site ran is not known)',
            'illuminate/contracts v12.70.0 => v12.70.1',
            'psr/log 3.0.2 would be removed',
        ]);
});

test('changes are judged on the lock, not on the vendor drift an update package ships', function () {
    // Composer 2.10 against the unpacked 3.5.8 archive, whose vendor is newer than its own lock.
    $output = "Loading composer repositories with package information\n"
        . "Updating dependencies\n"
        . "Lock file operations: 1 install, 1 update, 0 removals\n"
        . "  - Locking league/csv (9.28.0)\n"
        . "  - Downgrading symfony/finder (v7.4.14 => v7.4.0)\n"
        . "Installing dependencies from lock file\n"
        . "Package operations: 1 install, 3 updates, 0 removals\n"
        . "  - Downgrading symfony/finder (v7.4.17 => v7.4.0)\n"
        . "  - Downgrading composer/composer (2.10.3 => 2.10.2)\n"
        . "  - Downgrading illuminate/session (v12.69.2 => v12.67.0)\n"
        . "  - Installing league/csv (9.28.0)\n";
    $unchanged = "Loading composer repositories with package information\nUpdating dependencies\n"
        . "Nothing to modify in lock file\nInstalling dependencies from lock file\n"
        . "Package operations: 0 installs, 1 update, 0 removals\n  - Downgrading illuminate/session (v12.69.2 => v12.67.0)\n";

    expect(array_keys(CustomComposerPackages::operations($output, true)))->toBe(['league/csv', 'symfony/finder'])
        ->and(CustomComposerPackages::unexpectedChanges(CustomComposerPackages::operations($output, true), ['league/csv' => '9.28.0'], ['league/csv']))
        ->toBe(['symfony/finder v7.4.14 => v7.4.0'])
        ->and(CustomComposerPackages::operations($unchanged, true))->toBe([])
        // The bundled Composer would run into all of them, the drift included.
        ->and(array_keys(CustomComposerPackages::operations($output)))->toBe(['league/csv', 'symfony/finder', 'composer/composer', 'illuminate/session']);
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

test('the Composer of core/vendor is recognised however it is reached', function () {
    $core = customPackagesCore(['league/csv' => '^9.0']);
    mkdir($core . 'vendor/bin', 0777, true);
    file_put_contents($core . 'vendor/bin/composer', "#!/usr/bin/env php\n<?php include __DIR__ . '/../composer/composer/bin/composer';\n");
    $wrapper = $core . 'composer-wrapper';
    file_put_contents($wrapper, "#!/bin/sh\nexec php " . $core . "vendor/bin/composer \"\$@\"\n");
    $standalone = $core . 'custom/composer-standalone';
    file_put_contents($standalone, "#!/bin/sh\nexec php /opt/composer.phar \"\$@\"\n");
    $packages = new CustomComposerPackages($core);
    $path = getenv('PATH');

    try {
        expect($packages->isBundledComposer(escapeshellarg($core . 'vendor/bin/composer')))->toBeTrue()
            ->and($packages->isBundledComposer(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($core . 'vendor/bin/composer')))->toBeTrue()
            // Relative to the core directory, as make:site runs its commands from there.
            ->and($packages->isBundledComposer("'php' 'vendor/bin/composer'"))->toBeTrue()
            ->and($packages->isBundledComposer(escapeshellarg($wrapper)))->toBeTrue()
            ->and($packages->isBundledComposer(escapeshellarg($standalone)))->toBeFalse()
            ->and($packages->isBundledComposer(''))->toBeFalse()
            // Started by itself, the vendor script is run through PHP: an update package drops its executable bit.
            ->and($packages->bundledPhpScript(escapeshellarg($core . 'vendor/bin/composer')))->toBe(realpath($core . 'vendor/bin/composer'))
            ->and($packages->bundledPhpScript(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($core . 'vendor/bin/composer')))->toBeNull()
            ->and($packages->bundledPhpScript(escapeshellarg($wrapper)))->toBeNull();

        // A bare "composer" is what the PATH finds.
        putenv('PATH=' . $core . 'vendor/bin');
        expect($packages->isBundledComposer('composer'))->toBeTrue();
        putenv('PATH=' . $core . 'custom');
        expect($packages->isBundledComposer('composer'))->toBeFalse();
    } finally {
        $path === false ? putenv('PATH') : putenv('PATH=' . $path);
        removeCustomPackagesCore($core);
    }
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
