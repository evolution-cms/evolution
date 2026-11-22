<?php

use EvolutionCMS\Core;
use EvolutionCMS\Interfaces\CoreInterface;
use EvolutionCMS\ManagerTheme;
use EvolutionCMS\Support\Formatter\CSSMinify;

beforeEach(function () {
    $this->assets = sys_get_temp_dir() . '/evo-manager-css-' . bin2hex(random_bytes(6)) . '/';
    $this->themeDir = $this->assets . 'panel/media/style/default/';
    mkdir($this->themeDir . 'css', 0777, true);
    mkdir($this->assets . 'assets/plugin', 0777, true);
    $this->menu = $this->themeDir . 'css/mainmenu.css';
    $this->index = $this->themeDir . 'css/index.css';
    $this->plugin = $this->assets . 'assets/plugin/custom.css';
    $this->bundle = $this->themeDir . ManagerTheme::CSS_BUNDLE;
    $this->eventBundle = $this->themeDir . ManagerTheme::EVENT_CSS_BUNDLE;
    $this->list = $this->themeDir . 'CSSMinify.php';

    foreach ([$this->menu, $this->index, $this->plugin] as $file) {
        file_put_contents($file, '.overlay{color:red}');
    }
    file_put_contents($this->list, '<?php return ' . var_export([$this->menu, $this->index], true) . ';');
    $core = Mockery::mock(Core::class);
    $core->shouldReceive('getVersionData')->with('version')->andReturn('3.6.0');
    $this->theme = new class($core, $this->assets, $this->themeDir) extends ManagerTheme {
        public function __construct(CoreInterface $core, private string $assets, private string $directory)
        {
            $this->core = $core;
            $this->theme = 'evo-ui';
        }

        public function getThemeDir($full = true): string
        {
            return $full ? $this->directory : 'media/style/default/';
        }

        public function getThemeUrl(): string
        {
            return '/site/panel/' . $this->getThemeDir(false);
        }

        protected function stylesheetAssetRoots(): array
        {
            return [$this->assets . 'panel/' => '/site/panel/', $this->assets . 'assets/' => '/site/assets/'];
        }
    };
});

afterEach(function () {
    chmod($this->themeDir . 'css', 0755);
    foreach (new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->assets, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    ) as $file) {
        if ($file->isDir()) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }
    rmdir($this->assets);
});

test('a stale read-only bundle is replaced in the page by the complete ordered current sources', function () {
    $base = [$this->menu, $this->index];
    $ordered = [$this->index, $this->plugin, $this->menu];

    $old = (new CSSMinify($ordered))->minify($this->eventBundle);
    file_put_contents($this->eventBundle, $old);
    $mtime = filemtime($this->index);
    file_put_contents($this->index, '.overlay{color:blue}');
    touch($this->index, $mtime);
    chmod($this->themeDir . 'css', 0555);
    clearstatcache();
    if (is_writable($this->themeDir . 'css')) {
        $this->markTestSkipped('Filesystem cannot simulate a read-only theme for this process.');
    }

    $this->theme->getCore()->shouldReceive('invokeEvent')
        ->once()
        ->with('OnBeforeMinifyCss', ['files' => $base, 'source' => 'manager', 'theme' => 'evo-ui'])
        ->andReturn([$ordered]);

    expect(CSSMinify::isCacheCurrent($this->eventBundle))->toBeTrue()
        ->and(CSSMinify::isStale($this->eventBundle, $ordered))->toBeTrue()
        ->and($this->theme->stylesheets())->toBe([
            '/site/panel/media/style/default/css/index.css?v=3.6.0.' . hash('sha256', '.overlay{color:blue}'),
            '/site/assets/plugin/custom.css?v=3.6.0.' . hash_file('sha256', $this->plugin),
            '/site/panel/media/style/default/css/mainmenu.css?v=3.6.0.' . hash_file('sha256', $this->menu),
        ])
        ->and(file_get_contents($this->eventBundle))->toBe($old);
});

