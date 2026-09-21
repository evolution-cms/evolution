<?php
// Simulates one manager saving a document: insert a row, then refresh the document cache.
// argv: <sqlite file> <cache dir> <alias>
[, $database, $dir, $alias] = $argv;
require dirname(__DIR__, 2) . '/vendor/autoload.php';

use EvolutionCMS\Legacy\Cache;
use EvolutionCMS\Models\SiteContent;
use Tests\Mocks\CacheRefreshHarness;

$capsule = CacheRefreshHarness::boot($database);
$capsule->getConnection()->statement('PRAGMA busy_timeout = 10000');
$GLOBALS['evo'] = $evo = new CacheRefreshHarness();
$evo->dir = $dir;
$_SERVER['REQUEST_TIME'] = time();

$doc = new SiteContent();
$doc->forceFill(['alias' => $alias, 'pagetitle' => $alias, 'parent' => 0]);
$doc->save();

$sync = new Cache();
$sync->setCachepath($dir);
$sync->refreshDocumentCache();
echo $doc->getKey();
