<?php

/*
|--------------------------------------------------------------------------
| The CLI installer's composer step runs before the core bootstraps
|--------------------------------------------------------------------------
|
| Nothing has registered the Composer autoloader at that point, so the installer
| has to make the ExecWithFallback package loadable itself. The CI install job
| passes --skipComposer=y and never reaches the call, which is how a fresh
| `php cli-install.php` came to fatal with "Class ExecWithFallback not found".
|
*/

$root = dirname(__DIR__, 4);

it('loads ExecWithFallback in a process without the Composer autoloader', function () use ($root) {
    $probe = tempnam(sys_get_temp_dir(), 'evo_exec_probe_');
    file_put_contents($probe, '<?php'
        . ' define("EVO_CORE_PATH", ' . var_export($root . '/core/', true) . ');'
        . ' require ' . var_export($root . '/install/src/functions.php', true) . ';'
        . ' echo json_encode([loadExecWithFallback(), class_exists("ExecWithFallback\\ExecWithFallback"), class_exists("ExecWithFallback\\Availability")]);');
    $out = trim((string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($probe) . ' 2>&1'));
    unlink($probe);

    expect($out)->toBe('[true,true,true]');
});

it('reports a missing package instead of fataling', function () {
    $core = sys_get_temp_dir() . '/evo_exec_missing_' . uniqid() . '/';
    $probe = tempnam(sys_get_temp_dir(), 'evo_exec_probe_');
    file_put_contents($probe, '<?php'
        . ' define("EVO_CORE_PATH", ' . var_export($core, true) . ');'
        . ' require ' . var_export(dirname(__DIR__, 4) . '/install/src/functions.php', true) . ';'
        . ' var_export(loadExecWithFallback());');
    $out = trim((string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($probe) . ' 2>&1'));
    unlink($probe);

    expect($out)->toBe('false');
});

it('makes the package loadable before the installer calls it', function () use ($root) {
    $installer = (string) file_get_contents($root . '/install/cli-install.php');

    expect(strpos($installer, 'loadExecWithFallback()'))
        ->toBeLessThan(strpos($installer, 'ExecWithFallback::exec($cmd'));
});
