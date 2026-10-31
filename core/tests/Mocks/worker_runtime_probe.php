<?php

use EvolutionCMS\Runtime\WorkerRuntime;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
require dirname(__DIR__, 2) . '/includes/worker.runtime.php';

$mode = $argv[1];
if ($mode === 'secure-cookie') {
    define('EVO_CLASS', Container::class);
    define('IN_MANAGER_MODE', false);
    define('IN_INSTALL_MODE', false);
    define('EVO_API_MODE', true);
    $GLOBALS['evo'] = $app = new Container();
    $app->instance('path.storage', sys_get_temp_dir());
    $results = [];
    foreach (['', 'off', 'OFF', 'on'] as $https) {
        $_SERVER['HTTPS'] = $https;
        $_SERVER['SERVER_PORT'] = 80;
        $config = require dirname(__DIR__, 2) . '/config/session.php';
        $results[] = $config['secure'];
    }
    echo json_encode($results);
}
if ($mode === 'session-start') {
    if ($argv[2] === 'worker') {
        define('EVO_WORKER_MODE', true);
    }
    define('EVO_SESSION', $argv[3] === 'proxy');
    require dirname(__DIR__, 2) . '/functions/session_proxy.php';
    echo json_encode(EvoSessionProxy::startsAfterBoot());
}
if ($mode === 'classic-string' || $mode === 'classic-code') {
    evo_register_shutdown(static fn () => print('|shutdown'));
    evo_exit($mode === 'classic-string' ? 'body' : 7);
    echo 'unreachable';
}

if ($mode === 'keep-app') {
    define('EVO_CLASS', 'KeepProbeCore');
    class KeepProbeCore extends EvolutionCMS\Core
    {
        public int $initialized = 0;
        public function initialize()
        {
            $this->initialized++;
        }
    }
    WorkerRuntime::keepApplications(true);
    $boot = (new ReflectionClass(KeepProbeCore::class))->newInstanceWithoutConstructor();
    $boot->instance('config', new Repository(['app' => ['name' => 'boot']]));
    $boot->instance('events', new Illuminate\Events\Dispatcher($boot));
    $boot['events']->listen('boot.event', static fn () => 'boot');
    $results = [];
    WorkerRuntime::begin();
    WorkerRuntime::captureApplication($boot);
    foreach (['first', 'second'] as $request) {
        if ($request === 'second') {
            WorkerRuntime::begin();
        }
        $app = KeepProbeCore::getInstance();
        $results[] = [
            'same' => $app === $boot,
            'initialized' => $app->initialized,
            'container' => Container::getInstance() === $app,
            'config' => $app['config']->get('app.name'),
            'placeholder' => $app->placeholders['marker'] ?? null,
            'bound' => $app->bound('request.only'),
            'listeners' => count($app['events']->getListeners('boot.event')),
        ];
        $app['config']->set('app.name', 'dirty');
        $app->placeholders['marker'] = 'dirty';
        $app->instance('request.only', new stdClass());
        $app['events']->listen('boot.event', static fn () => 'request');
        WorkerRuntime::terminate();
        WorkerRuntime::releaseApplication();
    }
    echo json_encode($results);
}

if ($mode === 'session') {
    define('IN_MANAGER_MODE', false);
    define('IN_INSTALL_MODE', false);
    define('EVO_API_MODE', true);
    define('EVO_SESSION', true);
    define('EVO_CLASS', Container::class);
    require dirname(__DIR__, 2) . '/functions/session_proxy.php';
    class WorkerSessionStore
    {
        public array $data;
        public int $saves = 0;
        public function __construct(string $name) { $this->data = ['user' => $name]; }
        public function isStarted(): bool { return true; }
        public function getId(): string { return 'test-session'; }
        public function all(): array { return $this->data; }
        public function put($key, $value): void { $this->data[$key] = $value; }
        public function forget($key): void { unset($this->data[$key]); }
        public function save(): void { $this->saves++; }
    }
    $results = [];
    foreach (['alice', 'bob'] as $user) {
        WorkerRuntime::begin();
        $_COOKIE = [];
        $_SESSION = [];
        $store = new WorkerSessionStore($user);
        $GLOBALS['evo'] = $app = new Container();
        Container::setInstance($app);
        $app->instance('config', new Repository(['session' => ['cookie' => 'evo_session']]));
        $app->instance('session', $store);
        EvoSessionProxy::init();
        $_SESSION['changed'] = $user;
        WorkerRuntime::terminate();
        $results[] = ['session' => $_SESSION['user'], 'saved' => $store->data['changed'], 'saves' => $store->saves];
        WorkerRuntime::releaseApplication();
    }
    echo json_encode($results);
}
