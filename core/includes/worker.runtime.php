<?php

namespace EvolutionCMS\Runtime;

use EvolutionCMS\Bootstrap\EnvCacheLoader;
use EvolutionCMS\Core;
use EvolutionCMS\Parser;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

final class RequestTerminated extends \Error
{
}

/**
 * Persistent PHP code, with a fresh CMS container and session for each request.
 * With EVO_WORKER_KEEP_APP=1 the booted container is kept instead and reset to its post-boot state.
 */
final class WorkerRuntime
{
    /**
     * Static callback lists that providers append to on every boot. Each closure holds the container of
     * its request, so a fresh container per request would keep all previous ones alive. Names may be aliases.
     */
    private const BOOT_REGISTRIES = [
        ['Illuminate\Console\Application', 'bootstrappers'],
        ['Illuminate\Foundation\Http\Middleware\TrimStrings', 'skipCallbacks'],
        ['Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull', 'skipCallbacks'],
    ];

    private static bool $active = false;
    private static array $callbacks = [];
    private static bool $keepApplication = false;
    private static ?ApplicationSnapshot $snapshot = null;

    public static function active(): bool
    {
        return self::$active;
    }

    public static function defer(callable $callback): void
    {
        self::$callbacks[] = $callback;
    }

    public static function begin(): void
    {
        self::$callbacks = [];
        self::$active = true;
    }

    public static function terminate(): void
    {
        $failure = null;
        try {
            for ($i = 0; self::$callbacks !== []; $i++) {
                if ($i >= 1000) {
                    throw new \RuntimeException('Too many worker request cleanup callbacks.');
                }
                $callback = array_shift(self::$callbacks);
                try {
                    $callback();
                } catch (RequestTerminated) {
                } catch (\Throwable $exception) {
                    $failure ??= $exception;
                }
            }
            if ($failure !== null) {
                throw $failure;
            }
        } finally {
            self::$callbacks = [];
            self::$active = false;
        }
    }

