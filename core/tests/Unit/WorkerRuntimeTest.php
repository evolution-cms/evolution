<?php

use EvolutionCMS\Core;
use EvolutionCMS\Parser;
use EvolutionCMS\Runtime\RequestTerminated;
use EvolutionCMS\Runtime\WorkerRuntime;

require_once dirname(__DIR__, 2) . '/includes/worker.runtime.php';

test('classic request termination preserves string output, exit codes and shutdown callbacks', function () {
    $probe = dirname(__DIR__) . '/Mocks/worker_runtime_probe.php';
    expect(evoRunPhp($probe, ['classic-string'], $code))->toBe('body|shutdown')
        ->and($code)->toBe(0)
        ->and(evoRunPhp($probe, ['classic-code'], $code))->toBe('|shutdown')
        ->and($code)->toBe(7);
});

test('worker termination stops execution without ending the process', function () {
    WorkerRuntime::begin();
    ob_start();
    try {
        evo_exit('body');
        echo 'unreachable';
    } catch (RequestTerminated) {
        $body = ob_get_clean();
    } finally {
        WorkerRuntime::terminate();
    }
    expect($body)->toBe('body')->and(WorkerRuntime::active())->toBeFalse();
});

test('worker cleanup runs once per request, including callbacks appended during termination', function () {
    $calls = [];
    for ($i = 0; $i < 2; $i++) {
        WorkerRuntime::begin();
        evo_register_shutdown(function () use (&$calls, $i) {
            $calls[] = 'first' . $i;
            evo_register_shutdown(function () use (&$calls, $i) { $calls[] = 'last' . $i; });
        });
        WorkerRuntime::terminate();
    }
    expect($calls)->toBe(['first0', 'last0', 'first1', 'last1']);
});

test('failed request cleanup does not skip subsequent cleanup or leak callbacks', function () {
    WorkerRuntime::begin();
    $ran = false;
    evo_register_shutdown(static function () { throw new RuntimeException('cleanup failed'); });
    evo_register_shutdown(function () use (&$ran) { $ran = true; });
    try {
        WorkerRuntime::terminate();
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('cleanup failed');
    }
    expect($ran)->toBeTrue()->and(WorkerRuntime::active())->toBeFalse();
    WorkerRuntime::begin();
    WorkerRuntime::terminate();
});

test('successive worker sessions load and save the correct user independently', function () {
    $output = evoRunPhp(dirname(__DIR__) . '/Mocks/worker_runtime_probe.php', ['session'], $code);
    expect($code)->toBe(0)->and(json_decode($output, true))->toBe([
        ['session' => 'alice', 'saved' => 'alice', 'saves' => 1],
        ['session' => 'bob', 'saved' => 'bob', 'saves' => 1],
    ]);
});

test('the parser follows a new request core and releases its old reference', function () {
    $reflection = new ReflectionClass(Core::class);
    $first = $reflection->newInstanceWithoutConstructor();
    $second = $reflection->newInstanceWithoutConstructor();
    $one = Parser::getInstance($first);
    expect(Parser::getInstance($second))->not->toBe($one);
    $two = Parser::getInstance($second);
    Parser::resetForWorker();
    expect(Parser::getInstance($second))->not->toBe($two);
    Parser::resetForWorker();
});

test('plugin request termination is not treated as a fatal plugin error', function () {
    $core = (new ReflectionClass(Core::class))->newInstanceWithoutConstructor();
    $core->event = new EvolutionCMS\Event();
    WorkerRuntime::begin();
    $level = ob_get_level();
    try {
        $core->evalPlugin('evo_exit();', []);
        test()->fail('The plugin termination signal must propagate.');
    } catch (RequestTerminated) {
        expect($core->event)->toBeInstanceOf(EvolutionCMS\Event::class);
    } finally {
        while (ob_get_level() > $level) { ob_end_clean(); }
        WorkerRuntime::terminate();
    }
});

test('worker origins include scheme and non-default ports and reject malformed hosts', function () {
    expect(WorkerRuntime::origin(['HTTP_HOST' => 'EXAMPLE.com:80']))->toBe('http://example.com')
        ->and(WorkerRuntime::origin(['HTTP_HOST' => 'example.com:8443', 'HTTPS' => 'on']))->toBe('https://example.com:8443')
        ->and(WorkerRuntime::origin(['HTTP_HOST' => '[::1]:8080']))->toBe('http://[::1]:8080')
        ->and(WorkerRuntime::origin(['HTTP_HOST' => 'example.com:65536']))->toBeNull()
        ->and(WorkerRuntime::origin(['HTTP_HOST' => 'example.com@evil.test']))->toBeNull()
        ->and(WorkerRuntime::origin(['HTTP_HOST' => "example.com\r\nX: injected"]))->toBeNull();
});

