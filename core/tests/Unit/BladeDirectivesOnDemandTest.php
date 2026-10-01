<?php

use EvolutionCMS\Providers\BladeIconsAdapterServiceProvider;
use EvolutionCMS\Providers\BladeServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

/*
|--------------------------------------------------------------------------
| Blade directives are registered when the compiler is built
|--------------------------------------------------------------------------
|
| Booting the providers used to build the Blade compiler on every request,
| and the icon adapter built it whenever the view factory was resolved, which
| a page also does just to look for a template file.
|
*/

beforeEach(function () {
    $app = new class extends Container {
        public function runningInConsole(): bool
        {
            return false;
        }

        public function bootstrapPath($path = ''): string
        {
            return sys_get_temp_dir() . '/evo-no-bootstrap/' . $path;
        }
    };
    $app->instance('config', new Repository(['view' => ['directive' => ['legacyDirective' => fn () => 'legacy']]]));
    $app->singleton('blade.compiler', fn () => new BladeCompiler(new Filesystem(), sys_get_temp_dir()));
    $app->singleton('view', fn () => new Factory(new EngineResolver(), new FileViewFinder(new Filesystem(), []), new Dispatcher()));
    $this->app = $app;
});

test('booting the providers does not build the compiler', function () {
    (new BladeServiceProvider($this->app))->boot();
    (new BladeIconsAdapterServiceProvider($this->app))->boot();
    $this->app->make('view');

    expect($this->app->resolved('blade.compiler'))->toBeFalse();
});

test('the compiler gets the directives once it is built', function () {
    (new BladeServiceProvider($this->app))->boot();
    (new BladeIconsAdapterServiceProvider($this->app))->boot();

    $directives = $this->app->make('blade.compiler')->getCustomDirectives();

    expect($directives)->toHaveKeys(['evoConfig', 'makeUrl', 'revision', 'evoParser', 'evoRole', 'evoElseRole', 'evoEndRole', 'auth', 'guest', 'legacyDirective', 'svg'])
        ->and($this->app->make('blade.compiler')->compileString('@evoEndRole'))->toBe('<?php endif; ?>');
});

test('a compiler built before the providers boot gets the directives too', function () {
    $compiler = $this->app->make('blade.compiler');

    (new BladeServiceProvider($this->app))->boot();

    expect($compiler->getCustomDirectives())->toHaveKey('evoConfig');
});
