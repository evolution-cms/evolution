<?php
/** Standalone regressions keep cold-clock checks isolated from other test classes. */
require dirname(__DIR__, 2).'/vendor/autoload.php';

use EvolutionCMS\Cache\FileStore;
use EvolutionCMS\Session\FileSessionHandler;
use Illuminate\Filesystem\Filesystem;

/** Fail loudly without relying on PHP's optional assertion configuration. */
function clockCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$files = new Filesystem;
$dir = sys_get_temp_dir().'/evo-scalar-clock-'.bin2hex(random_bytes(5));
$files->makeDirectory($dir, 0755, true);
$mode = $argv[1] ?? '';
try {
    if ($mode === 'cache-system') {
        $store = new FileStore($files, $dir);
        clockCheck($store->get('missing') === null, 'Missing cache changed');
        $store->put('number', 2, 60);
        clockCheck($store->get('number') === 2 && $store->increment('number', 3) === 5, 'Cache payload/increment changed');
        $store->forever('forever', ['a' => 1]);
        clockCheck($store->get('forever') === ['a' => 1], 'Forever payload changed');
        $store->put('expired', 'gone', -1);
        clockCheck($store->get('expired') === null && !is_file($store->path('expired')), 'Expired cache changed');
        clockCheck(!class_exists(\Carbon\Carbon::class, false) && !class_exists(\Carbon\FactoryImmutable::class, false), 'Scalar cache initialized Carbon');
        $store->put('fraction', 'fraction', 1.5);
        clockCheck($store->get('fraction') === 'fraction', 'Fractional upstream path changed');
    } elseif ($mode === 'cache-frozen') {
        // Only the base Carbon class is configured: Illuminate Carbon need not be loaded yet.
        \Carbon\Carbon::setTestNow('2035-01-02 03:04:05');
        $store = new FileStore($files, $dir);
        $parent = new \Illuminate\Cache\FileStore($files, $dir.'/parent');
        foreach ([0, 1, 60, -1, 1.5, new DateInterval('PT2M'), new DateTimeImmutable('2035-01-03')] as $i => $ttl) {
            $store->put('key'.$i, 'value', $ttl);
            $parent->put('key'.$i, 'value', $ttl);
            clockCheck(file_get_contents($store->path('key'.$i)) === file_get_contents($parent->path('key'.$i)), 'Upstream expiry/serialization changed for '.$i);
            clockCheck($store->get('key'.$i) === $parent->get('key'.$i), 'Upstream payload changed for '.$i);
        }
        \Carbon\Carbon::setTestNow('2035-01-02 03:05:05');
        clockCheck($store->get('key2') === null, 'Exact cache expiry must be expired');
        \Carbon\Carbon::setTestNow();
        $factory = new \Carbon\Factory(['testNow' => '2040-05-06 07:08:09']);
        $factory->now()->settings(['timezone' => 'UTC']);
        $factory->withTestNow('2040-05-06 07:08:09', function () use ($store, $parent) {
            $store->put('factory', 'v', 60); $parent->put('factory', 'v', 60);
            clockCheck(file_get_contents($store->path('factory')) === file_get_contents($parent->path('factory')), 'Factory clock changed');
        });
    } elseif ($mode === 'session-system' || $mode === 'session-frozen') {
        if ($mode === 'session-frozen') \Carbon\Carbon::setTestNow('2035-01-02 03:04:05');
        $now = $mode === 'session-frozen' ? \Carbon\Carbon::now()->getTimestamp() : time();
        $native = new FileSessionHandler($files, $dir, 1);
        clockCheck($native->read('missing') === '', 'Missing session changed');
        file_put_contents($dir.'/live', 'session-data');touch($dir.'/live', $now);
        file_put_contents($dir.'/old', 'expired-data');touch($dir.'/old', $now - 61);
        clockCheck($native->read('live') === 'session-data' && $native->read('old') === '', 'Session expiry changed');
        if ($mode === 'session-system') clockCheck(!class_exists(\Carbon\Carbon::class, false) && !class_exists(\Carbon\FactoryImmutable::class, false), 'Session initialized Carbon');
        else {
            file_put_contents($dir.'/edge', 'edge-data');touch($dir.'/edge', $now - 60);
            clockCheck($native->read('edge') === 'edge-data', 'Inclusive session lifetime boundary changed');
        }
        foreach ([1, '1', 0, -1, 1.5, '1.5'] as $minutes) {
            $parent = new \Illuminate\Session\FileSessionHandler($files, $dir, $minutes);
            $native = new FileSessionHandler($files, $dir, $minutes);
            clockCheck($native->read('live') === $parent->read('live'), 'Session lifetime compatibility changed');
        }
    } elseif ($mode === 'drivers') {
        $app = new \Illuminate\Container\Container;
        $app->instance('files', $files);
        $app->instance('config', new \Illuminate\Config\Repository([
            'cache' => ['default' => 'file', 'stores' => ['file' => ['driver' => 'file', 'path' => $dir.'/cache', 'lock_path' => $dir.'/locks', 'permission' => 0750, 'serializable_classes' => false], 'array' => ['driver' => 'array']]],
            'session' => ['driver' => 'file', 'files' => $dir, 'lifetime' => 10, 'cookie' => 'evo_session', 'serialization' => 'php', 'encrypt' => false],
        ]));
        (new \EvolutionCMS\Providers\CacheServiceProvider($app))->register();
        (new \EvolutionCMS\Providers\SessionServiceProvider($app))->register();
        clockCheck($app->make('cache')->store()->getStore() instanceof FileStore, 'File cache wiring missing');
        clockCheck($app->make('cache')->store('array')->getStore() instanceof \Illuminate\Cache\ArrayStore, 'Non-file cache driver changed');
        clockCheck($app->make('session')->driver()->getHandler() instanceof FileSessionHandler, 'File session wiring missing');
        clockCheck($app->make(\Illuminate\Session\SessionManager::class) === $app->make('session'), 'Session alias changed');
        $app->make('cache')->put('data', ['v' => 1], 60);
        clockCheck($app->make('cache')->get('data') === ['v' => 1], 'Repository wrapper changed');
        $lock = $app->make('cache')->lock('probe', 30);clockCheck($lock->get(), 'File lock acquisition changed');$lock->release();
        $app->make('session')->driver('array');
    } else throw new RuntimeException('Unknown fixture mode');
    echo "PASS ".$mode.PHP_EOL;
} finally {
    if (class_exists(\Carbon\Carbon::class, false)) \Carbon\Carbon::setTestNow();
    $files->deleteDirectory($dir);
}