    public static function origin(array $server): ?string
    {
        $host = strtolower((string) ($server['HTTP_HOST'] ?? ''));
        if (!preg_match('/^(?:[a-z0-9._-]+|\[[a-f0-9:.]+\])(?::[0-9]{1,5})?$/D', $host)) {
            return null;
        }
        $https = strtolower((string) ($server['HTTPS'] ?? '')) === 'on'
            || (int) ($server['SERVER_PORT'] ?? 0) === (defined('HTTPS_PORT') ? (int) HTTPS_PORT : 443)
            || ($server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        $scheme = $https ? 'https' : 'http';
        $parts = parse_url($scheme . '://' . $host);
        if ($parts === false || isset($parts['port']) && ($parts['port'] < 1 || $parts['port'] > 65535)) {
            return null;
        }
        $port = $parts['port'] ?? ($https ? 443 : 80);

        return $scheme . '://' . $parts['host'] . ($port === ($https ? 443 : 80) ? '' : ':' . $port);
    }

    /** host[:port] of an origin() value, in the form define.inc.php reads from HTTP_HOST. */
    public static function authority(string $origin): string
    {
        return substr($origin, strpos($origin, '://') + 3);
    }

    /** Origins of an EVO_SITE_HOSTNAMES list; as in define.inc.php, an entry without a port means the scheme's default. */
    public static function hostnameOrigins(string $hostnames, string $scheme): array
    {
        $origins = [];
        foreach (explode(',', $hostnames) as $entry) {
            $entry = trim($entry);
            $origin = $entry === '' ? null : self::origin(['HTTP_HOST' => $entry, 'HTTPS' => $scheme === 'https' ? 'on' : 'off']);
            if ($origin !== null) {
                $origins[] = $origin;
            }
        }

        return array_values(array_unique($origins));
    }

    /**
     * The other origins of the site, which a worker redirects to its own one.
     *
     * EVO_SITE_URL and the constants derived from it are fixed by the first request of a worker,
     * so EVO_WORKER_ORIGIN must be the site URL that EVO_SITE_HOSTNAMES and custom/define.php yield.
     */
    public static function siteAliases(string $expectedOrigin, string $hostnames, ?string $siteUrl): array
    {
        $origins = self::hostnameOrigins($hostnames, (string) strstr($expectedOrigin, '://', true));
        if ($origins !== [] && !in_array($expectedOrigin, $origins, true)) {
            throw new \RuntimeException("EVO_WORKER_ORIGIN {$expectedOrigin} is not listed in EVO_SITE_HOSTNAMES.");
        }
        if ($siteUrl !== null) {
            $url = parse_url($siteUrl);
            $origin = is_array($url) && isset($url['scheme'], $url['host']) ? self::origin([
                'HTTP_HOST' => $url['host'] . (isset($url['port']) ? ':' . $url['port'] : ''),
                'HTTPS' => strtolower($url['scheme']) === 'https' ? 'on' : 'off',
            ]) : null;
            if ($origin !== $expectedOrigin) {
                throw new \RuntimeException("EVO_SITE_URL does not belong to EVO_WORKER_ORIGIN {$expectedOrigin}.");
            }
        }

        return array_values(array_diff($origins, [$expectedOrigin]));
    }

    /**
     * Null to serve the request, otherwise [status, Location or null].
     * Another hostname of the site is redirected only within the same scheme: behind a proxy that
     * drops X-Forwarded-Proto, an http to https redirect would never end.
     */
    public static function misdirected(array $server, string $expectedOrigin, array $aliases): ?array
    {
        $origin = self::origin($server);
        if ($origin === $expectedOrigin) {
            return null;
        }
        if ($origin !== null && in_array($origin, $aliases, true)
            && strstr($origin, '://', true) === strstr($expectedOrigin, '://', true)) {
            $uri = (string) ($server['REQUEST_URI'] ?? '/');

            return [308, $expectedOrigin . (preg_match('~^/[^\x00-\x20\x7f]*$~D', $uri) ? $uri : '/')];
        }

        return [421, null];
    }

    public static function restoreHandlers(?callable $errorHandler, ?callable $exceptionHandler): void
    {
        for ($i = 0; $i < 100; $i++) {
            $current = set_error_handler($errorHandler);
            restore_error_handler();
            if ($current === $errorHandler) {
                break;
            }
            restore_error_handler();
        }
        for ($j = 0; $j < 100; $j++) {
            $current = set_exception_handler($exceptionHandler);
            restore_exception_handler();
            if ($current === $exceptionHandler) {
                break;
            }
            restore_exception_handler();
        }
        if ($i === 100 || $j === 100) {
            throw new \RuntimeException('Unable to restore worker error handlers.');
        }
    }

    public static function keepsApplication(): bool
    {
        return self::$keepApplication && self::$active;
    }

    /** Called by Core once its providers have booted, before the request is initialized. */
    public static function captureApplication(Core $app): void
    {
        if (self::keepsApplication() && self::$snapshot === null) {
            self::$snapshot = new ApplicationSnapshot($app);
            if (getenv('EVO_WORKER_DEBUG') === '1') {
                error_log('[Evo worker] kept services: ' . implode(', ', self::$snapshot->services()));
            }
        }
    }

    /** The kept application initialized for this request, or null when Core must build one. */
    public static function reuseApplication(string $class): ?Core
    {
        $app = self::$snapshot?->application();
        if ($app === null || !self::keepsApplication()) {
            return null;
        }
        if (!$app instanceof $class) {
            throw new \LogicException("The kept application is not a {$class}.");
        }
        Container::setInstance($app);
        Facade::setFacadeApplication($app);
        $app->tstart = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        $app->initialize();

        return $app;
    }

    /** Test seam: start (or stop, with false) keeping applications outside run(). */
    public static function keepApplications(bool $keep): void
    {
        self::$keepApplication = $keep;
        self::$snapshot = null;
    }

    /**
     * OPcache revalidates timestamps against the request start time, which a worker does not advance:
     * recheck the PHP cache files that the manager, CLI or cron of another process rewrite.
     */
    public static function revalidateRuntimeFiles(string $corePath): void
    {
        if (!function_exists('opcache_invalidate')) {
            return;
        }
        $storage = rtrim(defined('EVO_STORAGE_PATH') ? EVO_STORAGE_PATH : $corePath . '/storage/', '/') . '/';
        foreach (['bootstrap/siteCache.idx.php', 'bootstrap/sitePublishing.idx.php', 'cache/env.php'] as $file) {
            // Without force, only a file newer than its cached script is invalidated.
            opcache_invalidate($storage . $file);
        }
    }

    public static function forgetBootRegistries(): void
    {
        foreach (self::BOOT_REGISTRIES as [$class, $name]) {
            if (class_exists($class, false) && property_exists($class, $name)) {
                $property = new \ReflectionProperty($class, $name);
                if ($property->isStatic() && $property->hasDefaultValue()) {
                    $property->setValue(null, $property->getDefaultValue());
                }
            }
        }
    }

    public static function run(string $frontController, string $corePath): void
    {
        if (!($_SERVER['FRANKENPHP_WORKER'] ?? false) || !function_exists('frankenphp_handle_request')) {
            throw new \RuntimeException('FrankenPHP worker mode is required.');
        }
        $configuredOrigin = rtrim((string) getenv('EVO_WORKER_ORIGIN'), '/');
        $url = parse_url($configuredOrigin);
        if ($url === false || !isset($url['scheme'], $url['host'])
            || !in_array($url['scheme'], ['http', 'https'], true)
            || array_diff(array_keys($url), ['scheme', 'host', 'port']) !== []) {
            throw new \RuntimeException('Set EVO_WORKER_ORIGIN to the canonical http(s) origin of this worker.');
        }
        $expectedOrigin = self::origin([
            'HTTP_HOST' => $url['host'] . (isset($url['port']) ? ':' . $url['port'] : ''),
            'HTTPS' => $url['scheme'] === 'https' ? 'on' : 'off',
        ]);
        if ($expectedOrigin === null) {
            throw new \RuntimeException('Invalid EVO_WORKER_ORIGIN.');
        }
        $configuredMaxRequests = getenv('EVO_WORKER_MAX_REQUESTS');
        $maxRequests = filter_var($configuredMaxRequests === false || $configuredMaxRequests === '' ? '500' : $configuredMaxRequests, FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 100000]]);
        if ($maxRequests === false) {
            throw new \RuntimeException('EVO_WORKER_MAX_REQUESTS must be between 1 and 100000.');
        }
        self::$keepApplication = getenv('EVO_WORKER_KEEP_APP') === '1';
        define('EVO_WORKER_MODE', true);
        require_once $corePath . '/functions/laravel.php';
        require_once $corePath . '/vendor/autoload.php';
        // .env and custom/define.php hold request-independent settings; the guards below need them before the first request.
        require_once $corePath . '/includes/custom-define.inc.php';
        $aliases = self::siteAliases(
            $expectedOrigin,
            defined('EVO_SITE_HOSTNAMES') ? (string) EVO_SITE_HOSTNAMES
                : (string) env('REDIRECT_EVO_SITE_HOSTNAMES', env('EVO_SITE_HOSTNAMES', '')),
            defined('EVO_SITE_URL') ? (string) EVO_SITE_URL : null
        );
        $authority = self::authority($expectedOrigin);
        ignore_user_abort(true);
        register_shutdown_function(static function (): void {
            // A legacy native exit or fatal error bypasses the request's finally block.
            if (self::active()) {
                try {
                    if (class_exists('EvoSessionProxy', false)) {
                        \EvoSessionProxy::saveAndEmitCookie();
                    }
                } catch (\Throwable $exception) {
                    error_log('[Evo worker shutdown] ' . $exception);
                } finally {
                    try {
                        self::terminate();
                    } catch (\Throwable $exception) {
                        error_log('[Evo worker shutdown cleanup] ' . $exception);
                    }
                }
            }
        });

