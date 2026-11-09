<?php

use EvolutionCMS\Core;
use EvolutionCMS\Legacy\Cache as SyncCache;
use EvolutionCMS\Models\SiteContent;
use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\Mocks\CacheRefreshHarness;

/*
|--------------------------------------------------------------------------
| Refresh site without publish_document must not strand an overdue schedule
|--------------------------------------------------------------------------
|
| Refresh site (a=26) clears the cache and, for a user with publish_document,
| applies the due (un)publish. When the user lacks publish_document the apply is
| skipped, but the cache clear still runs
| (clearCache('full') -> Legacy\Cache::emptyCache() -> publishTimeConfig()).
|
| publishTimeConfig() recomputes the publish threshold from getCacheRefreshTime().
| That method used to look only at FUTURE pub_date / unpub_date, so a transition
| already past its time but not yet synced dropped out of the threshold, which
| became the next future date or 0. Core::updatePubStatus() then never scheduled the
| sync, and the stored published flag stayed stale indefinitely - the front-end
| fallback relied on in the PR did not actually fire.
|
| getCacheRefreshTime() now also detects an overdue-but-unsynced transition - with
| the scopes Core::runPubStatusSync() applies (SiteContent::publishDocuments() /
| unPublishDocuments(), as Refresh site does) - and pins the threshold to "now" so
| the next front-end request runs the sync. A publication window whose unpub_date
| has passed as well is over: it is neither published only to be unpublished again
| nor kept due, which would repeat those writes and events on every request. These
| tests drive the real threshold calculation and the real Core::runPubStatusSync()
| end to end.
|
*/

/**
 * Drives the real Core::runPubStatusSync() against the harness database without the heavy
 * Core bootstrap: only the handful of methods that method calls are overridden.
 */
class OverduePubSyncCore extends Core
{
    /** @var array<int, array{0:string,1:?int}> */
    public array $firedEvents = [];
    /** @var string[] */
    public array $clearedCaches = [];
    public string $cachePath = '';

    public function __construct()
    {
        // bypass the full Core bootstrap; runPubStatusSync() needs none of it
    }

    public function invokeEvent($evtName, $extParams = [])
    {
        $this->firedEvents[] = [$evtName, isset($extParams['docid']) ? (int) $extParams['docid'] : null];
        return false;
    }

    public function clearCache($type = '', $report = false)
    {
        $this->clearedCaches[] = (string) $type;
    }

    public function bootstrapPath($path = '')
    {
        return $this->cachePath;
    }

    public function getConfig($name = '', $default = null)
    {
        return $default;
    }

    public function syncNow(int $time): void
    {
        $this->runPubStatusSync($time);
    }
}

function overdueMakeDoc(array $attrs): void
{
    $doc = new SiteContent();
    $doc->forceFill($attrs + ['pagetitle' => $attrs['alias']]);
    $doc->save();
}

function overdueThreshold(string $dir): int
{
    $src = (string) file_get_contents($dir . '/sitePublishing.idx.php');
    return preg_match('/\\$cacheRefreshTime\\s*=\\s*\'?(\\d+)\'?/', $src, $m) ? (int) $m[1] : 0;
}

beforeEach(function () {
    $this->dir = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-overdue-' . uniqid();
    mkdir($this->dir);
    $capsule = CacheRefreshHarness::boot($this->dir . '/site.sqlite');
    CacheRefreshHarness::schema($capsule);
    // runPubStatusSync() writes publishedon; the harness schema does not ship it
    $capsule->getConnection()->getSchemaBuilder()->table('site_content', function ($t) {
        $t->unsignedInteger('publishedon')->default(0);
    });

    global $evo;
    $evo = new CacheRefreshHarness();
    $evo->dir = $this->dir;
    $this->evo = $evo;
    $this->now = time();
    $_SERVER['REQUEST_TIME'] = $this->now;

    $this->sync = new SyncCache();
    $this->sync->setCachepath($this->dir);
});

afterEach(function () {
    global $evo;
    $evo = null;
    Capsule::connection()->disconnect();
    foreach (array_diff(scandir($this->dir), ['.', '..']) as $f) {
        @unlink($this->dir . '/' . $f);
    }
    @rmdir($this->dir);
});

