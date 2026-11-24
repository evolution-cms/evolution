<?php
// A front-end page that ends past the StartSession middleware, served over HTTP: the CLI sends no headers.
// ?call=token|none|redirect
define('IN_MANAGER_MODE', false);
define('EVO_SESSION', true);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', true);
define('EVO_CLASS', Illuminate\Container\Container::class);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/functions/session_proxy.php';
require_once dirname(__DIR__, 2) . '/functions/laravel.php';

$store = new class {
    public array $data = ['_token' => 'store-token'];
    public int $saves = 0;
    public function isStarted(): bool { return true; }
    public function getId(): string { return 'guestsessionid'; }
    public function all(): array { return $this->data; }
    public function put($k, $v): void { $this->data[$k] = $v; }
    public function forget($k): void { unset($this->data[$k]); }
    public function save(): void { $this->saves++; }
};
$app = new Illuminate\Container\Container();
$app->instance('session', $store);
$app->instance('config', new Illuminate\Config\Repository(['session' => ['cookie' => 'evo_session']]));
Illuminate\Container\Container::setInstance($app);
$GLOBALS['evo'] = $app;

EvoSessionProxy::init();
$token = '';
if ($_GET['call'] !== 'none') {
    $token = csrf_token();
    csrf_token();
}
if ($_GET['call'] === 'redirect') {
    EvoSessionProxy::saveAndEmitCookie();
}
$_SESSION['later'] = 'written after the token';
EvoSessionProxy::syncBackAtShutdown();

echo json_encode(['token' => $token, 'saves' => $store->saves, 'data' => $store->data]);
