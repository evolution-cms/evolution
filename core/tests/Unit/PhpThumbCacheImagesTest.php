<?php

/*
|--------------------------------------------------------------------------
| phpThumb thumbnails in assets/cache/images
|--------------------------------------------------------------------------
|
| Helper::phpThumb() writes thumbnails to assets/cache/images and pages link to them, but the
| Apache and nginx templates refused all of assets/cache, so every thumbnail was a 403/404. And a
| browser that accepts WebP on a host where no WebP converter works (a GD without WebP) turned the
| whole page into a 500: WebPConvert's exception was not caught.
|
*/

use EvolutionCMS\Core;
use EvolutionCMS\HelperProcessor;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use WebPConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperationalException;

function phpThumbRepoFile(string $relative): string
{
    return (string) file_get_contents(dirname(__DIR__, 3) . '/' . $relative);
}

/** Paths the templates must serve, and paths under assets/cache they must keep refusing. */
dataset('phpthumb cache paths', [
    'jpg thumbnail' => ['/assets/cache/images/demo/photo-320x200-fd5.jpg', true],
    'jpeg thumbnail' => ['/assets/cache/images/photo-x200-fd5.jpeg', true],
    'png thumbnail' => ['/assets/cache/images/a/b/logo-200x-402.PNG', true],
    'gif thumbnail' => ['/assets/cache/images/logo-120x-4b8.gif', true],
    'avif thumbnail' => ['/assets/cache/images/photo-100x-abc.avif', true],
    'bmp thumbnail' => ['/assets/cache/images/photo-100x-abc.bmp', true],
    'ico thumbnail' => ['/assets/cache/images/photo-16x-abc.ico', true],
    'wbmp thumbnail' => ['/assets/cache/images/photo-100x-abc.wbmp', true],
    'webp copy' => ['/assets/cache/images/demo/photo-320x200-fd5.jpg.webp', true],
    'image straight in the cache' => ['/assets/cache/probe.png', false],
    'image in another cache folder' => ['/assets/cache/store/probe.png', false],
    'php in images' => ['/assets/cache/images/probe.php', false],
    'php with an image suffix' => ['/assets/cache/images/probe.png.php', false],
    'svg' => ['/assets/cache/images/probe.svg', false],
    'html' => ['/assets/cache/images/probe.html', false],
    'video is never a phpThumb output' => ['/assets/cache/images/clip.mp4', false],
    'webm is never a phpThumb output' => ['/assets/cache/images/clip.webm', false],
    'htaccess' => ['/assets/cache/images/.htaccess', false],
    'folder' => ['/assets/cache/images/', false],
    'site cache' => ['/assets/cache/siteCache.idx.php', false],
]);

it('lets nginx serve phpThumb images in assets/cache/images and nothing else of the cache', function (string $uri, bool $served) {
    $nginx = phpThumbRepoFile('ng.inx');
    expect(preg_match('/location ~\* (\^\/assets\/cache\/images\/\S+) \{/', $nginx, $allow))->toBe(1);
    $deny = 'location ~ ^/assets/(cache|backup|export|import)(/|$)';

    // nginx tries regex locations in order: the exception only counts above the deny
    expect(strpos($nginx, $allow[0]))->toBeLessThan(strpos($nginx, $deny));
    expect(preg_match('~' . $allow[1] . '~i', $uri))->toBe($served ? 1 : 0);
    expect(preg_match('~^/assets/(cache|backup|export|import)(/|$)~', $uri))->toBe(1); // the deny takes the rest
})->with('phpthumb cache paths');

it('lets apache serve phpThumb images in assets/cache/images and nothing else of the cache', function (string $uri, bool $served) {
    $htaccess = str_replace("\r\n", "\n", phpThumbRepoFile('ht.access'));
    expect(preg_match('/RewriteCond %\{REQUEST_FILENAME\} \\\\\.\(([a-z?|]+)\)\$ \[NC\]\n'
        . 'RewriteCond %\{REQUEST_FILENAME\} -f\n'
        . 'RewriteRule \^assets\/cache\/images\/ - \[L\]\n/', $htaccess, $allow))->toBe(1);
    $deny = 'RewriteRule ^assets/(cache|backup|export|import)(/|$) - [F,L]';

    // the image exception must sit directly above the deny that ends rewriting for the rest of the cache
    expect(strpos($htaccess, 'RewriteRule ^assets/cache/images/ - [L]'))->toBeLessThan(strpos($htaccess, $deny));

    // per-directory rules see the path without the leading slash; REQUEST_FILENAME is the file it resolves to
    $path = ltrim($uri, '/');
    $passes = preg_match('~^assets/cache/images/~', $path) === 1 && preg_match('~\.(' . $allow[1] . ')$~i', $path) === 1;
    expect($passes)->toBe($served);
})->with('phpthumb cache paths');

