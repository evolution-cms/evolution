<?php

/*
 * An uncaught exception of a console command ends the process in ExceptionHandler. It used to end with
 * status 0, so "artisan migrate" with a failed migration looked like a success to whatever ran it: make:site,
 * Composer scripts, a deploy.
 */

/**
 * @param string $call Code that runs against $handler.
 * @return array{0: int, 1: string} Exit status and output.
 */
function consoleHandlerExit(string $call): array
{
    $core = dirname(__DIR__, 2);
    $script = tempnam(sys_get_temp_dir(), 'evo-exit-');
    file_put_contents($script, '<?php
        require ' . var_export($core . '/vendor/autoload.php', true) . ';
        require_once ' . var_export($core . '/functions/helper.php', true) . ';
        require_once ' . var_export($core . '/functions/preload.php', true) . ';
        define("EVO_BASE_PATH", ' . var_export(dirname($core) . '/', true) . ');
        $_SERVER += ["HTTP_USER_AGENT" => "", "HTTP_REFERER" => ""];
        error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);

        class ExitCodeService
        {
            public function __call($name, $arguments)
            {
                if ($name === "getConnection") {
                    throw new Exception("no database");
                }

                return $name === "htmlspecialchars" ? (string) $arguments[0] : "";
            }
        }

        class ExitCodeContainer
        {
            public $messageQuitCount = 0;
            public $error_reporting = 1;
            public $documentIdentifier = "";
            public $queryTime = 0.0;
            public $tstart = 0.0;
            public $mstart = 0;

            public function __call($name, $arguments)
            {
                return match ($name) {
                    "getConfig", "getMicroTime" => 0,
                    "getManagerApi" => (object) ["action" => 0],
                    default => new ExitCodeService(),
                };
            }
        }

        $class = new ReflectionClass(EvolutionCMS\ExceptionHandler::class);
        $handler = $class->newInstanceWithoutConstructor();
        $class->getProperty("container")->setValue($handler, new ExitCodeContainer());
        ' . $call . '
        echo "still running";
    ');

    try {
        $output = evoRunPhp($script, [], $status);
    } finally {
        unlink($script);
    }

    return [(int) $status, (string) $output];
}

test('an uncaught exception ends a console command with status 1', function () {
    [$status, $output] = consoleHandlerExit('$handler->handleException(new LogicException("migration failed"));');

    expect($output)->toContain('migration failed')->not->toContain('still running')
        ->and($status)->toBe(1, $output);
});

test('a console usage error ends with status 1', function (string $exception) {
    [$status, $output] = consoleHandlerExit('$handler->handleException(new ' . $exception . '("bad usage"));');

    expect($output)->toBe('bad usage')
        ->and($status)->toBe(1);
})->with([
    'unknown command' => [\Symfony\Component\Console\Exception\CommandNotFoundException::class],
    'invalid argument' => [\Symfony\Component\Console\Exception\InvalidArgumentException::class],
    'invalid option' => [\Symfony\Component\Console\Exception\InvalidOptionException::class],
    'missing argument' => [\Symfony\Component\Console\Exception\RuntimeException::class],
]);

test('a debug stop that is no error keeps status 0', function () {
    [$status, $output] = consoleHandlerExit('$handler->messageQuit("stop", "", false);');

    expect($output)->toContain('stop')->not->toContain('still running')
        ->and($status)->toBe(0, $output);
});