it('keeps an overdue transition in the refresh threshold so the front-end sync still applies it', function (string $kind, bool $withFuture) {
    $now = $this->now;

    // the overdue, not-yet-synced transition
    if ($kind === 'publish') {
        overdueMakeDoc(['id' => 1, 'alias' => 'overdue', 'published' => 0, 'pub_date' => $now - 100]);
    } else {
        overdueMakeDoc(['id' => 1, 'alias' => 'overdue', 'published' => 1, 'unpub_date' => $now - 100]);
    }
    // optionally a later schedule that is still in the future
    if ($withFuture) {
        overdueMakeDoc(['id' => 2, 'alias' => 'later', 'published' => 0, 'pub_date' => $now + 3600]);
    }

    $before = (int) SiteContent::find(1)->published;

    // Refresh site without publish_document: the (un)publish is skipped, but the cache
    // clear rebuilds the publishing threshold (clearCache('full') path).
    $this->sync->publishTimeConfig();

    // the manager action itself did not apply the transition
    expect((int) SiteContent::find(1)->published)->toBe($before)
        // the cache clear wrote the publishing file
        ->and(is_file($this->dir . '/sitePublishing.idx.php'))->toBeTrue();

    // the overdue transition kept the threshold at/<= now, so updatePubStatus()'s gate
    // (timeNow >= cacheRefreshTime && cacheRefreshTime != 0) now passes
    $threshold = overdueThreshold($this->dir);
    expect($threshold)->not->toBe(0)
        ->and($threshold)->toBeLessThanOrEqual($now)
        ->and($now >= $threshold && $threshold !== 0)->toBeTrue();

    // the front-end request then applies the transition and fires the matching event
    $core = new OverduePubSyncCore();
    $core->cachePath = $this->dir;
    $core->syncNow($now);

    $expectedFlag = $kind === 'publish' ? 1 : 0;
    $expectedEvent = $kind === 'publish' ? 'OnDocPublished' : 'OnDocUnPublished';
    expect((int) SiteContent::find(1)->published)->toBe($expectedFlag)
        ->and($core->firedEvents)->toContain([$expectedEvent, 1]);
    if ($kind === 'unpublish') {
        // an unpublish drops only the affected page cache
        expect($core->clearedCaches)->toContain('1');
    }
    if ($withFuture) {
        // the still-future document is left for its own time
        expect((int) SiteContent::find(2)->published)->toBe(0);
    }

    // the threshold self-heals: the next future date when one remains, else 0 - no
    // sync-on-every-request loop once the overdue work is done
    expect(overdueThreshold($this->dir))->toBe($withFuture ? $now + 3600 : 0);
})->with([
    'overdue publish, no future schedule'      => ['publish', false],
    'overdue publish, later future schedule'   => ['publish', true],
    'overdue unpublish, no future schedule'    => ['unpublish', false],
    'overdue unpublish, later future schedule' => ['unpublish', true],
]);

it('does not pin the threshold to now when nothing is actually overdue', function () {
    $now = $this->now;
    // a future schedule, plus a past date that has already been applied (published = 1)
    overdueMakeDoc(['id' => 1, 'alias' => 'later', 'published' => 0, 'pub_date' => $now + 3600]);
    overdueMakeDoc(['id' => 2, 'alias' => 'applied', 'published' => 1, 'pub_date' => $now - 100]);

    $this->sync->publishTimeConfig();

    // threshold is the future date, not pinned to now - an already-applied past date
    // (published = 1) is not treated as overdue, so there is no sync-every-request loop
    expect(overdueThreshold($this->dir))->toBe($now + 3600);
});

it('writes a zero threshold when there is no schedule at all', function () {
    $this->sync->publishTimeConfig();

    expect(overdueThreshold($this->dir))->toBe(0);
});

it('treats a publication window that has already ended as complete', function (int $published, int $pubOffset, int $unpubOffset) {
    $now = $this->now;
    overdueMakeDoc([
        'id' => 1, 'alias' => 'ended', 'published' => $published,
        'pub_date' => $now + $pubOffset, 'unpub_date' => $now + $unpubOffset,
    ]);

    $this->sync->publishTimeConfig();
    // only a document still marked published has work left (the unpublish)
    expect(overdueThreshold($this->dir))->toBe($published === 1 ? $now : 0);

    $core = new OverduePubSyncCore();
    $core->cachePath = $this->dir;
    $core->syncNow($now);

    // it ends unpublished; a published document is unpublished once, never re-published
    expect((int) SiteContent::find(1)->published)->toBe(0)
        ->and($core->firedEvents)->toBe($published === 1 ? [['OnDocUnPublished', 1]] : [])
        ->and(overdueThreshold($this->dir))->toBe(0);

    // a repeated sync (the next front-end request) writes and fires nothing
    $core->firedEvents = [];
    $core->clearedCaches = [];
    $core->syncNow($now + 1);

    expect((int) SiteContent::find(1)->published)->toBe(0)
        ->and($core->firedEvents)->toBe([])
        ->and($core->clearedCaches)->toBe([]);
})->with([
    'never published, unpub after pub' => [0, -200, -100],
    'never published, unpub before pub' => [0, -100, -200],
    'still published, both dates past' => [1, -200, -100],
]);
