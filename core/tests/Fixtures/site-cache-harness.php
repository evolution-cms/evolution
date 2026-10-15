<?php

/*
 * Builds siteCache.idx.php with the real Cache::writeSiteCache() from an in-memory SQLite database
 * whose names carry quotes and backslashes, then loads the file the way Core does (include with
 * $this bound to the core) and prints what it defined as JSON. A name that breaks out of its
 * string literal either fails to parse or runs: the harness reports both.
 *
 * Usage: php site-cache-harness.php
 */

ini_set('display_errors', 'stderr');

define('IN_MANAGER_MODE', true);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', true);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

final class SiteCacheHarnessCore
{
    public array $config = [];
    public array $aliasListing = [];
    public array $documentListing = [];
    public array $documentMap = [];
    public array $contentTypes = [];
    public array $chunkCache = [];
    public array $snippetCache = [];
    public array $pluginCache = [];
    public array $pluginEvent = [];
    public string $file;

    public function getConfig($name = '', $default = null)
    {
        return ['server_offset_time' => 0][$name] ?? $default;
    }

    public function getSiteCacheFilePath(): string
    {
        return $this->file;
    }

    public function invokeEvent(...$args)
    {
        return false;
    }

    public function parseProperties($props): array
    {
        return [];
    }

    public function load(): void
    {
        include $this->file;
    }
}

function evo()
{
    static $core;

    return $core ??= new SiteCacheHarnessCore();
}

function evolutionCMS()
{
    return evo();
}

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$_SERVER['REQUEST_TIME'] = time();

$capsule = new Capsule();
$capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'evo_']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$schema = $capsule->getConnection()->getSchemaBuilder();
$schema->create('system_settings', function (Blueprint $t) {
    $t->string('setting_name');
    $t->text('setting_value')->nullable();
});
$schema->create('site_content', function (Blueprint $t) {
    $t->increments('id');
    $t->string('alias')->default('');
    $t->integer('parent')->default(0);
    $t->integer('isfolder')->default(0);
    $t->integer('alias_visible')->default(1);
    $t->integer('deleted')->default(0);
    $t->integer('deletedon')->nullable();
    $t->integer('menuindex')->default(0);
    $t->string('contentType')->default('text/html');
});
$schema->create('site_htmlsnippets', function (Blueprint $t) {
    $t->increments('id');
    $t->string('name');
    $t->text('snippet')->nullable();
    $t->integer('disabled')->default(0);
});
$schema->create('site_modules', function (Blueprint $t) {
    $t->increments('id');
    $t->string('guid')->default('');
    $t->text('properties')->nullable();
});
foreach (['site_snippets' => 'snippet', 'site_plugins' => 'plugincode'] as $table => $code) {
    $schema->create($table, function (Blueprint $t) use ($code) {
        $t->increments('id');
        $t->string('name');
        $t->text($code)->nullable();
        $t->integer('disabled')->default(0);
        $t->text('properties')->nullable();
        $t->string('moduleguid')->default('');
    });
}
$schema->create('system_eventnames', function (Blueprint $t) {
    $t->increments('id');
    $t->string('name');
});
$schema->create('site_plugin_events', function (Blueprint $t) {
    $t->integer('pluginid');
    $t->integer('evtid');
    $t->integer('priority')->default(0);
});

$db = $capsule->getConnection();
$evil = "x');\$GLOBALS['site_cache_injected']=1;//";
$db->table('system_settings')->insert([
    ['setting_name' => 'friendly_urls', 'setting_value' => '0'],
    ['setting_name' => "set'ting\\", 'setting_value' => 'v'],
]);
$db->table('site_content')->insert([
    ['id' => 1, 'alias' => "home'\\", 'contentType' => "text/plain');\$GLOBALS['site_cache_injected']=2;//"],
]);
$db->table('site_htmlsnippets')->insert([
    ['name' => $evil, 'snippet' => 'chunk body'],
    ['name' => "back\\slash", 'snippet' => "it's"],
]);
$db->table('site_snippets')->insert([['name' => "sn'ip", 'snippet' => 'return 1;']]);
$db->table('site_plugins')->insert([['id' => 1, 'name' => "pl'ug", 'plugincode' => 'return;']]);
$db->table('system_eventnames')->insert([['id' => 1, 'name' => "On'Event"]]);
$db->table('site_plugin_events')->insert([['pluginid' => 1, 'evtid' => 1]]);

$core = evo();
$core->file = tempnam(sys_get_temp_dir(), 'evo-site-cache-');

$cache = new \EvolutionCMS\Legacy\Cache();
$cache->setCachepath(dirname($core->file));
(function () use ($core) {
    $this->writeSiteCache($core);
})->call($cache);

$lint = null;
$result = ['parsed' => true, 'error' => null];
try {
    $core->load();
} catch (\ParseError $e) {
    $result = ['parsed' => false, 'error' => $e->getMessage()];
}
@unlink($core->file);

echo json_encode($result + [
    'injected' => $GLOBALS['site_cache_injected'] ?? null,
    'chunks' => array_keys($core->chunkCache),
    'chunkValues' => $core->chunkCache,
    'settings' => array_keys($core->config),
    'aliases' => $core->aliasListing,
    'documents' => $core->documentListing,
    'contentTypes' => $core->contentTypes,
    'snippets' => array_keys($core->snippetCache),
    'plugins' => array_keys($core->pluginCache),
    'events' => $core->pluginEvent,
]);
