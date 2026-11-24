<?php
// The front end of a site in miniature, served over HTTP because the CLI sends no headers: the real
// Core::processRoutes(), router, session middleware, executeParser() and output, without a database.
//   /routed, /routed-exit       a route of the `web` group that returns its page, or ends in exit
//   /routed-abort/<status>      a route that aborts
//   anything else               the parser: a document, or ?do=exit|redirect|none|later|abort
// A POST answers whether its token is the one of the session.
use EvolutionCMS\Core;
use EvolutionCMS\Extensions\Router;
use EvolutionCMS\Middleware\SessionProxy;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Response;
use Illuminate\Routing\CallableDispatcher;
use Illuminate\Routing\Contracts\CallableDispatcher as CallableDispatcherContract;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Facade;

ob_start();
define('IN_MANAGER_MODE', false);
define('IN_PARSER_MODE', false);
define('EVO_SESSION', true);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', false);
define('EVO_SANITIZE_SEED', 'seed');
define('EVO_SITE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/');
define('EVO_CLASS', Core::class);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/functions/session_proxy.php';
require_once dirname(__DIR__, 2) . '/functions/laravel.php';

function formPage(): string
{
    $sent = $_POST['_token'] ?? null;
    if ($sent !== null) {
        return is_string($sent) && hash_equals((string) ($_SESSION['_token'] ?? ''), $sent) ? 'accepted' : 'rejected';
    }
    $form = csrf_field() . csrf_field();
    $_SESSION['later'] = 'written after the token';

    return $form;
}

class DLTemplate
{
    public static function getInstance($core): self
    {
        return new self();
    }

    public function setTemplatePath($path): void
    {
    }
}

class FrontEndPageCore extends Core
{
    public function prepareResponse()
    {
        $do = $_GET['do'] ?? '';
        if ($do === 'exit') {
            echo formPage();
            exit;
        }
        if ($do === 'redirect') {
            csrf_token();
            $this->sendRedirect('/elsewhere');
        }
        if ($do === 'abort') {
            // what a snippet printed, in the buffer evalSnippet() leaves open
            echo 'printed before ';
            ob_start();
            echo 'the abort';
            abort((int) $_GET['status']);
        }
        $this->documentContent = match ($do) {
            'later' => (string) ($_SESSION['later'] ?? ''),
            'none' => 'no form here',
            default => formPage(),
        };
        $this->outputContent(true, false);
    }

    public function sendErrorPage($noEvent = false)
    {
        http_response_code(404);
        echo 'the error page of the site';
        exit;
    }

    // what of the parser needs a database

    public function checkSiteStatus()
    {
        return true;
    }

    public function updatePubStatus()
    {
    }

    public function getDocumentIdentifier($method)
    {
        return 1;
    }

    public function getConfig($name = '', $default = null)
    {
        return $default;
    }

    public function invokeEvent($evtName, $extParams = [])
    {
        return false;
    }

    public function setConditional()
    {
    }
}

$core = (new ReflectionClass(FrontEndPageCore::class))->newInstanceWithoutConstructor();
$core->tstart = microtime(true);
Container::setInstance($core);
Facade::setFacadeApplication($core);
$GLOBALS['evo'] = $core;
$core->instance('app', $core);
$core->instance('config', new Repository([
    'session' => [
        'driver' => 'file', 'files' => getenv('FRONT_END_SESSIONS'), 'lifetime' => 120, 'expire_on_close' => false,
        'lottery' => [0, 100], 'cookie' => 'evo_session', 'path' => '/', 'domain' => null, 'secure' => false,
        'http_only' => true, 'same_site' => 'lax',
    ],
    'app' => ['middleware' => ['global' => [StartSession::class, SessionProxy::class], 'aliases' => []]],
]));
$core->instance('files', new Filesystem());
$core->instance('events', new Dispatcher($core));
$core->instance('session', $sessions = new SessionManager($core));
$core->singleton(StartSession::class, static fn () => new StartSession($sessions));
$core->singleton(CallableDispatcherContract::class, static fn () => new CallableDispatcher($core));
$core->instance('router', $router = new Router($core['events'], $core));

$router->middleware('web')->group(static function () use ($router) {
    $router->match(['GET', 'POST'], '/routed', static fn () => new Response(formPage()));
    $router->get('/routed-exit', static function () {
        echo formPage();
        exit;
    });
    $router->get('/routed-abort/{status}', static function ($status) {
        if ($status === 'response') {
            abort(new Response('a response of its own', 409));
        }
        echo 'printed before the abort';
        abort((int) $status, 'refused <b>here</b>', ['X-Reason' => 'test']);
    });
});
$router->fallbackToParser();

EvoSessionProxy::init();
$core->processRoutes();
