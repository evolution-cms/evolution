<?php

use EvolutionCMS\AliasLoader;
use EvolutionCMS\Core;
use EvolutionCMS\ProviderRepository;
use EvolutionCMS\Support\OnDemandProviders;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use OnDemandFixture\Always\AlwaysServiceProvider;
use OnDemandFixture\Editor\EditorServiceProvider;
use OnDemandFixture\Gallery\Gallery;
use OnDemandFixture\Gallery\GalleryServiceProvider;

require_once dirname(__DIR__) . '/Fixtures/on-demand-providers.php';

/**
 * A Core that registers configured providers as a frontend (or manager) request would, without the parser.
 */
class OnDemandRuntimeCore extends Core
{
    public function __construct(
        public string $manifestFile,
        array $providers,
        array $onDemand,
        public bool $frontend = true,
        public string $path = 'articles/category-042',
    ) {
        $this->instance('events', new Dispatcher($this));
        $this->instance('config', new Repository(['app' => [
            'providers' => $providers,
            'on_demand_providers' => $onDemand,
        ]]));
    }

    public function getCachedServicesPath()
    {
        return $this->manifestFile;
    }

    protected function registersProvidersOnDemand(): bool
    {
        return $this->frontend;
    }

    protected function onDemandRequestPath(): string
    {
        return $this->path;
    }
}

function onDemandProviders(): array
{
    return [
        'Gallery' => GalleryServiceProvider::class,
        'Editor' => EditorServiceProvider::class,
        'Always' => AlwaysServiceProvider::class,
    ];
}

function onDemandDeclarations(): array
{
    return [
        'Gallery' => ['paths' => ['gallery'], 'services' => ['gallery']],
        'Editor' => ['paths' => ['editor-upload'], 'events' => ['OnRichTextEditorInit']],
        'Always' => false,
    ];
}

function onDemandApp(string $path = 'articles/category-042', bool $frontend = true): OnDemandRuntimeCore
{
    $app = new OnDemandRuntimeCore(tempnam(sys_get_temp_dir(), 'evo-on-demand-'), onDemandProviders(), onDemandDeclarations(), $frontend, $path);
    $app->registerConfiguredProviders();
    $app->boot();
    return $app;
}

afterEach(function () {
    foreach (glob(sys_get_temp_dir() . '/evo-on-demand-*') ?: [] as $file) {
        @unlink($file);
    }
});

test('request paths are taken relative to the site base URL', function (string $uri, string $base, string $path) {
    expect(OnDemandProviders::requestPath($uri, $base))->toBe($path);
})->with([
    'root site' => ['/sgallery/upload?x=1', '/', 'sgallery/upload'],
    'subdirectory site' => ['/shop/sgallery/upload', '/shop/', 'sgallery/upload'],
    'subdirectory letter case' => ['/Shop/stask/', '/shop/', 'stask'],
    'outside the base' => ['/other/sgallery', '/shop/', 'other/sgallery'],
    'base prefix of a longer segment' => ['/shopping/sgallery', '/shop/', 'shopping/sgallery'],
    'encoded' => ['/dtui%2Dplantuml', '/', 'dtui-plantuml'],
    'home page' => ['/', '/', ''],
]);

test('a path prefix matches whole leading segments in any letter case', function (string $path, bool $matches) {
    expect(OnDemandProviders::matchesPath($path, ['sgallery', '/ddocs/']))->toBe($matches);
})->with([
    ['sgallery', true],
    ['sgallery/upload-file', true],
    ['SGallery/upload', true],
    ['ddocs/health', true],
    ['sgallery-news', false],
    ['articles/sgallery', false],
    ['', false],
]);

test('declarations name what loads a waiting provider', function () {
    $providers = onDemandProviders() + ['Unknown' => 'Vendor\\Missing\\Provider'];
    $declarations = onDemandDeclarations() + ['Undeclared' => ['services' => ['x']]];
    $onDemand = new OnDemandProviders($providers, $declarations, 'articles');

    expect($onDemand->pending())->toBe([GalleryServiceProvider::class => true, EditorServiceProvider::class => true])
        ->and($onDemand->forService('gallery'))->toBe(GalleryServiceProvider::class)
        ->and($onDemand->forService(Gallery::class))->toBe(GalleryServiceProvider::class)
        ->and($onDemand->forService('\\' . Gallery::class))->toBe(GalleryServiceProvider::class)
        ->and($onDemand->forService('OnDemandFixture\\GalleryOther\\Thing'))->toBeNull()
        ->and($onDemand->forService('view'))->toBeNull()
        ->and($onDemand->forEvent('OnRichTextEditorInit'))->toBe([EditorServiceProvider::class])
        ->and($onDemand->declaresService('gallery'))->toBeTrue();

    $onDemand->forget(GalleryServiceProvider::class);
    expect($onDemand->forService('gallery'))->toBeNull()
        ->and($onDemand->declaresService('gallery'))->toBeFalse()
        ->and($onDemand->isEmpty())->toBeFalse();
    $onDemand->forget(EditorServiceProvider::class);
    expect($onDemand->forEvent('OnRichTextEditorInit'))->toBe([])->and($onDemand->isEmpty())->toBeTrue();
});