        $globals = $GLOBALS;
        $environment = $_ENV;
        $directory = dirname($frontController);
        $timezone = date_default_timezone_get();
        $ini = ini_get_all();
        $errorHandler = set_error_handler(null);
        restore_error_handler();
        $exceptionHandler = set_exception_handler(null);
        restore_exception_handler();
        $workerId = bin2hex(random_bytes(8));
        $debug = getenv('EVO_WORKER_DEBUG') === '1';
        $retire = false;
        $requestNumber = 0;

        $handler = static function () use ($frontController, $corePath, $expectedOrigin, $aliases, $authority, $directory,
            $timezone, $ini, $globals, $environment, $workerId, $debug, $errorHandler, $exceptionHandler,
            &$retire, &$requestNumber): void {
            $requestNumber++;
            http_response_code(200);
            $misdirected = self::misdirected($_SERVER, $expectedOrigin, $aliases);
            if ($misdirected !== null) {
                http_response_code($misdirected[0]);
                if ($misdirected[1] !== null) {
                    header('Location: ' . $misdirected[1]);
                    return;
                }
                header('Content-Type: text/plain; charset=UTF-8');
                echo 'Misdirected Request';
                return;
            }
            // The first request defines EVO_SITE_URL for the whole worker: never in the letter case a client chose.
            $_SERVER['HTTP_HOST'] = $authority;
            $path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
            $manager = defined('MGR_DIR') ? MGR_DIR : (getenv('MGR_DIR') ?: 'manager');
            if (preg_match('~^/(?:' . preg_quote($manager, '~') . '|install)(?:/|$)~i', $path)) {
                http_response_code(404);
                if ($debug) {
                    header('X-Evo-Worker-Guard: frontend-only');
                }
                return;
            }

            chdir($directory);
            $_SESSION = [];
            $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
            $_SERVER['REQUEST_TIME'] = time();
            error_clear_last();
            ini_set('display_errors', '0');
            set_time_limit((int) ($ini['max_execution_time']['local_value'] ?? 30));
            self::begin();
            $bufferLevel = ob_get_level();
            ob_start();
            if ($debug) {
                header('X-Evo-Worker: ' . $workerId);
                header('X-Evo-Request: ' . $requestNumber);
                if (self::$keepApplication) {
                    header('X-Evo-Worker-App: ' . (self::$snapshot === null ? 'new' : 'kept'));
                }
            }
            try {
                self::revalidateRuntimeFiles($corePath);
                if (class_exists(EnvCacheLoader::class, false)) {
                    EnvCacheLoader::load($directory);
                }
                // Includes run in a function scope; request locals never become worker globals.
                (static function (string $entry): void { require $entry; })($frontController);
            } catch (RequestTerminated) {
                // evo_exit() ends this request while leaving the PHP worker alive.
            } catch (\Throwable $exception) {
                $retire = true;
                error_log('[Evo worker] ' . $exception);
                while (ob_get_level() > $bufferLevel) {
                    ob_end_clean();
                }
                ob_start();
                if (!headers_sent()) {
                    header_remove();
                    http_response_code(500);
                    header('Content-Type: text/plain; charset=UTF-8');
                    header('Cache-Control: no-store');
                }
                echo 'Internal Server Error';
            } finally {
                try {
                    if (class_exists('EvoSessionProxy', false)) {
                        \EvoSessionProxy::saveAndEmitCookie();
                    }
                } catch (\Throwable $exception) {
                    $retire = true;
                    error_log('[Evo worker cleanup] ' . $exception);
                } finally {
                    try {
                        self::terminate();
                    } catch (\Throwable $exception) {
                        $retire = true;
                        error_log('[Evo worker callbacks] ' . $exception);
                    }
                    try {
                        self::releaseApplication();
                        self::restoreHandlers($errorHandler, $exceptionHandler);
                    } catch (\Throwable $exception) {
                        $retire = true;
                        error_log('[Evo worker release] ' . $exception);
                    }
                    while (ob_get_level() > $bufferLevel) {
                        ob_end_flush();
                    }
                    $_SESSION = [];
                    $_ENV = $environment;
                    foreach (array_keys($GLOBALS) as $key) {
                        if (!in_array($key, ['GLOBALS', '_SERVER', '_GET', '_POST', '_COOKIE', '_FILES', '_REQUEST', '_SESSION', '_ENV', '__composer_autoload_files'], true)
                            && !array_key_exists($key, $globals)) {
                            unset($GLOBALS[$key]);
                        }
                    }
                    foreach ($globals as $key => $value) {
                        if (!in_array($key, ['GLOBALS', '_SERVER', '_GET', '_POST', '_COOKIE', '_FILES', '_REQUEST', '_SESSION', '_ENV', '__composer_autoload_files'], true)) {
                            $GLOBALS[$key] = $value;
                        }
                    }
                    date_default_timezone_set($timezone);
                    foreach ($ini as $key => $setting) {
                        if (($setting['access'] & INI_USER) && ini_get($key) !== $setting['local_value']) {
                            @ini_set($key, $setting['local_value']);
                        }
                    }
                    chdir($directory);
                    gc_collect_cycles();
                }
            }
        };

