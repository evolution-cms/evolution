<?php

use EvolutionCMS\Core;
use EvolutionCMS\ProviderRepository;
use Illuminate\Config\Repository;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

/**
 * Exercise the real container registration path without starting the parser.
 *
 * @since 3.7.0
 */
class ManifestRuntimeCore extends Core
{
    public string $manifestFile;

    /**
     * Bind only the services needed to register configured providers.
     *
     * @param string $path Temporary manifest path owned by the test.
     * @param list<string> $providers Providers in their original configuration order.
     * @since 3.7.0
     */
    public function __construct(string $path, array $providers)
    {
        $this->manifestFile = $path;
        $this->instance('events', new Dispatcher($this));
        $this->instance('config', new Repository(['app' => ['providers' => $providers]]));
    }

    /**
     * Isolate manifest invalidation from the installed CMS bootstrap directory.
     *
     * @return string Test-owned manifest path.
     * @since 3.7.0
     */
    public function getCachedServicesPath()
    {
        return $this->manifestFile;
    }
}

/**
 * Eager provider whose registration order is observable without external state.
 *
 * @since 3.7.0
 */
class ManifestRuntimeEager extends ServiceProvider
{
    /**
     * Record registration in the isolated application container.
     *
     * @return void
     * @since 3.7.0
     */
    public function register(): void
    {
        $this->app->instance('eager-registered', true);
    }

    /**
     * Observe boot callbacks when an event loads a provider after application boot.
     *
     * @return void
     * @since 3.7.0
     */
    public function boot(): void
    {
        $this->app->instance('eager-booted', true);
    }
}

/**
 * Write a current manifest without loading any of its deferred classes.
 *
 * @param ManifestRuntimeCore $app Test container and path owner.
 * @param list<string> $providers Configured provider declarations.
 * @param array<string, string> $deferred Binding-to-provider map.
 * @param array<string, list<string>> $when Provider-to-event declarations.
 * @return ProviderRepository The repository whose signature was recorded.
 * @since 3.7.0
 */
function manifestRuntimeWarm(ManifestRuntimeCore $app, array $providers, array $deferred, array $when = []): ProviderRepository
{
    $repository = new ProviderRepository($app, new Filesystem, $app->manifestFile);
    $signature = (new ReflectionMethod($repository, 'autoloadSignature'))->invoke($repository);
    $repository->writeManifest([
        'providers' => $providers, 'eager' => [], 'deferred' => $deferred,
        'when' => $when, 'missing' => [], 'autoload_signature' => $signature,
    ]);
    return $repository;
}

test('warm configured providers do not autoload a deferred class until its binding is used', function () {
    $path = tempnam(sys_get_temp_dir(), 'evo-manifest-');
    $class = 'ManifestLazy'.bin2hex(random_bytes(4));
    $loads = [];
    $loader = static function ($name) use ($class, &$loads) {
        if ($name !== $class) return;
        $loads[] = $name;
        eval('class '.$class.' extends \\Illuminate\\Support\\ServiceProvider { public function register() { $this->app->singleton("lazy-value", fn () => "ready"); } }');
    };
    spl_autoload_register($loader);
    try {
        $app = new ManifestRuntimeCore($path, [$class]);
        manifestRuntimeWarm($app, [$class], ['lazy-value' => $class]);
        $app->registerConfiguredProviders();
        expect($loads)->toBe([])->and($app->bound('lazy-value'))->toBeTrue();
        expect($app->make('lazy-value'))->toBe('ready')->and($loads)->toBe([$class]);
        expect($app->make('lazy-value'))->toBe('ready')->and($app->getProviders($class))->toHaveCount(1);
    } finally {
        spl_autoload_unregister($loader);
        @unlink($path);
    }
});

test('a missing provider is reported once when the manifest is compiled', function () {
    $path = tempnam(sys_get_temp_dir(), 'evo-manifest-');
    $log = tempnam(sys_get_temp_dir(), 'evo-manifest-log-');
    $missing = 'ManifestMissing'.bin2hex(random_bytes(4));
    $previous = ini_set('error_log', $log);
    try {
        (new ManifestRuntimeCore($path, [ManifestRuntimeEager::class, $missing]))->registerConfiguredProviders();
        expect(substr_count((string) file_get_contents($log), 'Skipped missing service provider "'.$missing.'"'))->toBe(1);
    } finally {
        ini_set('error_log', (string) $previous);
        @unlink($path);
        @unlink($log);
    }
});

