<?php

use EvolutionCMS\Controllers\SystemInfo;
use EvolutionCMS\Interfaces\CoreInterface;
use EvolutionCMS\Interfaces\ManagerThemeInterface;
use EvolutionCMS\ManagerTheme;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

function evoUiThemeFixture(string $name): array
{
    $root = dirname(__DIR__, 4) . '/';
    foreach (['EVO_MANAGER_PATH' => $root . 'manager/', 'EVO_MANAGER_URL' => '/manager/', 'IN_INSTALL_MODE' => false] as $key => $value) {
        if (!defined($key)) {
            define($key, $value);
        }
    }

    $views = new Factory(new EngineResolver(), new FileViewFinder(new Filesystem(), []), new Dispatcher());
    $core = Mockery::mock(CoreInterface::class, ArrayAccess::class);
    $core->shouldReceive('offsetGet')->with('view')->andReturn($views);
    $core->shouldReceive('getConfig')->with('manager_language')->andReturn('english');
    $core->shouldReceive('getConfig')->with('mgr_jquery_path', '')->andReturn('jquery.js');
    $core->shouldReceive('getConfig')->with('mgr_date_picker_path', '')->andReturn('datepicker.php');

    $theme = new class($core, $name) extends ManagerTheme {
        protected function loadLang($lang = 'english') {}
        protected function loadStyle() {}
    };

    return [$theme, $views->getFinder()];
}

test('switching manager themes selects only the native System Info override', function (string $name, string $path) {
    [$theme, $finder] = evoUiThemeFixture($name);
    $root = dirname(__DIR__, 4) . '/manager/';

    expect($theme->getTheme())->toBe($name)
        ->and(realpath($finder->find($theme->getViewName('page.sysinfo'))))->toBe(realpath($root . $path))
        ->and(realpath($finder->find($theme->getViewName('page.system_settings'))))->toBe(realpath($root . 'views/page/system_settings.blade.php'))
        ->and(realpath($finder->find($theme->getViewName('frame.1'))))->toBe(realpath($root . 'views/frame/1.blade.php'))
        ->and($theme->getThemeDir(false))->toBe('media/style/default/')
        ->and($theme->getThemeUrl())->toBe(EVO_MANAGER_URL . 'media/style/default/')
        ->and($theme->getCssFiles())->not->toBeEmpty();
})->with([
    ['default', 'views/page/sysinfo.blade.php'],
    ['evo-ui', 'media/style/evo-ui/views/page/sysinfo.blade.php'],
    ['default', 'views/page/sysinfo.blade.php'],
]);

test('other manager themes retain their own physical asset paths', function () {
    [$theme] = evoUiThemeFixture('custom-theme');

    expect($theme->getThemeDir(false))->toBe('media/style/custom-theme/')
        ->and(ManagerTheme::resolveAssetTheme('custom-theme'))->toBe('custom-theme');
});

test('System Info still requires the settings right with either renderer', function (bool $allowed) {
    $core = Mockery::mock(CoreInterface::class);
    $core->shouldReceive('getDatabase')->andReturn(Mockery::mock(\EvolutionCMS\Database::class));
    $core->shouldReceive('hasPermission')->with('settings')->andReturn($allowed);
    $theme = Mockery::mock(ManagerThemeInterface::class);
    $theme->shouldReceive('getCore')->andReturn($core);

    expect((new SystemInfo($theme))->canView())->toBe($allowed);
})->with([true, false]);

test('both Evo UI endpoint forms replace unguarded provider routes with mgr middleware', function () {
    if (!defined('MGR_DIR')) {
        define('MGR_DIR', 'manager');
    }
    $previous = \Illuminate\Support\Facades\Facade::getFacadeApplication();
    $container = new \Illuminate\Container\Container();
    $router = new \Illuminate\Routing\Router(new Dispatcher(), $container);
    $container->instance('router', $router);
    \Illuminate\Support\Facades\Facade::setFacadeApplication($container);
    \Illuminate\Support\Facades\Route::clearResolvedInstance('router');
    $paths = ['evo-ui', trim(MGR_DIR, '/') . '/evo-ui'];

    try {
        // Model the package provider's earlier registrations, then load the real
        // manager routes exactly as ManagerTheme::handleRoute() does.
        foreach ($paths as $path) {
            $router->match(['GET', 'POST'], $path . '/{path?}', fn () => 'unguarded')->where('path', '.*');
        }
        $router->middleware('mgr')->group(dirname(__DIR__, 4) . '/manager/routes.php');

        foreach ($paths as $path) {
            foreach (['GET', 'POST'] as $method) {
                $route = $router->getRoutes()->match(\Illuminate\Http\Request::create('/' . $path, $method));
                expect($route->gatherMiddleware())->toContain('mgr');
            }
        }
    } finally {
        \Illuminate\Support\Facades\Route::clearResolvedInstance('router');
        \Illuminate\Support\Facades\Facade::setFacadeApplication($previous);
    }
});
