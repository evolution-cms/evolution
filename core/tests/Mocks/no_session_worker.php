<?php
// Boots EvoSessionProxy in a fresh process with the given constants and reports
// what it did to the session store. argv: <no_session:0|1|-> <manager:0|1>
[, $noSession, $manager] = $argv;
if ($noSession !== '-') {
    define('NO_SESSION', (bool) $noSession);
}
define('IN_MANAGER_MODE', (bool) $manager);
define('EVO_SESSION', true);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', true);
define('EVO_CLASS', Illuminate\Container\Container::class);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/functions/session_proxy.php';

class SpyStore
{
    public array $calls = [];
    public function isStarted(): bool { return false; }
    public function start(): void { $this->calls[] = 'start'; }
    public function getId(): string { return 'spy-session-id'; }
    public function all(): array { return []; }
    public function put($k, $v): void { $this->calls[] = 'put'; }
    public function forget($k): void { $this->calls[] = 'forget'; }
    public function save(): void { $this->calls[] = 'save'; }
}
$store = new SpyStore();
$app = new Illuminate\Container\Container();
$app->instance('session', $store);
$app->instance('config', new Illuminate\Config\Repository(['session' => ['cookie' => 'evo_session']]));
Illuminate\Container\Container::setInstance($app);
$GLOBALS['evo'] = $app; // evo()/app() hand out the container

EvoSessionProxy::init();
$_SESSION['visitor'] = 'x';
EvoSessionProxy::syncBack();

echo json_encode([
    'disabled' => EvoSessionProxy::disabled(),
    'store' => $store->calls,
    'sessionIsArray' => isset($_SESSION) && is_array($_SESSION),
    'phpSession' => session_status() === PHP_SESSION_ACTIVE,
    'middleware' => EvoSessionProxy::filterMiddleware([
        Illuminate\Session\Middleware\StartSession::class,
        EvolutionCMS\Middleware\SessionProxy::class,
        Illuminate\Routing\Middleware\SubstituteBindings::class,
        Illuminate\View\Middleware\ShareErrorsFromSession::class,
    ]),
]);