it('keeps the rest of assets/cache closed in the per-directory file', function () {
    expect(phpThumbRepoFile('assets/cache/.htaccess'))
        ->toContain('Require all denied')
        ->not->toContain('FilesMatch')
        ->not->toContain('granted');
});

it('writes the same two-dialect .htaccess into the thumbnail folder as the one shipped there', function () {
    expect(HelperProcessor::CACHE_FOLDER_HTACCESS)
        ->toBe(str_replace("\r\n", "\n", phpThumbRepoFile('assets/cache/images/.htaccess')));
});

it('honours $webp = false', function () {
    expect(phpThumbRepoFile('core/src/HelperProcessor.php'))
        ->toContain('if ($webp && class_exists(')
        ->not->toContain('isset($webp)');
});

/**
 * A HelperProcessor exposing the protected option sanitiser, with no Core needed.
 */
function sanitisingHelper(): object
{
    $core = test()->getMockBuilder(Core::class)->disableOriginalConstructor()->getMock();

    return new class ($core) extends HelperProcessor {
        /** @param array<string, mixed> $params */
        public function sanitise(array $params, string $default): array
        {
            return $this->sanitizeParams($params, $default);
        }
    };
}

it('pins the output format to a real image format, never an executable extension', function (string $requested, string $default, string $expected) {
    $out = sanitisingHelper()->sanitise(['f' => $requested, 'w' => 100], $default);
    expect($out['f'])->toBe($expected);
})->with([
    'php is refused' => ['php', 'jpg', 'jpg'],
    'phtml is refused' => ['phtml', 'png', 'png'],
    'empty falls back' => ['', 'gif', 'gif'],
    'unknown falls back' => ['exe', 'jpg', 'jpg'],
    'webp kept' => ['webp', 'jpg', 'webp'],
    'avif kept' => ['avif', 'jpg', 'avif'],
    'uppercase normalised' => ['PNG', 'jpg', 'png'],
]);

it('drops every option key the phpThumb library must not receive from a caller', function () {
    $out = sanitisingHelper()->sanitise([
        'w' => 120, 'h' => 80, 'zc' => 1, 'q' => 90, 'fltr' => ['flip|x'],   // kept
        'config_cache_directory' => '/tmp/evil',                              // config_* injection
        'config_imagemagick_path' => 'touch /tmp/x;',
        'config_allow_src_above_docroot' => 1,
        'config_document_root' => '/',
        'src' => '/etc/passwd', 'new' => 'x', 'file' => 'y',                  // source repointing
        'nocache' => 1, 'phpThumbDebug' => 1, 'md5s' => 'x',                  // not geometry
    ], 'jpg');

    expect($out)->toHaveKeys(['w', 'h', 'zc', 'q', 'fltr', 'f'])
        ->and(array_keys($out))->each->not->toStartWith('config')
        ->and($out)->not->toHaveKey('src')
        ->and($out)->not->toHaveKey('new')
        ->and($out)->not->toHaveKey('file')
        ->and($out)->not->toHaveKey('nocache')
        ->and($out)->not->toHaveKey('phpThumbDebug')
        ->and($out)->not->toHaveKey('md5s');
});

it('keeps thumbnails inside the cache folder when input tries to climb out', function () {
    expect(phpThumbRepoFile('core/src/HelperProcessor.php'))
        ->toContain("\$folder !== '.' && \$folder !== '..'");
});

/**
 * A HelperProcessor whose WebP converter is $convert, with the event log collected in $log.
 */
