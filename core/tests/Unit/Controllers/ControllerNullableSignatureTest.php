<?php

use Symfony\Component\Process\Process;

test('resources controller autoload has no implicit nullable deprecation', function () {
    $result = runControllerAutoloadCheck('EvolutionCMS\\Controllers\\Resources');

    expect($result['exitCode'])->toBe(0)
        ->and($result['output'])->toContain('ok');
});

test('role management controller autoload has no implicit nullable deprecation', function () {
    $result = runControllerAutoloadCheck('EvolutionCMS\\Controllers\\UserRoles\\RoleManagment');

    expect($result['exitCode'])->toBe(0)
        ->and($result['output'])->toContain('ok');
});

function runControllerAutoloadCheck(string $className): array
{
    $autoloadPath = dirname(__DIR__, 3) . '/vendor/autoload.php';
    $script = <<<'PHP'
set_error_handler(function (int $severity, string $message, string $file, int $line) {
    if (in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    return false;
});

require $argv[1];

if (!class_exists($argv[2])) {
    fwrite(STDERR, "class-not-found\n");
    exit(2);
}

echo "ok\n";
PHP;

    $process = new Process([PHP_BINARY, '-d', 'display_errors=1', '-r', $script, $autoloadPath, $className]);
    $process->run();

    return [
        'exitCode' => $process->getExitCode(),
        'output' => $process->getOutput(),
        'errorOutput' => $process->getErrorOutput(),
    ];
}
