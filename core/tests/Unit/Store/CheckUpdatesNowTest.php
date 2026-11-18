<?php

/*
| An administrator who learns of a security fix must see it at once, not after the next daily
| check: the Extras module's "Check for updates" drops the Updater's release and branch-head
| caches and the outdated-extras list, and fetches the package catalog again. Any other manager
| is refused, so nobody else can spend the site's GitHub quota.
*/

use EvolutionCMS\Services\Store\ModuleActionService;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;

if (!defined('EVO_CORE_PATH')) {
    define('EVO_CORE_PATH', dirname(__DIR__, 3) . '/');
}

function checkUpdatesStore(bool $superAdmin, bool $catalogFetched = true): object
{
    return new class($superAdmin, $catalogFetched) {
        public array $lang = [];
        public int $catalogRefreshes = 0;

        public function __construct(private bool $superAdmin, private bool $catalogFetched)
        {
        }

        public function isSuperAdmin()
        {
            return $this->superAdmin;
        }

        public function refreshConsoleCatalog()
        {
            $this->catalogRefreshes++;

            return $this->catalogFetched;
        }
    };
}

beforeEach(function () {
    $this->facadeApp = Facade::getFacadeApplication();
    $this->cache = new Repository(new ArrayStore());
    $container = new Container();
    $container->instance('cache', $this->cache);
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($container);

    $this->dir = sys_get_temp_dir() . '/evo-check-updates-' . bin2hex(random_bytes(4));
    mkdir($this->dir);
    foreach (['check_18.json', 'check_17.json', 'head_abc_2026111813.json', 'forced_note.json'] as $file) {
        file_put_contents($this->dir . '/' . $file, '{}');
    }
    $this->cache->put(ModuleActionService::OUTDATED_EXTRAS_CACHE_KEY, ['plugin' => []], 60);
});

afterEach(function () {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    @rmdir($this->dir);
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this->facadeApp);
});

test('an administrator drops the update caches and fetches the catalog again', function () {
    $store = checkUpdatesStore(true);

    $result = (new ModuleActionService())->checkUpdatesNow($store, 'POST', $this->dir);

    expect($result)->toBe(['ok' => true, 'updater_cache_dropped' => 3, 'catalog_refreshed' => true])
        ->and(array_map('basename', glob($this->dir . '/*')))->toBe(['forced_note.json'])
        ->and($this->cache->has(ModuleActionService::OUTDATED_EXTRAS_CACHE_KEY))->toBeFalse()
        ->and($store->catalogRefreshes)->toBe(1);
});

test('a catalog that could not be fetched is reported, the caches are dropped all the same', function () {
    $result = (new ModuleActionService())->checkUpdatesNow(checkUpdatesStore(true, false), 'POST', $this->dir);

    expect($result['ok'])->toBeTrue()
        ->and($result['catalog_refreshed'])->toBeFalse()
        ->and(glob($this->dir . '/check_*.json'))->toBe([]);
});

test('another manager cannot spend the GitHub quota', function () {
    $store = checkUpdatesStore(false);

    $result = (new ModuleActionService())->checkUpdatesNow($store, 'POST', $this->dir);

    expect($result['ok'])->toBeFalse()
        ->and($result['error_code'])->toBe('ACL_DENIED')
        ->and(count(glob($this->dir . '/*')))->toBe(4)
        ->and($this->cache->has(ModuleActionService::OUTDATED_EXTRAS_CACHE_KEY))->toBeTrue()
        ->and($store->catalogRefreshes)->toBe(0);
});

test('the check takes POST only', function () {
    $store = checkUpdatesStore(true);

    $result = (new ModuleActionService())->checkUpdatesNow($store, 'GET', $this->dir);

    expect($result['error_code'])->toBe('METHOD_NOT_ALLOWED')
        ->and(count(glob($this->dir . '/*')))->toBe(4)
        ->and($store->catalogRefreshes)->toBe(0);
});

test('the module routes check_updates and drops the key OutdatedExtrasCheck caches under', function () {
    $root = dirname(__DIR__, 4);
    $actions = (string) file_get_contents($root . '/core/src/Services/Store/ModuleActionService.php');
    $plugin = (string) file_get_contents($root . '/assets/plugins/extrascheck/CheckOutdated.class.php');

    expect($actions)->toContain("case 'check_updates':")
        ->and($plugin)->toContain("public const CACHE_KEY = '" . ModuleActionService::OUTDATED_EXTRAS_CACHE_KEY . "';")
        ->and($plugin)->toContain('Cache::remember(self::CACHE_KEY, 24 * 60,')
        ->and($plugin)->not->toContain("Cache::remember('users'");
});