function webpHelper(callable $convert, array &$log, int &$calls): HelperProcessor
{
    $core = test()->getMockBuilder(Core::class)->disableOriginalConstructor()->onlyMethods(['logEvent'])->getMock();
    $core->method('logEvent')->willReturnCallback(function ($id, $type, $msg, $source) use (&$log) {
        $log[] = [$type, $source, $msg];
    });

    return new class ($core, $convert, $calls) extends HelperProcessor {
        private $convert;
        private $calls;

        public function __construct($core, callable $convert, int &$calls)
        {
            parent::__construct($core);
            $this->convert = $convert;
            $this->calls = &$calls;
        }

        public function webp(string $filename): bool
        {
            return $this->makeWebp($filename);
        }

        protected function convertToWebp(string $source, string $destination): void
        {
            $this->calls++;
            ($this->convert)($source, $destination);
        }
    };
}

beforeEach(function () {
    Cache::swap(new Repository(new ArrayStore()));
    $this->thumb = sys_get_temp_dir() . '/evo-phpthumb-' . bin2hex(random_bytes(6)) . '.jpg';
    file_put_contents($this->thumb, 'jpeg');
});

afterEach(function () {
    @unlink($this->thumb);
    @unlink($this->thumb . '.webp');
    Facade::clearResolvedInstances();
});

it('serves the thumbnail itself when no WebP converter works, and stops trying for a while', function () {
    $log = [];
    $calls = 0;
    $helper = webpHelper(function () {
        throw new ConverterNotOperationalException('None of the converters in the stack are operational');
    }, $log, $calls);

    expect($helper->webp($this->thumb))->toBeFalse()
        ->and($helper->webp($this->thumb))->toBeFalse()
        ->and($calls)->toBe(1)
        ->and($log)->toHaveCount(1)
        ->and($log[0][0])->toBe(2)
        ->and($log[0][1])->toBe('phpthumb')
        ->and($log[0][2])->toContain(basename($this->thumb))->toContain('None of the converters')
        ->and(Cache::get(HelperProcessor::WEBP_UNAVAILABLE_KEY))->toBeTrue();
});

it('serves the thumbnail itself when one conversion fails, and tries the next image again', function () {
    $log = [];
    $calls = 0;
    $helper = webpHelper(function () {
        throw new RuntimeException('<b>broken</b> image');
    }, $log, $calls);

    expect($helper->webp($this->thumb))->toBeFalse()
        ->and($helper->webp($this->thumb))->toBeFalse()
        ->and($calls)->toBe(2)
        ->and($log)->toHaveCount(2)
        ->and($log[0][2])->toContain('&lt;b&gt;broken&lt;/b&gt;')->not->toContain('<b>')
        ->and(Cache::get(HelperProcessor::WEBP_UNAVAILABLE_KEY))->toBeNull();
});

it('links the WebP copy once it is made, and makes it only once', function () {
    $log = [];
    $calls = 0;
    $helper = webpHelper(function ($source, $destination) {
        file_put_contents($destination, 'webp');
    }, $log, $calls);

    expect($helper->webp($this->thumb))->toBeTrue()
        ->and($helper->webp($this->thumb))->toBeTrue()
        ->and($calls)->toBe(1)
        ->and($log)->toBe([])
        ->and(file_get_contents($this->thumb . '.webp'))->toBe('webp');
});

it('does not try to convert a thumbnail phpThumb failed to write', function () {
    $log = [];
    $calls = 0;
    $helper = webpHelper(fn () => null, $log, $calls);
    @unlink($this->thumb);

    expect($helper->webp($this->thumb))->toBeFalse()
        ->and($calls)->toBe(0)
        ->and($log)->toBe([]);
});

it('works without a cache store, trying every time', function () {
    Facade::clearResolvedInstances();
    $app = Facade::getFacadeApplication();
    Facade::setFacadeApplication(null);
    try {
        $log = [];
        $calls = 0;
        $helper = webpHelper(function () {
            throw new ConverterNotOperationalException('none');
        }, $log, $calls);

        expect($helper->webp($this->thumb))->toBeFalse()
            ->and($helper->webp($this->thumb))->toBeFalse()
            ->and($calls)->toBe(2);
    } finally {
        Facade::setFacadeApplication($app);
    }
});
