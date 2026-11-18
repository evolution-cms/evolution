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
    array_map('unlink', glob($this->dir . '/*') ?: []);
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
        ->and($handler)->toContain('data: {_token: csrfToken()}');
});

test('the catalog is fetched past its 15 minute cache, and a failed fetch keeps the cached copy', function () {
    $cacheFile = rtrim(EVO_CORE_PATH, '/\\') . '/custom/cache/store/console-catalog.json';
    $saved = is_file($cacheFile) ? file_get_contents($cacheFile) : null;
    @mkdir(dirname($cacheFile), 0775, true);
    file_put_contents($cacheFile, '{"packages":[{"name":"old/package"}]}');

    $catalog = new class extends \EvolutionCMS\Services\Store\CatalogService {
        public string $answer = '';

        public function fetchRemoteBody($url)
        {
            return $this->answer;
        }
    };

    try {
        $catalog->answer = '<html>rate limited</html>';
        expect($catalog->refreshConsoleCatalog())->toBeFalse()
            ->and(file_get_contents($cacheFile))->toContain('old/package');

        $catalog->answer = '{"packages":[{"name":"new/package"}]}';
        expect($catalog->refreshConsoleCatalog())->toBeTrue()
            ->and(file_get_contents($cacheFile))->toContain('new/package');
    } finally {
        $saved === null ? @unlink($cacheFile) : file_put_contents($cacheFile, $saved);
    }
});
