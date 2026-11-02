<?php

/*
|--------------------------------------------------------------------------
| The web installer installs the Composer dependencies like make:site
|--------------------------------------------------------------------------
|
| The manager's updater lays a source archive over the site, which has no
| vendor directory. Before any step boots the new core (WebInstallStaleVendorTest)
| the web installer builds core/vendor from the new lock and rebuilds the
| classmap-authoritative autoloader with the arguments make:site uses
| (CustomComposerPackages::INSTALL_ARGUMENTS / DUMP_AUTOLOAD_ARGUMENTS).
|
*/

use EvolutionCMS\Services\CustomComposerPackages;

$root = dirname(__DIR__, 4);

/**
 * Run installerInstallComposerDependencies() in its own process with a stand-in shell.
 *
 * @param array<string, array{0: int, 1: string}> $answers Exit code and output by the first word after the Composer command.
 * @return array{result: array, commands: list<string>, discovered: bool}
 */
function webComposerInstallRun(string $root, string $core, bool $standalone, array $answers = []): array
{
    $probe = tempnam(sys_get_temp_dir(), 'evo_web_composer_install_');
    file_put_contents($probe, '<?php'
        . ' define("EVO_CORE_PATH", ' . var_export($core, true) . ');'
        . ' require ' . var_export($root . '/core/vendor/autoload.php', true) . ';'
        . ' require ' . var_export($root . '/install/src/functions.php', true) . ';'
        . ' $answers = ' . var_export($answers, true) . '; $commands = []; $discovered = false;'
        . ' $run = static function (string $command) use (&$commands, $answers): array {'
        . '   $commands[] = $command;'
        . '   $verb = strtok(substr($command, strlen("composer ")), " ");'
        . '   return $answers[$verb] ?? [0, ""];'
        . ' };'
        . ' $result = installerInstallComposerDependencies($run, static function () use (&$discovered): void { $discovered = true; },'
        . '   ["command" => "composer", "standalone" => ' . var_export($standalone, true) . ']);'
        . ' echo json_encode(["result" => $result, "commands" => $commands, "discovered" => $discovered]);');
    $out = trim(evoRunPhp($probe));
    unlink($probe);

    return json_decode($out, true, flags: JSON_THROW_ON_ERROR);
}

function webComposerInstallCore(): string
{
    $core = sys_get_temp_dir() . '/evo-web-composer-install-' . getmypid() . '-' . uniqid() . '/';
    mkdir($core . 'vendor/composer', 0777, true);
    // composer/composer runs on symfony/console: the Composer of core/vendor must not replace it.
    file_put_contents($core . 'vendor/composer/installed.json', json_encode(['packages' => [
        ['name' => 'composer/composer', 'require' => ['symfony/console' => '^8.0']],
        ['name' => 'symfony/console', 'require' => []],
    ]]));

    return $core;
}

test('a standalone Composer installs from the lock, rebuilds the autoloader and discovers the packages', function () use ($root) {
    $core = webComposerInstallCore();
    $dir = ' --working-dir=' . escapeshellarg(rtrim($core, '/\\')) . ' 2>&1';

    try {
        $run = webComposerInstallRun($root, $core, true);

        expect($run['result'])->toBe(['installed' => true, 'command' => 'cd core && composer install --no-dev --classmap-authoritative', 'output' => ''])
            ->and($run['commands'])->toBe([
                'composer ' . CustomComposerPackages::INSTALL_ARGUMENTS . $dir,
                'composer ' . CustomComposerPackages::DUMP_AUTOLOAD_ARGUMENTS . $dir,
            ])
            ->and($run['discovered'])->toBeTrue()
            ->and(CustomComposerPackages::INSTALL_ARGUMENTS)->toContain('--no-dev')->toContain('--classmap-authoritative')
            ->and(CustomComposerPackages::DUMP_AUTOLOAD_ARGUMENTS)->toContain('--classmap-authoritative');
    } finally {
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($core);
    }
});

test('the Composer of core/vendor proves with a dry run that it keeps its own packages', function () use ($root) {
    $core = webComposerInstallCore();

    try {
        $refused = webComposerInstallRun($root, $core, false, [
            'install' => [0, "Package operations: 0 installs, 1 update, 0 removals\n  - Upgrading symfony/console (v8.0.1 => v8.1.0)"],
        ]);
        $allowed = webComposerInstallRun($root, $core, false, [
            'install' => [0, "Package operations: 0 installs, 1 update, 0 removals\n  - Upgrading seiger/sgallery (v1.7.0 => v1.8.1)"],
        ]);

        expect($refused['result']['installed'])->toBeFalse()
            ->and($refused['result']['output'])->toContain('symfony/console')->toContain('standalone Composer')
            ->and($refused['commands'])->toHaveCount(1)
            ->and($refused['commands'][0])->toContain(' --dry-run')
            ->and($refused['discovered'])->toBeFalse()
            ->and($allowed['result']['installed'])->toBeTrue()
            ->and($allowed['commands'])->toHaveCount(3)
            ->and($allowed['commands'][0])->toContain(' --dry-run');
    } finally {
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($core);
    }
});

test('a failed install leaves the command to run and its output, and discovers nothing', function () use ($root) {
    $core = webComposerInstallCore();

    try {
        $run = webComposerInstallRun($root, $core, true, ['install' => [2, "Loading composer repositories\nCould not reach packagist.org"]]);

        expect($run['result']['installed'])->toBeFalse()
            ->and($run['result']['command'])->toBe('cd core && composer install --no-dev --classmap-authoritative')
            ->and($run['result']['output'])->toContain('Could not reach packagist.org')
            ->and($run['commands'])->toHaveCount(1)
            ->and($run['discovered'])->toBeFalse();
    } finally {
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($core);
    }
});

test('make:site and the web installer share the Composer arguments', function () use ($root) {
    $command = (string) file_get_contents($root . '/core/src/Console/SiteUpdateCommand.php');

    expect($command)->toContain("' ' . CustomComposerPackages::INSTALL_ARGUMENTS;")
        ->and($command)->toContain("' ' . CustomComposerPackages::DUMP_AUTOLOAD_ARGUMENTS;");
});

test('Composer runs before the new core boots; the booted update step only discovers the packages', function () use ($root) {
    $controller = (string) file_get_contents($root . '/install/src/controllers/install.php');
    $repairPage = (string) file_get_contents($root . '/install/src/template/actions/dependencies.php');
    $lang = (string) file_get_contents($root . '/install/src/lang/en.inc.php');
    $update = substr($controller, strpos($controller, "seed('update');"));

    // Rebuilding core/vendor in this request would not reach the Composer loader it already runs.
    expect($controller)->not->toContain('installerInstallComposerDependencies(')
        ->and(strpos($update, 'restoreCustomComposerPackages()'))->toBeLessThan(strpos($update, "Console::call('package:discover');"))
        ->and($repairPage)->toContain("htmlspecialchars(\$failed['command'], ENT_QUOTES, 'UTF-8')")
        ->and($repairPage)->toContain("htmlspecialchars(\$failed['output'], ENT_QUOTES, 'UTF-8')");
    foreach (['composer_dependencies_not_installed', 'dependencies_check_again', 'dependencies_note', 'dependencies_retry', 'dependencies_title'] as $key) {
        expect($lang)->toContain("'$key' =>");
    }
});