        for ($i = 0; $i < $maxRequests; $i++) {
            if (!frankenphp_handle_request($handler) || $retire) {
                break;
            }
        }
    }

    public static function releaseApplication(): void
    {
        // Once captured, the kept application serves every request of this worker, whether or not it reached evo().
        $kept = self::$snapshot !== null;
        $app = $kept ? self::$snapshot->application() : ($GLOBALS['evo'] ?? Container::getInstance());
        if ($app instanceof Core) {
            if ($app->resolved('ExceptionHandler')) {
                $app->getService('ExceptionHandler')->restoreHandlers();
            }
            if ($app->resolved('db')) {
                foreach ($app['db']->getConnections() as $connection) {
                    if ($connection->transactionLevel() > 0) {
                        $connection->rollBack(0);
                    }
                    $connection->disconnect();
                    if ($kept) {
                        $connection->flushQueryLog();
                        $connection->forgetRecordModificationState();
                        $connection->resetTotalQueryDuration();
                        $connection->allowQueryDurationHandlersToRunAgain();
                    }
                }
            }
            if ($kept) {
                self::$snapshot->restore();
            } else {
                $app->flush();
            }
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        session_id('');
        if (class_exists('EvoSessionProxy', false)) {
            \EvoSessionProxy::resetForWorker();
        }
        if (class_exists(Parser::class, false)) {
            Parser::resetForWorker();
        }
        if (class_exists(\EvolutionCMS\Services\ManagerActivity::class, false)) {
            \EvolutionCMS\Services\ManagerActivity::reset();
        }
        foreach ([\EvolutionCMS\Support\TvBindingArguments::class, \EvolutionCMS\Support\TvBindingGuard::class] as $class) {
            if (class_exists($class, false)) {
                $class::resetForWorker();
            }
        }
        if (class_exists(Model::class, false)) {
            Model::reguard();
            // A kept application keeps the models booted before its snapshot; restore() forgets the later ones.
            if (!$kept) {
                Model::clearBootedModels();
                Model::unsetConnectionResolver();
                Model::unsetEventDispatcher();
            }
        }
        Facade::clearResolvedInstances();
        if (!$kept) {
            Facade::setFacadeApplication(null);
            self::forgetBootRegistries();
        }
        Container::setInstance(null);
        EnvCacheLoader::resetForWorker();
        unset($GLOBALS['evo'], $GLOBALS['modx']);
    }
}

