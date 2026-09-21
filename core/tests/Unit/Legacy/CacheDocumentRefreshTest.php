<?php

use EvolutionCMS\Legacy\Cache as SyncCache;
use EvolutionCMS\Models\ClosureTable;
use EvolutionCMS\Models\SiteContent;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Cache;
use Tests\Mocks\CacheRefreshHarness;

function cacheRefreshDoc(array $attrs): SiteContent
{
    $doc = new SiteContent();
    $doc->forceFill($attrs + ['pagetitle' => $attrs['alias']]);
    $doc->save();
    return $doc;
}

function cacheRefreshListing(string $file): array
{
    return CacheRefreshHarness::listing($file)['documentListing'];
}

beforeEach(function () {
    $this->dir = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-cache-test-' . uniqid();
    mkdir($this->dir);
    $this->database = $this->dir . '/site.sqlite';
    CacheRefreshHarness::schema(CacheRefreshHarness::boot($this->database));

    global $evo;
    $evo = new CacheRefreshHarness();
    $evo->dir = $this->dir;
    $this->evo = $evo;
    $_SERVER['REQUEST_TIME'] = time();

    $this->sync = new SyncCache();
    $this->sync->setCachepath($this->dir);
});

afterEach(function () {
    global $evo;
    $evo = null;
    Capsule::connection()->disconnect();
    foreach (array_diff(scandir($this->dir), ['.', '..']) as $f) {
        unlink($this->dir . '/' . $f);
    }
    rmdir($this->dir);
});

test('document refresh drops page caches, rebuilds listing and publishing file without opcache reset', function () {
    cacheRefreshDoc(['id' => 1, 'alias' => 'root', 'parent' => 0, 'isfolder' => 1]);
    cacheRefreshDoc(['id' => 2, 'alias' => 'child', 'parent' => 1, 'pub_date' => time() + 3600]);
    file_put_contents($this->dir . '/docid_1.pageCache.php', 'x');
    file_put_contents($this->dir . '/docid_2_abc.pageCache.php', 'x');
    file_put_contents($this->dir . '/keep.txt', 'x');
    Cache::forever('snippet-output', 'stale');

    $this->sync->refreshDocumentCache();

    expect(glob($this->dir . '/*.pageCache.php'))->toBe([])
        ->and(file_exists($this->dir . '/keep.txt'))->toBeTrue()
        ->and(Cache::get('snippet-output'))->toBeNull()
        ->and($this->evo->events)->toBe(['OnBeforeCacheUpdate', 'OnCacheUpdate']);

    $listing = CacheRefreshHarness::listing($this->dir . '/siteCache.idx.php');
    expect($listing['documentListing'])->toBe(['root' => 1, 'root/child' => 2])
        ->and($listing['aliasListing'][2]['path'])->toBe('root');

    $cacheRefreshTime = 0;
    include $this->dir . '/sitePublishing.idx.php';
    expect((int) $cacheRefreshTime)->toBe(SiteContent::find(2)->pub_date)
        ->and(glob($this->dir . '/*.tmp'))->toBe([]);
});

test('only the full clear resets opcache and cleans user password settings', function () {
    $class = new ReflectionClass(SyncCache::class);
    $lines = explode("\n", file_get_contents($class->getFileName()));
    $body = function (string $name) use ($class, $lines) {
        $m = $class->getMethod($name);
        return implode("\n", array_slice($lines, $m->getStartLine() - 1, $m->getEndLine() - $m->getStartLine() + 1));
    };

    expect($body('emptyCache'))->toContain('opcache_reset()')->toContain('UserSetting')
        ->and($body('refreshDocumentCache'))->not->toContain('opcache_reset')->not->toContain('UserSetting');
});

