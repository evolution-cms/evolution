<?php
// A page that ends past the StartSession middleware, in a fresh process.
// argv: <cookie:0|1> <manager:0|1> <set-cookie header:0|1> <sync:shutdown|direct>
[, $cookie, $manager, $setCookie, $sync] = $argv;
define('IN_MANAGER_MODE', (bool) $manager);
define('EVO_SESSION', true);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', true);
define('EVO_CLASS', Illuminate\Container\Container::class);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/functions/session_proxy.php';

class ShutdownSpyStore
{
    public array $calls = [];
    public array $data = [];
    public function isStarted(): bool { return true; }
    public function getId(): string { return 'spy-session-id'; }
    public function all(): array { return $this->data; }
    public function put($k, $v): void { $this->data[$k] = $v; }
    public function forget($k): void { unset($this->data[$k]); }
    public function save(): void { $this->calls[] = 'save'; }
}
$store = new ShutdownSpyStore();
$app = new Illuminate\Container\Container();
$app->instance('session', $store);
$app->instance('config', new Illuminate\Config\Repository(['session' => ['cookie' => 'evo_session']]));
Illuminate\Container\Container::setInstance($app);
$GLOBALS['evo'] = $app;

if ($cookie) {
    $_COOKIE['evo_session'] = 'spy-session-id';
}
EvoSessionProxy::init();
$_SESSION['visitor'] = 'x';

if ($sync === 'direct') {
    EvoSessionProxy::syncBack();
} else {
    EvoSessionProxy::syncBackAtShutdown($setCookie ? ['Content-Type: text/html', 'Set-Cookie: evo_session=spy-session-id; path=/'] : ['Set-Cookie: other=1']);
}

echo json_encode(['saves' => $store->calls, 'data' => $store->data]);