/**
 * Post-boot state of a kept application, put back after every request.
 *
 * The properties of the container, of Core and of every service resolved while providers booted return
 * to their post-boot values: whatever a request bound, resolved, registered or set on them is dropped.
 * Objects nested inside those services are shared by all requests; their owners reset them through
 * flushState() (Laravel's and Livewire's convention) or Symfony's ResetInterface. The routes of the router
 * forget their controllers, and Eloquent's static boot registry returns to its post-boot value.
 */
final class ApplicationSnapshot
{
    /** @var list<array{\ReflectionProperty, object, mixed}> */
    private array $properties = [];
    /** @var list<\Closure> */
    private array $resets = [];
    private array $services = [];

    public function __construct(private readonly Core $app)
    {
        $this->capture($app);
        $this->services = (new \ReflectionProperty(Container::class, 'instances'))->getValue($app);
        $captured = [spl_object_id($app) => true];
        $services = [];
        foreach ($this->services as $service) {
            if (is_object($service) && $service !== $app && !$service instanceof \Closure) {
                $services[spl_object_id($service)] = $service;
            }
        }
        foreach ($services as $service) {
            $this->captureResets($service);
        }
        foreach ($services as $id => $service) {
            if (!isset($captured[$id])) {
                $captured[$id] = true;
                $from = count($this->properties);
                $this->capture($service);
                // One level further: the objects a service holds directly, such as the view finder or the route collection.
                foreach (array_slice($this->properties, $from) as [, , $value]) {
                    if (is_object($value) && !$value instanceof \Closure && !isset($captured[spl_object_id($value)])) {
                        $captured[spl_object_id($value)] = true;
                        $this->capture($value);
                    }
                }
            }
        }
        $this->captureModelBootState();
        if (class_exists(\Illuminate\Support\Once::class)) {
            $this->resets[] = static fn () => \Illuminate\Support\Once::flush();
        }
    }

