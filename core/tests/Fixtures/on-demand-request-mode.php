<?php

// Prints whether a web request in the mode named by argv[1] registers declared providers on demand.
// Runs in its own process: the request modes are constants.

$core = dirname(__DIR__, 2);
require $core . '/vendor/autoload.php';

$mode = $argv[1] ?? 'frontend';
define('IN_MANAGER_MODE', $mode === 'manager');
if ($mode === 'install') {
    define('IN_INSTALL_MODE', true);
}
if ($mode === 'kept-worker') {
    require $core . '/includes/worker.runtime.php';
    EvolutionCMS\Runtime\WorkerRuntime::keepApplications(true);
    (new ReflectionProperty(EvolutionCMS\Runtime\WorkerRuntime::class, 'active'))->setValue(null, true);
}

$app = (new ReflectionClass(new class extends EvolutionCMS\Core {
    public function __construct()
    {
    }

    public function runningInConsole()
    {
        return false;
    }
}))->newInstanceWithoutConstructor();

echo (new ReflectionMethod($app, 'registersProvidersOnDemand'))->invoke($app) ? 'on-demand' : 'all';
