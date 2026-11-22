<?php
// A routed request in which native session code and the Laravel store both write, in a fresh process.
define('IN_MANAGER_MODE', false);
define('EVO_SESSION', true);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', true);
define('EVO_CLASS', Illuminate\Container\Container::class);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/includes/worker.runtime.php';
require_once dirname(__DIR__, 2) . '/functions/session_proxy.php';

class NativeKeysSpyStore
{
    public array $data = [];
    public function isStarted(): bool { return true; }
    public function getId(): string { return 'spy-session-id'; }
    public function all(): array { return $this->data; }
    public function put($k, $v): void { $this->data[$k] = $v; }
    public function forget($k): void { unset($this->data[$k]); }
    public function save(): void {}
}
$store = new NativeKeysSpyStore();
// What the previous requests left in the store.
$store->data = [
    '_token' => 'old-token',
    '_flash' => ['old' => [], 'new' => []],
    '_flashMessages' => ['success' => ['Goodbye!']],
    '_native_stale' => 'remove me',
    'visitor' => 'x',
];
$app = new Illuminate\Container\Container();
$app->instance('session', $store);
$app->instance('config', new Illuminate\Config\Repository(['session' => ['cookie' => 'evo_session']]));
Illuminate\Container\Container::setInstance($app);
$GLOBALS['evo'] = $app;
$_COOKIE['evo_session'] = 'spy-session-id';

EvoSessionProxy::init();
$seen = $_SESSION;

// Native code: a flash library shows its message and queues another, and drops a key of its own.
$_SESSION['_flashMessages'] = ['success' => ['Thanks']];
unset($_SESSION['_native_stale']);
// Laravel, during the same request: a new CSRF token; $_SESSION still holds the old one.
$store->put('_token', 'new-token');

EvoSessionProxy::syncBack();

echo json_encode(['seen' => $seen, 'data' => $store->data]);