test('the frame bypasses the minify event and uses a single current bundle', function () {
    $this->theme->getCore()->shouldNotReceive('invokeEvent');
    $url = '/site/panel/media/style/default/css/styles.min.css?v=3.6.0.123.';
    $result = $this->theme->stylesheets('css/page.css', '.123', useEvent: false);

    expect($result)->toBe([$url . filemtime($this->bundle)])
        ->and(CSSMinify::isStale($this->bundle, [$this->menu, $this->index]))->toBeFalse()
        ->and($this->theme->stylesheets('css/page.css', '.123', useEvent: false))->toBe($result);
});

test('a missing frame manifest preserves the page stylesheet even with a marked bundle', function () {
    $this->theme->getCore()->shouldNotReceive('invokeEvent');
    file_put_contents($this->bundle, (new CSSMinify([$this->menu]))->minify($this->bundle));
    unlink($this->list);

    expect($this->theme->stylesheets('css/page.css', '.123', useEvent: false))
        ->toBe(['/site/panel/media/style/default/css/page.css?v=3.6.0.123']);
});

test('an unpublishable source never yields a partial ordered list', function () {
    $adopted = [$this->menu, $this->index];
    $private = $this->assets . 'internal.css';
    file_put_contents($private, '.private{color:red}');
    chmod($this->themeDir . 'css', 0555);
    clearstatcache();
    if (is_writable($this->themeDir . 'css')) {
        $this->markTestSkipped('Filesystem cannot simulate a read-only theme for this process.');
    }

    $this->theme->getCore()->shouldReceive('invokeEvent')
        ->once()
        ->with('OnBeforeMinifyCss', ['files' => $adopted, 'source' => 'manager', 'theme' => 'evo-ui'])
        ->andReturn([[$this->menu, $private, $this->index]]);

    expect($this->theme->stylesheets())->toBe(['/site/panel/media/style/default/style.css?v=3.6.0'])
        ->and(is_file($this->bundle))->toBeFalse();
});


test('the read-only frame uses the original ordered list without invoking the header event', function () {
    $this->theme->getCore()->shouldNotReceive('invokeEvent');
    $old = (new CSSMinify([$this->menu, $this->index]))->minify($this->bundle);
    file_put_contents($this->bundle, $old);
    file_put_contents($this->menu, '.overlay{color:blue}');
    chmod($this->themeDir . 'css', 0555);
    clearstatcache();
    if (is_writable($this->themeDir . 'css')) {
        $this->markTestSkipped('Filesystem cannot simulate a read-only theme for this process.');
    }

    expect($this->theme->stylesheets('css/page.css', '.123', useEvent: false))->toBe([
        '/site/panel/media/style/default/css/mainmenu.css?v=3.6.0.123.' . hash_file('sha256', $this->menu),
        '/site/panel/media/style/default/css/index.css?v=3.6.0.123.' . hash_file('sha256', $this->index),
    ])->and(file_get_contents($this->bundle))->toBe($old);
});

test('event-adjusted header and frame bundles do not overwrite each other', function () {
    $base = [$this->menu, $this->index];
    $ordered = [$this->index, $this->plugin, $this->menu];
    $this->theme->getCore()->shouldReceive('invokeEvent')
        ->twice()
        ->with('OnBeforeMinifyCss', ['files' => $base, 'source' => 'manager', 'theme' => 'evo-ui'])
        ->andReturn([$ordered]);

    $header = $this->theme->stylesheets();
    $frame = $this->theme->stylesheets('css/page.css', '.123', useEvent: false);

    expect($header)->toBe(['/site/panel/media/style/default/css/styles.event.min.css?v=3.6.0.' . filemtime($this->eventBundle)])
        ->and($frame)->toBe(['/site/panel/media/style/default/css/styles.min.css?v=3.6.0.123.' . filemtime($this->bundle)])
        ->and(CSSMinify::isStale($this->eventBundle, $ordered))->toBeFalse()
        ->and(CSSMinify::isStale($this->bundle, $base))->toBeFalse()
        ->and($this->theme->css())->toBe($header[0]);
});