test('the button is rendered for administrators only and posts with the CSRF token', function () {
    $root = dirname(__DIR__, 4);
    $view = (string) file_get_contents($root . '/core/src/Services/Store/ModuleViewService.php');
    $template = (string) file_get_contents($root . '/assets/modules/store/template/main.html');
    $script = (string) file_get_contents($root . '/assets/modules/store/js/store.js');
    $handler = substr($script, strpos($script, 'bindCheckUpdates: function(){'), 2400);

    expect($view)->toContain("\$store->lang['check_updates_attr'] = \$store->isSuperAdmin() ? '' : 'hidden';")
        ->and($template)->toContain('<span class="input-group-btn" [+check_updates_attr+]>')
        ->and($handler)->toContain("url: link() + '&action=check_updates',")
        ->and($handler)->toContain("type: 'POST',")
        ->and($handler)->toContain('data: {_token: csrfToken()}')
        // the notice is decided by check-updates.js, which the template loads before store.js
        ->and($handler)->toContain('window.evoStoreCheckUpdates.noticeFor(data, {')
        ->and($handler)->toContain("partial: $('[name=\"check_updates_partial\"]').val(),")
        ->and(strpos($template, 'js/check-updates.js?v='))->toBeLessThan(strpos($template, 'js/store.js?v='));
});

/** Catalog service that answers with the given body and keeps its cache in the given folder. */
function scriptedCatalog(string $directory): object
{
    return new class($directory) extends \EvolutionCMS\Services\Store\CatalogService {
        public string $answer = '';

        public function __construct(private string $directory)
        {
            parent::__construct();
        }

        public function fetchRemoteBody($url)
        {
            return $this->answer;
        }

        protected function getConsoleCatalogCacheDirectory(): string
        {
            return $this->directory;
        }
    };
}

test('the catalog is fetched past its 15 minute cache, and a failed fetch keeps the cached copy', function () {
    $cacheFile = $this->dir . '/store/console-catalog.json';
    mkdir(dirname($cacheFile));
    file_put_contents($cacheFile, '{"packages":[{"name":"old/package"}]}');
    $catalog = scriptedCatalog(dirname($cacheFile));

    $catalog->answer = '<html>rate limited</html>';
    expect($catalog->refreshConsoleCatalog())->toBeFalse()
        ->and(file_get_contents($cacheFile))->toContain('old/package');

    $catalog->answer = '{"packages":[{"name":"new/package"}]}';
    expect($catalog->refreshConsoleCatalog())->toBeTrue()
        ->and(file_get_contents($cacheFile))->toContain('new/package')
        ->and(glob(dirname($cacheFile) . '/*.tmp'))->toBe([]);
});

test('a valid catalog that cannot be stored is not reported as refreshed', function () {
    // a file where the cache folder belongs: the folder cannot be created, for root either
    file_put_contents($this->dir . '/store', 'not a folder');
    $catalog = scriptedCatalog($this->dir . '/store/cache');
    $catalog->answer = '{"packages":[{"name":"new/package"}]}';

    expect($catalog->refreshConsoleCatalog())->toBeFalse()
        ->and(file_get_contents($this->dir . '/store'))->toBe('not a folder');
});

test('a failed write leaves what was stored before untouched and no temporary file behind', function () {
    $directory = $this->dir . '/store';
    // the catalog path taken by a folder: the rename onto it fails, as a write the web user may not do
    mkdir($directory . '/console-catalog.json', 0775, true);
    file_put_contents($directory . '/console-catalog.json/keep', 'previous');
    $catalog = scriptedCatalog($directory);
    $catalog->answer = '{"packages":[{"name":"new/package"}]}';

    expect($catalog->refreshConsoleCatalog())->toBeFalse()
        ->and(file_get_contents($directory . '/console-catalog.json/keep'))->toBe('previous')
        ->and(glob($directory . '/*.tmp'))->toBe([]);
});

test('the Extras module tells a partial refresh from a full one', function () {
    $node = (new Symfony\Component\Process\ExecutableFinder())->find('node');
    if ($node === null) {
        $this->markTestSkipped('Node.js is not installed.');
    }

    $test = dirname(__DIR__, 4) . '/assets/modules/store/js/tests/check-updates.test.js';
    $output = evoRunCommand(escapeshellarg($node) . ' --test ' . escapeshellarg($test) . ' 2>&1', $exitCode);

    expect($exitCode)->toBe(0, $output);
});