test('empty HTTPS values from FrankenPHP keep HTTP session cookies usable', function () {
    $output = evoRunPhp(dirname(__DIR__) . '/Mocks/worker_runtime_probe.php', ['secure-cookie'], $code);
    expect($code)->toBe(0)->and(json_decode($output, true))->toBe([false, false, false, true]);
});

test('document tree caches do not survive a new core instance', function () {
    if (!class_exists('UrlProcessor')) {
        class_alias(EvolutionCMS\Facades\UrlProcessor::class, 'UrlProcessor');
    }
    $reflection = new ReflectionClass(Core::class);
    $old = EvolutionCMS\Facades\UrlProcessor::getFacadeRoot();
    try {
        $results = [];
        foreach ([2, 3] as $child) {
            $core = $reflection->newInstanceWithoutConstructor();
            $core->instance('config', new Illuminate\Config\Repository());
            $core->documentMap = [[0 => $child]];
            EvolutionCMS\Facades\UrlProcessor::swap((object) ['aliasListing' => [
                $child => ['path' => '', 'alias' => 'child-' . $child],
            ]]);
            $results[] = $core->getChildIds(0, 1);
        }
        expect($results)->toBe([['child-2' => 2], ['child-3' => 3]]);
    } finally {
        EvolutionCMS\Facades\UrlProcessor::swap($old);
    }
});

test('request error handlers are unwound to the worker baseline', function () {
    $error = set_error_handler(null);
    restore_error_handler();
    $exception = set_exception_handler(null);
    restore_exception_handler();
    set_error_handler(static fn () => true);
    set_error_handler(static fn () => false);
    set_exception_handler(static function (Throwable $exception): void {});
    WorkerRuntime::restoreHandlers($error, $exception);
    $restoredError = set_error_handler(null);
    restore_error_handler();
    $restoredException = set_exception_handler(null);
    restore_exception_handler();
    expect($restoredError)->toBe($error)->and($restoredException)->toBe($exception);
});

test('worker host guard redirects other site hostnames of the same scheme and refuses the rest', function () {
    $aliases = WorkerRuntime::siteAliases('https://example.com', 'www.example.com, example.com', null);
    $request = fn (string $host, array $server = []) => WorkerRuntime::misdirected(
        $server + ['HTTP_HOST' => $host, 'HTTPS' => 'on', 'REQUEST_URI' => '/page?x=1'], 'https://example.com', $aliases
    );

    expect($aliases)->toBe(['https://www.example.com'])
        ->and($request('example.com'))->toBeNull()
        ->and($request('EXAMPLE.COM:443'))->toBeNull()
        ->and($request('www.example.com'))->toBe([308, 'https://example.com/page?x=1'])
        ->and($request('www.example.com', ['REQUEST_URI' => "/\r\nX: y"]))->toBe([308, 'https://example.com/'])
        // a proxy that drops X-Forwarded-Proto must not loop between http and https
        ->and($request('www.example.com', ['HTTPS' => 'off']))->toBe([421, null])
        ->and($request('evil.test'))->toBe([421, null])
        ->and(WorkerRuntime::authority('https://example.com'))->toBe('example.com')
        ->and(WorkerRuntime::authority('http://[::1]:8080'))->toBe('[::1]:8080');
});

test('site hostnames without a port stand for the default port of the worker scheme', function () {
    expect(WorkerRuntime::hostnameOrigins('a.test, a.test:443, b.test:8443,, bad host', 'https'))
        ->toBe(['https://a.test', 'https://b.test:8443'])
        ->and(WorkerRuntime::hostnameOrigins('a.test:443', 'http'))->toBe(['http://a.test:443']);
});

test('a worker origin that the site URL settings would not produce is refused at startup', function (string $hostnames, ?string $siteUrl) {
    expect(fn () => WorkerRuntime::siteAliases('http://a.test:8080', $hostnames, $siteUrl))->toThrow(RuntimeException::class);
})->with([
    'not in EVO_SITE_HOSTNAMES' => ['b.test:8080', null],
    'other port' => ['a.test', null],
    'EVO_SITE_URL of another host' => ['', 'http://b.test:8080/'],
    'EVO_SITE_URL of another scheme' => ['', 'https://a.test:8080/'],
]);