test('cold manifests skip missing packages and detect providers that become available', function () {
    $path = tempnam(sys_get_temp_dir(), 'evo-manifest-');
    $missing = 'ManifestMissing'.bin2hex(random_bytes(4));
    try {
        $app = new ManifestRuntimeCore($path, [ManifestRuntimeEager::class, $missing]);
        $app->registerConfiguredProviders();
        $manifest = require $path;
        expect($app->make('eager-registered'))->toBeTrue()->and($manifest['missing'])->toBe([$missing]);
        $repository = new ProviderRepository($app, new Filesystem, $path);
        expect($repository->shouldRecompile($manifest, $manifest['providers']))->toBeFalse();
        eval('class '.$missing.' extends \\Illuminate\\Support\\ServiceProvider {}');
        expect($repository->shouldRecompile($manifest, $manifest['providers']))->toBeTrue();
    } finally {
        @unlink($path);
    }
});

test('removed deferred and event providers invalidate the manifest without a class-not-found fatal', function () {
    $path = tempnam(sys_get_temp_dir(), 'evo-manifest-');
    $class = 'ManifestRemoved'.bin2hex(random_bytes(4));
    try {
        $app = new ManifestRuntimeCore($path, [$class]);
        $repository = manifestRuntimeWarm($app, [$class], ['removed-value' => $class], [$class => ['load-removed']]);
        $repository->load([$class]);
        $app->loadDeferredProvider('removed-value');
        expect($app->getDeferredServices())->toBe([])->and(is_file($path))->toBeFalse();
        manifestRuntimeWarm($app, [$class], [], [$class => ['load-removed']]);
        $app->make('events')->dispatch('load-removed');
        expect(is_file($path))->toBeFalse();
    } finally {
        @unlink($path);
    }
});

test('Composer metadata changes and malformed manifests force recompilation', function () {
    $dir = sys_get_temp_dir().'/evo-composer-signature-'.bin2hex(random_bytes(4));
    $files = new Filesystem;
    $files->makeDirectory($dir.'/composer', 0755, true);
    $loader = new Composer\Autoload\ClassLoader($dir);
    $loader->register();
    try {
        $app = new ManifestRuntimeCore($dir.'/services.php', []);
        manifestRuntimeWarm($app, [], []);
        $manifest = require $app->manifestFile;
        $files->put($dir.'/composer/installed.php', '<?php return [];');
        $repository = new ProviderRepository($app, $files, $app->manifestFile);
        expect($repository->shouldRecompile($manifest, []))->toBeTrue()
            ->and($repository->shouldRecompile(['providers' => []], []))->toBeTrue();
    } finally {
        $loader->unregister();
        $files->deleteDirectory($dir);
    }
});

test('the core cache adapter retains the framework-first provider ordering', function () {
    $path = tempnam(sys_get_temp_dir(), 'evo-manifest-');
    try {
        $cache = EvolutionCMS\Providers\CacheServiceProvider::class;
        $app = new ManifestRuntimeCore($path, [ManifestRuntimeEager::class, $cache]);
        $app->registerConfiguredProviders();
        $manifest = require $path;
        expect($manifest['providers'])->toBe([$cache, ManifestRuntimeEager::class]);
    } finally {
        @unlink($path);
    }
});

test('event-triggered providers still register and boot after the application has booted', function () {
    $path = tempnam(sys_get_temp_dir(), 'evo-manifest-');
    try {
        $provider = ManifestRuntimeEager::class;
        $app = new ManifestRuntimeCore($path, [$provider]);
        $repository = manifestRuntimeWarm($app, [$provider], [], [$provider => ['load-ready']]);
        $repository->load([$provider]);
        $app->boot();
        expect($app->bound('eager-registered'))->toBeFalse();
        $app->make('events')->dispatch('load-ready');
        expect($app->make('eager-registered'))->toBeTrue()
            ->and($app->make('eager-booted'))->toBeTrue();
        $app->make('events')->dispatch('load-ready');
        expect($app->getProviders($provider))->toHaveCount(1);
    } finally {
        @unlink($path);
    }
});
