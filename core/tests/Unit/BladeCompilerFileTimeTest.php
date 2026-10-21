<?php

use EvolutionCMS\Extensions\BladeCompiler as FileTimeBladeCompiler;
use EvolutionCMS\Providers\BladeServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

/*
|--------------------------------------------------------------------------
| Compiled views follow the file time of their template
|--------------------------------------------------------------------------
|
| An update archive, a Composer or git checkout gives templates the time of
| their commit, older than the views compiled before the switch. Laravel only
| recompiles a newer template, so 3.5.x ran the dashboard 3.5.8 had compiled.
|
*/

beforeEach(function () {
    $this->workspace = sys_get_temp_dir() . '/evo-blade-filetime-' . getmypid() . '-' . uniqid();
    mkdir($this->workspace . '/views', 0777, true);
    mkdir($this->workspace . '/compiled', 0777, true);
    $this->template = $this->workspace . '/views/dashboard.blade.php';
    $this->compiler = new FileTimeBladeCompiler(new Filesystem(), $this->workspace . '/compiled');
});

afterEach(function () {
    (new Filesystem())->deleteDirectory($this->workspace);
});

function compiledViewsIn(string $workspace): array
{
    return glob($workspace . '/compiled/*.php') ?: [];
}

test('a template replaced by an older one is compiled again', function () {
    $now = time();
    file_put_contents($this->template, 'v358 {{ $feed }}');
    touch($this->template, $now - 60);
    $this->compiler->compile($this->template);
    $before = $this->compiler->getCompiledPath($this->template);

    // The incoming version: a different template with the time of an older commit.
    file_put_contents($this->template, 'v35x {{ $rss }}');
    touch($this->template, $now - 86400);
    clearstatcache();

    expect($this->compiler->isExpired($this->template))->toBeTrue();

    $this->compiler->compile($this->template);
    $after = $this->compiler->getCompiledPath($this->template);

    expect($after)->not->toBe($before)
        ->and(file_get_contents($after))->toContain('v35x')
        // The view of the replaced version is gone, not left to pile up.
        ->and(compiledViewsIn($this->workspace))->toBe([$after]);
});

test('an unchanged template keeps its compiled view', function () {
    file_put_contents($this->template, 'stable');
    touch($this->template, time() - 60);
    $this->compiler->compile($this->template);
    clearstatcache();

    expect($this->compiler->isExpired($this->template))->toBeFalse();
});

test('an edited template is compiled again and replaces its earlier view', function () {
    file_put_contents($this->template, 'first');
    touch($this->template, time() - 120);
    $this->compiler->compile($this->template);

    file_put_contents($this->template, 'second');
    touch($this->template, time() - 60);
    clearstatcache();
    $this->compiler->compile($this->template);

    expect(compiledViewsIn($this->workspace))->toHaveCount(1)
        ->and(file_get_contents(compiledViewsIn($this->workspace)[0]))->toContain('second');
});

test('a view compiled under the path alone is dropped with the first new compile', function () {
    file_put_contents($this->template, 'new');
    $laravelName = (new BladeCompiler(new Filesystem(), $this->workspace . '/compiled'))->getCompiledPath($this->template);
    file_put_contents($laravelName, '<?php echo "compiled by 3.5.8";');

    $this->compiler->compile($this->template);

    expect(file_exists($laravelName))->toBeFalse()
        ->and(compiledViewsIn($this->workspace))->toBe([$this->compiler->getCompiledPath($this->template)]);
});

test('the provider swaps the plain compiler and leaves a custom one alone', function () {
    $app = new Container();
    $app->instance('files', new Filesystem());
    $app->instance('config', new Repository(['view' => ['compiled' => $this->workspace . '/compiled']]));
    $app->singleton('blade.compiler', fn () => new BladeCompiler(new Filesystem(), $this->workspace . '/compiled'));
    (new BladeServiceProvider($app))->register();

    $compiler = $app->make('blade.compiler');

    expect($compiler)->toBeInstanceOf(FileTimeBladeCompiler::class)
        ->and($compiler->getClassComponentAliases())->toHaveKey('dynamic-component');

    $custom = new class(new Filesystem(), $this->workspace . '/compiled') extends BladeCompiler {
    };
    $app->forgetInstance('blade.compiler');
    $app->singleton('blade.compiler', fn () => $custom);
    (new BladeServiceProvider($app))->register();

    expect($app->make('blade.compiler'))->toBe($custom);
});
