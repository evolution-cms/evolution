<?php

/*
|--------------------------------------------------------------------------
| A CLI install drops the development packages after the update
|--------------------------------------------------------------------------
|
| The update keeps resolving with require-dev (Composer runs out of the vendor
| directory it rewrites, and the order it replaces its own dependencies in
| depends on that), then a second pass removes the test suite's packages, whose
| autoloaded files a site would otherwise include on every request.
|
*/

$root = dirname(__DIR__, 4);

/** The Composer commands composerUpdate() runs when each run reports $results in turn. */
function composerCommandsOfInstall(string $root, array $results): array
{
    $probe = tempnam(sys_get_temp_dir(), 'evo_composer_probe_');
    file_put_contents($probe, '<?php'
        . ' require ' . var_export($root . '/install/cli-install.php', true) . ';'
        . ' class RecordingInstall extends InstallEvo {'
        . '   public array $commands = []; public array $results = [];'
        . '   protected function runComposerUpdate(string $cmd): bool { $this->commands[] = $cmd; return array_shift($this->results) ?? true; }'
        . ' }'
        . ' $install = (new ReflectionClass(RecordingInstall::class))->newInstanceWithoutConstructor();'
        . ' $install->results = ' . var_export($results, true) . ';'
        . ' ob_start(); $install->composerUpdate(); ob_end_clean();'
        . ' echo json_encode($install->commands);');
    $out = trim(evoRunPhp($probe));
    unlink($probe);

    return json_decode($out, true, flags: JSON_THROW_ON_ERROR);
}

it('updates with the development packages, then removes them', function () use ($root) {
    $commands = composerCommandsOfInstall($root, [true, true]);

    expect($commands)->toHaveCount(2)
        ->and($commands[0])->toContain(' update ')->not->toContain('--no-dev')
        ->and($commands[1])->toContain(' install ')->toContain('--no-dev');
});

it('leaves the packages alone when the update failed', function () use ($root) {
    $commands = composerCommandsOfInstall($root, [false]);

    expect($commands)->toHaveCount(1)
        ->and($commands[0])->toContain(' update ');
});