test('moving a document keeps the closure table consistent and the rebuilt listing follows the new parent', function () {
    cacheRefreshDoc(['id' => 1, 'alias' => 'a', 'parent' => 0, 'isfolder' => 1]);
    cacheRefreshDoc(['id' => 2, 'alias' => 'b', 'parent' => 0, 'isfolder' => 1]);
    cacheRefreshDoc(['id' => 3, 'alias' => 'c', 'parent' => 1]);
    cacheRefreshDoc(['id' => 4, 'alias' => 'd', 'parent' => 3]);
    $this->sync->refreshDocumentCache();
    expect(cacheRefreshListing($this->dir . '/siteCache.idx.php'))->toHaveKey('a/c/d');

    $doc = SiteContent::find(3);
    $doc->parent = 2;
    $doc->save();
    $this->sync = new SyncCache();
    $this->sync->setCachepath($this->dir);
    $this->sync->refreshDocumentCache();

    $ancestorsOf4 = ClosureTable::query()->where('descendant', 4)->orderBy('depth')->pluck('ancestor')->all();
    expect($ancestorsOf4)->toBe([4, 3, 2])
        ->and(ClosureTable::query()->where('ancestor', 1)->where('descendant', '!=', 1)->count())->toBe(0);

    $listing = cacheRefreshListing($this->dir . '/siteCache.idx.php');
    expect($listing)->toHaveKey('b/c/d')->not->toHaveKey('a/c/d')
        ->and($listing['b/c'])->toBe(3);
});

test('tree drag-and-drop: moving a subtree to another parent, to root and reordering keeps closure and listing in step', function () {
    cacheRefreshDoc(['id' => 1, 'alias' => 'a', 'parent' => 0, 'isfolder' => 1]);
    cacheRefreshDoc(['id' => 2, 'alias' => 'b', 'parent' => 0, 'isfolder' => 1]);
    cacheRefreshDoc(['id' => 3, 'alias' => 'c', 'parent' => 1, 'isfolder' => 1]);
    cacheRefreshDoc(['id' => 4, 'alias' => 'd', 'parent' => 3]);
    cacheRefreshDoc(['id' => 5, 'alias' => 'e', 'parent' => 2]);
    $ancestors = fn (int $id) => ClosureTable::query()->where('descendant', $id)->orderBy('depth')->pluck('ancestor')->all();

    // what manager/media/style/default/ajax.php movedocument does: model save, then explicit sibling order
    $move = function (int $id, int $parent, array $order) {
        $doc = SiteContent::withTrashed()->find($id);
        $doc->parent = $parent;
        $doc->save();
        foreach ($order as $key => $value) {
            SiteContent::withTrashed()->where('id', $value)->update(['menuindex' => $key]);
        }
        $sync = new SyncCache();
        $sync->setCachepath($this->dir);
        $sync->refreshDocumentCache();
    };

    $move(3, 2, [3, 5]);
    expect($ancestors(4))->toBe([4, 3, 2])
        ->and($ancestors(3))->toBe([3, 2])
        ->and(ClosureTable::query()->where('ancestor', 1)->count())->toBe(1);
    $listing = cacheRefreshListing($this->dir . '/siteCache.idx.php');
    expect($listing)->toHaveKey('b/c/d')->not->toHaveKey('a/c')
        ->and(array_keys($listing))->toBe(['a', 'b', 'b/c', 'b/e', 'b/c/d']);

    $move(3, 0, [1, 3, 2]);
    expect($ancestors(4))->toBe([4, 3])
        ->and($ancestors(3))->toBe([3]);
    $listing = cacheRefreshListing($this->dir . '/siteCache.idx.php');
    expect(array_keys($listing))->toBe(['a', 'c', 'b', 'b/e', 'c/d']);

    $move(3, 1, [3]);
    expect($ancestors(4))->toBe([4, 3, 1])
        ->and(ClosureTable::query()->where('depth', '>', 0)->orderBy('descendant')->orderBy('depth')->get(['ancestor', 'descendant', 'depth'])->map(fn ($r) => "$r->ancestor>$r->descendant:$r->depth")->all())
        ->toBe(['1>3:1', '3>4:1', '1>4:2', '2>5:1'])
        ->and(cacheRefreshListing($this->dir . '/siteCache.idx.php'))->toHaveKey('a/c/d');
});