test('a frontend request leaves declared providers unregistered and keeps the shared manifest', function () {
    $app = onDemandApp();

    expect($app->getProviders(GalleryServiceProvider::class))->toBe([])
        ->and($app->getProviders(EditorServiceProvider::class))->toBe([])
        ->and($app->make('always.registered'))->toBeTrue();

    $manifest = require $app->manifestFile;
    $repository = new ProviderRepository($app, new Filesystem, $app->manifestFile);
    // A manager request passes the same list, so the two kinds of request never rewrite each other's manifest.
    expect($manifest['providers'])->toBe(onDemandProviders())
        ->and($repository->shouldRecompile($manifest, onDemandProviders()))->toBeFalse();
});

test('resolving a declared service registers and boots its provider once', function () {
    $app = onDemandApp();

    expect($app->bound('gallery'))->toBeTrue()
        ->and($app->make('gallery'))->toBeInstanceOf(Gallery::class)
        ->and($app->make('gallery.registered'))->toBe(1)
        ->and($app->make('gallery'))->toBe($app->make(Gallery::class))
        ->and($app->getProviders(GalleryServiceProvider::class))->toHaveCount(1)
        ->and($app->getProviders(EditorServiceProvider::class))->toBe([]);
});

test('resolving a class of the provider namespace registers it', function () {
    $app = onDemandApp();

    expect($app->make(Gallery::class))->toBeInstanceOf(Gallery::class)
        ->and($app->make('gallery.registered'))->toBe(1);
});

test('a request to any declared path registers every declared provider up front', function () {
    $app = onDemandApp('gallery/upload-file');

    expect($app->make('gallery.registered'))->toBe(1)
        ->and($app->getProviders(EditorServiceProvider::class))->toHaveCount(1);
});

test('invoking a declared event registers the provider before its listeners run', function () {
    $app = onDemandApp();
    $app->pluginEvent = [];

    expect($app->invokeEvent('OnDocFormRender'))->toBeFalse()
        ->and($app->getProviders(EditorServiceProvider::class))->toBe([])
        ->and($app->invokeEvent('OnRichTextEditorInit'))->toBe(['editor'])
        ->and($app->getProviders(EditorServiceProvider::class))->toHaveCount(1);
});

test('requests outside the frontend register every provider', function () {
    $app = onDemandApp(frontend: false);

    expect($app->make('gallery.registered'))->toBe(1)
        ->and($app->getProviders(EditorServiceProvider::class))->toHaveCount(1)
        ->and($app->bound('gallery'))->toBeTrue();
});

test('only frontend web requests of a process that does not keep its application wait', function (string $mode, string $expected) {
    $output = evoRunPhp(dirname(__DIR__) . '/Fixtures/on-demand-request-mode.php', [$mode], $status);

    expect($status)->toBe(0, $output)->and(trim($output))->toBe($expected);
})->with([
    ['frontend', 'on-demand'],
    ['manager', 'all'],
    ['install', 'all'],
    ['kept-worker', 'all'],
]);

test('the console is never treated as a frontend request', function () {
    $app = (new ReflectionClass(Core::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod($app, 'registersProvidersOnDemand');

    expect($method->invoke($app))->toBeFalse();
});

test('an alias whose provider declares it while booting is not declared twice', function () {
    $app = onDemandApp();
    $alias = 'OnDemandFixtureGallery' . bin2hex(random_bytes(4));
    $app['config']->set('gallery.alias', $alias);
    $previous = Container::getInstance();
    Container::setInstance($app);
    $loader = AliasLoader::getInstance();
    $aliases = $loader->getAliases();
    $loader->setAliases([$alias => \OnDemandFixture\Gallery\Facades\GalleryFacade::class]);
    $warnings = [];
    set_error_handler(static function (int $level, string $message) use (&$warnings): bool {
        $warnings[] = $message;
        return true;
    });
    try {
        expect($loader->load($alias))->toBeTrue()
            ->and($warnings)->toBe([])
            ->and(class_exists($alias, false))->toBeTrue()
            ->and($app->make('gallery.registered'))->toBe(1);
    } finally {
        restore_error_handler();
        $loader->setAliases($aliases);
        Container::setInstance($previous);
    }
});

test('the bundled manager packages wait on the frontend and Livewire stays off there', function () {
    $config = require dirname(__DIR__, 2) . '/config/app.php';
    $onDemand = new OnDemandProviders($config['providers'], $config['on_demand_providers'], 'articles/category-042');

    expect(array_keys($onDemand->pending()))->toBe([
        EvoUI\EvoUIServiceProvider::class,
        EvolutionCMS\dTuiEditor\DTuiEditorServiceProvider::class,
        Dmi3yy\dDocs\dDocsServiceProvider::class,
        Seiger\sGallery\sGalleryServiceProvider::class,
        Seiger\sTask\sTaskServiceProvider::class,
    ])
        ->and($onDemand->forService('livewire'))->toBeNull()
        ->and($onDemand->forService('sGallery'))->toBe(Seiger\sGallery\sGalleryServiceProvider::class)
        // sTask's module page renders EvoUI's Livewire panel: its URL needs EvoUI registered before routing.
        ->and((new OnDemandProviders($config['providers'], $config['on_demand_providers'], 'stask'))->pending())
        ->toBe([]);
});
