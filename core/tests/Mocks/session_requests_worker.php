<?php
// Requests of one visitor over the real Laravel store, in a fresh process.
// argv: <native:0|1> <routed:0|1>
// CLI starts no native session: native=0 rebuilds $_SESSION from the store alone, as a worker does;
// native=1 hands over the $_SESSION of the request before, as the native session file does.
// routed=1 goes through StartSession and SessionProxy, routed=0 is a page synced at its end.
define('IN_MANAGER_MODE', false);
define('EVO_SESSION', true);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', true);
define('EVO_CLASS', Illuminate\Container\Container::class);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/includes/worker.runtime.php';
require_once dirname(__DIR__, 2) . '/functions/session_proxy.php';

use EvolutionCMS\Middleware\SessionProxy;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;
use Illuminate\Session\Store;

[, $native, $routed] = $argv;
$directory = sys_get_temp_dir() . '/evo-session-sync-' . bin2hex(random_bytes(8));
mkdir($directory);
register_shutdown_function(static function () use ($directory) {
    array_map('unlink', glob("$directory/*") ?: []);
    rmdir($directory);
});
$app = new Container();
$app->instance('config', new Repository(['session' => [
    'driver' => 'file', 'files' => $directory, 'lifetime' => 120, 'expire_on_close' => false,
    'lottery' => [0, 100], 'cookie' => 'evo_session', 'path' => '/', 'domain' => null, 'secure' => false,
    'http_only' => true, 'same_site' => 'lax',
]]));
$app->instance('files', new Filesystem());
Container::setInstance($app);
$GLOBALS['evo'] = $app;
$_COOKIE['evo_session'] = str_repeat('a', 40);
$_SESSION = [];

$request = static function (callable $page, int $at = 1000) use ($app, $native, $routed): array {
    $app->instance('session', $sessions = new SessionManager($app));
    if (!$native) {
        $_SESSION = [];
    }
    $_SERVER['REQUEST_TIME'] = $at;
    foreach (['initialized' => false, 'synced' => false, 'nativeSnapshot' => [],
        'fingerprints' => [], 'shared' => []] as $property => $value) {
        (new ReflectionProperty(EvoSessionProxy::class, $property))->setValue(null, $value);
    }
    EvoSessionProxy::earlyInit();
    EvoSessionProxy::init();
    $store = $sessions->driver();
    $seen = $_SESSION;
    $result = null;

    if (!$routed) {
        $result = $page($store);
        EvoSessionProxy::syncBack();
    } else {
        $http = Request::create('/page', 'GET', [], ['evo_session' => $_COOKIE['evo_session']]);
        $next = static function () use ($page, $store, &$result) {
            $result = $page($store);

            return new Response();
        };
        (new StartSession($sessions))->handle($http, static fn ($http) => (new SessionProxy())->handle($http, $next));
    }
    $_COOKIE['evo_session'] = $store->getId();

    return ['seen' => $seen, 'result' => $result, 'id' => $store->getId()];
};

$first = $request(static function (Store $store) {
    $_SESSION['shop'] = 'open';
    $_SESSION['shop.cart'] = ['a.b' => 1];
    $_SESSION['_native.dotted'] = 'native';
    $_SESSION['gone.soon'] = 'x';
    $_SESSION['both'] = 'native 1';
    $_SESSION['forgotten'] = 'x';
    $_SESSION['object'] = new ArrayObject(['in place' => 1]);
    $store->put('laravel', 'put');
    $store->put('nested.key', 'value');
    $store->flash('notice', 'saved');
    $store->flash('_old_input', ['name' => 'typed']);
}, 1000);
$second = $request(static function (Store $store) {
    $_SESSION['shop.cart'] = ['a.b' => 2];
    unset($_SESSION['gone.soon']);
    $_SESSION['object']['in place'] = 2;
    $store->put('both', 'laravel 2');
    $store->forget('forgotten');

    return array_intersect_key($store->all(), array_flip(['laravel', 'notice', '_old_input', 'nested']))
        + ['cart' => $store->get('shop.cart')];
}, 2000);
$third = $request(static function (Store $store) {
    $_SESSION['both'] = 'native 3';

    return $store->get('both');
}, 3000);
$object = ($third['seen']['object'] ?? new ArrayObject())->getArrayCopy();
$fourth = $request(static fn (Store $store) => $store->invalidate());
$fifth = $request(static fn () => null, 5000);

// The same name in both notations, and objects the store and $_SESSION share.
$request(static function () {
    $_SESSION['cart.notice'] = 'native';
    $_SESSION['cart.now'] = 'native';
    $_SESSION['cart.total'] = 1;
    $_SESSION['box'] = new ArrayObject(['n' => 1]);
    $_SESSION['boxes'] = [new ArrayObject(['n' => 1])];
    $_SESSION['swapped'] = new ArrayObject(['n' => 1]);
    $_SESSION['reassigned'] = new ArrayObject(['n' => 1]);
}, 6000);
$request(static function (Store $store) {
    $store->flash('cart.notice', 'saved');
    $store->now('cart.now', 'laravel');
    $store->forget('cart.total');
    $store->put('cart.total', 2);
    unset($_SESSION['cart.total']);
    $store->get('box')->offsetSet('n', 2);
    $store->forget('box');
    $store->get('boxes')[0]->offsetSet('n', 2);
    $store->forget('boxes');
    $store->get('swapped')->offsetSet('n', 2);
    $store->put('swapped', 'laravel');
    $_SESSION['reassigned'] = 'native';
    $store->forget('reassigned');
}, 7000);
$flashed = $request(static fn () => null, 8000)['seen'];
$expired = $request(static fn () => null, 9000)['seen'];
$later = $request(static fn () => null, 10000)['seen'];

// What the proxy stored before it kept keys with a dot flat: the time of the last request, nested.
$upgraded = [];
foreach (['lost native file' => [], 'native file' => ['evo.session.created.time' => 500]] as $kind => $nativeFile) {
    $_COOKIE['evo_session'] = str_repeat($nativeFile === [] ? 'b' : 'c', 40);
    $_SESSION = [];
    $old = (new SessionManager($app))->driver();
    $old->setId($_COOKIE['evo_session']);
    $old->put('evo.session.created.time', 800);
    $old->put('evo.other', 'kept');
    $old->save();
    $_SESSION = $nativeFile;
    $upgraded[$kind] = [];
    foreach ([11000, 12000] as $at) {
        $upgraded[$kind][] = $request(static fn () => null, $at)['seen'];
    }
}

echo json_encode(compact('first', 'second', 'third', 'fourth', 'fifth', 'object', 'flashed', 'expired', 'later', 'upgraded'));