test('a matching EVO_SITE_URL and an empty hostname list are accepted', function () {
    expect(WorkerRuntime::siteAliases('http://a.test:8080', '', 'http://A.test:8080/sub/'))->toBe([]);
});

test('kept application serves each request from its post-boot state', function () {
    $output = evoRunPhp(dirname(__DIR__) . '/Mocks/worker_runtime_probe.php', ['keep-app'], $code);
    $state = ['same' => true, 'initialized' => 1, 'container' => true, 'config' => 'boot',
        'placeholder' => null, 'bound' => false, 'listeners' => 1];
    expect($code)->toBe(0)->and(json_decode($output, true))->toBe([$state, $state]);
});

test('a kept application builds route controllers again, with the request of their own request', function () {
    $output = evoRunPhp(dirname(__DIR__) . '/Mocks/worker_runtime_probe.php', ['keep-app-controller'], $code);
    expect($code)->toBe(0, $output)->and(json_decode($output, true))->toBe([['alice:1', 1], ['bob:2', 2]]);
});

test('models first booted by a kept-application request boot again and fire their listeners once', function () {
    $output = evoRunPhp(dirname(__DIR__) . '/Mocks/worker_runtime_probe.php', ['keep-app-model'], $code);
    $request = [[
        'KeepProbeBootModel.saving', 'KeepProbeBootModel.creating',
        'KeepProbeLateModel.saving', 'KeepProbeLateModel.creating',
    ], ['probe']];
    expect($code)->toBe(0, $output)->and(json_decode($output, true))->toBe([$request, $request]);
});

test('only a worker defers the native session until the providers have booted', function (string $runtime, string $session, bool $deferred) {
    $output = evoRunPhp(dirname(__DIR__) . '/Mocks/worker_runtime_probe.php', ['session-start', $runtime, $session], $code);
    expect($code)->toBe(0)->and(json_decode($output, true))->toBe($deferred);
})->with([
    'worker, native session' => ['worker', 'native', true],
    'worker, session proxy' => ['worker', 'proxy', false],
    'classic, native session' => ['classic', 'native', false],
    'classic, session proxy' => ['classic', 'proxy', false],
]);

test('bootstrap, Tracy and Core start the native session on the same condition', function () {
    $core = dirname(__DIR__, 2);
    expect(file_get_contents($core . '/bootstrap.php'))->toContain('!EvoSessionProxy::startsAfterBoot()')
        ->and(file_get_contents($core . '/src/Providers/TracyServiceProvider.php'))->toContain('\EvoSessionProxy::startsAfterBoot()')
        ->and(file_get_contents($core . '/src/Core.php'))
        ->toMatch('~\\\\EvoSessionProxy::startsAfterBoot\(\)[^}]*startCMSSession\(\);~s');
});

test('the snapshot resets nested service state and calls the reset hooks of kept services', function () {
    $app = (new ReflectionClass(Core::class))->newInstanceWithoutConstructor();
    $finder = new Illuminate\View\FileViewFinder(new Illuminate\Filesystem\Filesystem(), []);
    $finder->addNamespace('manager', '/boot');
    $view = new class ($finder) {
        public array $shared = ['boot' => true];
        public int $flushes = 0;
        public function __construct(public Illuminate\View\FileViewFinder $finder) {}
        public function flushState(): void { $this->flushes++; }
    };
    $resettable = new class implements Symfony\Contracts\Service\ResetInterface {
        public int $resets = 0;
        public function reset(): void { $this->resets++; }
    };
    $app->instance('view', $view);
    $app->instance('resettable', $resettable);
    $snapshot = new EvolutionCMS\Runtime\ApplicationSnapshot($app);

    $view->shared['request'] = true;
    $finder->addNamespace('manager', '/request');
    $snapshot->restore();

    expect($view->shared)->toBe(['boot' => true])
        ->and($finder->getHints())->toBe(['manager' => ['/boot']])
        ->and($view->flushes)->toBe(1)
        ->and($resettable->resets)->toBe(1)
        ->and($snapshot->services())->toBe(['view', 'resettable']);
});

test('fresh containers forget the static callbacks that every boot registers again', function () {
    Illuminate\Console\Application::starting(static fn () => null);
    WorkerRuntime::forgetBootRegistries();
    expect((new ReflectionProperty(Illuminate\Console\Application::class, 'bootstrappers'))->getValue())->toBe([]);
});