test('tree move and menuindex sort persist through the model and refresh the document cache', function () {
    $root = dirname(__DIR__, 4);
    $ajax = file_get_contents("$root/manager/media/style/default/ajax.php");
    $move = substr($ajax, strpos($ajax, "case 'movedocument'"), strpos($ajax, "case 'getLockedElements'") - strpos($ajax, "case 'movedocument'"));

    expect($move)->toContain('$document->parent = $parent;')->toContain('$document->save();')->toContain("clearCache('document')")
        ->and($move)->not->toContain("update([
                                'parent'")
        ->and(file_get_contents("$root/manager/actions/mutate_menuindex_sort.dynamic.php"))->toContain("clearCache('document')");
});

test('site cache rebuild waits for a concurrent builder holding the lock', function () {
    cacheRefreshDoc(['id' => 1, 'alias' => 'a', 'parent' => 0]);
    $lockFile = $this->evo->getSiteCacheFilePath() . '.lock';
    $hold = 1.5;
    $script = sprintf('$h=fopen(%s,"c");flock($h,LOCK_EX);echo "locked\n";flush();usleep(%d);flock($h,LOCK_UN);', var_export($lockFile, true), (int) ($hold * 1e6));
    $proc = proc_open([PHP_BINARY, '-r', $script], [1 => ['pipe', 'w']], $pipes);
    expect(trim(fgets($pipes[1])))->toBe('locked');

    $start = microtime(true);
    $this->sync->buildCache($this->evo);
    $elapsed = microtime(true) - $start;
    proc_close($proc);

    expect($elapsed)->toBeGreaterThan($hold * 0.8)
        ->and(cacheRefreshListing($this->dir . '/siteCache.idx.php'))->toBe(['a' => 1]);
});

test('concurrent managers saving documents all end up in the site cache', function () {
    cacheRefreshDoc(['id' => 1, 'alias' => 'home', 'parent' => 0]);
    $worker = dirname(__DIR__, 2) . '/Mocks/cache_refresh_worker.php';
    $procs = $pipes = [];
    for ($i = 1; $i <= 6; $i++) {
        $procs[$i] = proc_open([PHP_BINARY, $worker, $this->database, $this->dir, "manager$i"], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes[$i]);
    }
    foreach ($procs as $i => $proc) {
        $out = stream_get_contents($pipes[$i][1]) . stream_get_contents($pipes[$i][2]);
        expect(proc_close($proc))->toBe(0, "worker $i: $out");
    }

    $listing = cacheRefreshListing($this->dir . '/siteCache.idx.php');
    expect(array_keys($listing))->toEqualCanonicalizing(SiteContent::query()->pluck('alias')->all())
        ->and(count($listing))->toBe(7)
        ->and(glob($this->dir . '/*.tmp'))->toBe([])
        ->and(ClosureTable::query()->where('depth', 0)->count())->toBe(7);
});

test('cache files are replaced atomically and stay includable', function () {
    cacheRefreshDoc(['id' => 1, 'alias' => 'first', 'parent' => 0]);
    $this->sync->buildCache($this->evo);

    SiteContent::find(1)->update(['alias' => 'second']);
    $this->sync->buildCache($this->evo);

    expect(cacheRefreshListing($this->dir . '/siteCache.idx.php'))->toBe(['second' => 1])
        ->and(glob($this->dir . '/*.tmp'))->toBe([]);
});

test('document processors use the document cache path and element processors keep the full clear', function () {
    $root = dirname(__DIR__, 4);
    $document = ['manager/processors/save_content.processor.php', 'manager/processors/publish_content.processor.php', 'manager/processors/unpublish_content.processor.php', 'manager/processors/delete_content.processor.php', 'manager/processors/undelete_content.processor.php', 'manager/processors/remove_content.processor.php', 'core/src/Controllers/MoveDocument.php'];
    $element = ['manager/processors/save_snippet.processor.php', 'manager/processors/save_plugin.processor.php', 'manager/processors/save_htmlsnippet.processor.php', 'manager/processors/save_template.processor.php', 'manager/processors/save_settings.processor.php', 'core/src/Controllers/RefreshSite.php'];

    foreach ($document as $file) {
        expect(file_get_contents("$root/$file"))->toContain("clearCache('document')")->not->toContain("clearCache('full')");
    }
    foreach ($element as $file) {
        expect(file_get_contents("$root/$file"))->toContain("clearCache('full'");
    }
});