    public function application(): Core
    {
        return $this->app;
    }

    /** @return list<string> */
    public function services(): array
    {
        return array_keys($this->services);
    }

    public function restore(): void
    {
        foreach ($this->properties as [$property, $object, $value]) {
            $property->setValue($object, $value);
        }
        $this->app->forgetScopedInstances();
        foreach ($this->resets as $reset) {
            $reset();
        }
    }

    private function capture(object $object): void
    {
        for ($class = new \ReflectionClass($object); $class !== false; $class = $class->getParentClass()) {
            foreach ($class->getProperties() as $property) {
                if (!$property->isStatic() && !$property->isReadOnly()
                    && $property->getDeclaringClass()->getName() === $class->getName()
                    && $property->isInitialized($object)) {
                    $this->properties[] = [$property, $object, $property->getValue($object)];
                }
            }
        }
    }

    /**
     * A model boots once per process and registers its listeners on the shared dispatcher, whose listeners
     * restore() puts back: a model first booted by a request must boot again in the next one, and only such a model.
     */
    private function captureModelBootState(): void
    {
        $state = [];
        foreach (['booting', 'booted', 'bootedCallbacks', 'traitInitializers', 'globalScopes', 'classAttributes', 'dispatcher', 'resolver'] as $name) {
            if (property_exists(Model::class, $name)) {
                $property = new \ReflectionProperty(Model::class, $name);
                $state[] = [$property, $property->getValue()];
            }
        }
        $this->resets[] = static function () use ($state): void {
            foreach ($state as [$property, $value]) {
                $property->setValue(null, $value);
            }
        };
    }

    private function captureResets(object $service): void
    {
        if ($service instanceof \Symfony\Contracts\Service\ResetInterface) {
            $this->resets[] = $service->reset(...);
        } elseif (method_exists($service, 'flushState') && (new \ReflectionMethod($service, 'flushState'))->isPublic()) {
            $this->resets[] = $service->flushState(...);
        }
        if ($service instanceof \Illuminate\Routing\Router) {
            // Routes live in arrays of the route collection, out of reach of capture(); each one caches the
            // controller it built, together with the request or user that controller took in its constructor.
            $forget = \Closure::bind(static function (\Illuminate\Routing\Route $route): void {
                $route->flushController();
                $route->parameters = null;
                $route->originalParameters = null;
            }, null, \Illuminate\Routing\Route::class);
            $this->resets[] = static function () use ($service, $forget): void {
                $routes = $service->getRoutes();
                if ($routes instanceof \Illuminate\Routing\RouteCollection) {
                    foreach ($routes->getRoutes() as $route) {
                        $forget($route);
                    }
                }
            };
        }
        if ($service instanceof \Illuminate\Log\LogManager) {
            // Context set on a channel during a request lives in the Logger object, not in the manager.
            $this->resets[] = static function () use ($service): void {
                foreach ($service->getChannels() as $channel) {
                    if ($channel instanceof \Illuminate\Log\Logger) {
                        $channel->withoutContext();
                    }
                }
            };
        }
    }
}
