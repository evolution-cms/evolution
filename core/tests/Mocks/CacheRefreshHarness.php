<?php

namespace Tests\Mocks;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Facade;

/**
 * Stand-in for the core: only what Legacy\Cache touches while rebuilding the site cache.
 */
class CacheRefreshHarness
{
    public array $config = ['server_offset_time' => 0];
    public array $events = [];
    public string $dir;

    public function getConfig($name = '', $default = null)
    {
        return $this->config[$name] ?? $default;
    }

    public function parseProperties($props, $name = null, $type = null): array
    {
        return [];
    }

    public function invokeEvent($name, $params = [])
    {
        $this->events[] = $name;
        return false;
    }

    public function getSiteCacheFilePath(): string
    {
        return $this->dir . '/siteCache.idx.php';
    }

    public function getSitePublishingFilePath(): string
    {
        return $this->dir . '/sitePublishing.idx.php';
    }

    /** Boots Eloquent (with model events) and the Cache facade against the given sqlite database. */
    public static function boot(string $database = ':memory:'): Capsule
    {
        foreach (['IN_MANAGER_MODE', 'IN_INSTALL_MODE'] as $c) {
            defined($c) || define($c, false);
        }
        defined('EVO_API_MODE') || define('EVO_API_MODE', true);
        defined('EVO_CLASS') || define('EVO_CLASS', self::class);
        require_once dirname(__DIR__, 2) . '/functions/preload.php';

        $database === ':memory:' || is_file($database) || touch($database);
        $capsule = new Capsule();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => $database, 'prefix' => '']);
        $capsule->setEventDispatcher(new Dispatcher(new Container()));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        Model::setConnectionResolver($capsule->getDatabaseManager());
        Model::clearBootedModels();

        $container = new Container();
        $container->instance('cache', new Repository(new ArrayStore()));
        Facade::setFacadeApplication($container);

        return $capsule;
    }

    public static function schema(Capsule $capsule): void
    {
        $schema = $capsule->getConnection()->getSchemaBuilder();
        $schema->create('site_content', function (Blueprint $t) {
            $t->increments('id');
            $t->string('type', 20)->default('document');
            $t->string('contentType', 50)->default('text/html');
            $t->string('pagetitle')->default('');
            $t->string('alias', 245)->nullable()->default('');
            $t->unsignedInteger('published')->default(0);
            $t->unsignedInteger('pub_date')->default(0);
            $t->unsignedInteger('unpub_date')->default(0);
            $t->unsignedInteger('parent')->default(0);
            $t->unsignedInteger('isfolder')->default(0);
            $t->unsignedInteger('menuindex')->default(0);
            $t->unsignedInteger('createdon')->default(0);
            $t->unsignedInteger('editedon')->default(0);
            $t->unsignedInteger('deleted')->default(0);
            $t->unsignedInteger('deletedon')->default(0);
            $t->unsignedInteger('alias_visible')->default(1);
        });
        $schema->create('site_content_closure', function (Blueprint $t) {
            $t->increments('closure_id');
            $t->unsignedInteger('ancestor');
            $t->unsignedInteger('descendant');
            $t->unsignedInteger('depth');
        });
        $schema->create('system_settings', function (Blueprint $t) {
            $t->string('setting_name', 50)->primary();
            $t->text('setting_value')->nullable();
        });
        $schema->create('site_htmlsnippets', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name')->default('');
            $t->text('snippet')->nullable();
            $t->boolean('disabled')->default(0);
        });
        foreach (['site_snippets' => 'snippet', 'site_plugins' => 'plugincode'] as $table => $code) {
            $schema->create($table, function (Blueprint $t) use ($code) {
                $t->increments('id');
                $t->string('name')->default('');
                $t->text($code)->nullable();
                $t->text('properties')->nullable();
                $t->string('moduleguid', 32)->default('');
                $t->boolean('disabled')->default(0);
            });
        }
        $schema->create('site_modules', function (Blueprint $t) {
            $t->increments('id');
            $t->string('guid', 32)->default('');
            $t->text('properties')->nullable();
        });
        $schema->create('system_eventnames', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name', 50)->default('');
        });
        $schema->create('site_plugin_events', function (Blueprint $t) {
            $t->unsignedInteger('pluginid');
            $t->unsignedInteger('evtid');
            $t->integer('priority')->default(0);
        });
        $schema->create('user_settings', function (Blueprint $t) {
            $t->unsignedInteger('user');
            $t->string('setting_name', 50);
            $t->text('setting_value')->nullable();
        });
        $capsule->table('system_settings')->insert([
            ['setting_name' => 'friendly_urls', 'setting_value' => '1'],
            ['setting_name' => 'use_alias_path', 'setting_value' => '1'],
        ]);
    }

    /** Includes a generated site cache the way Core::recoverySiteCache() does. */
    public static function listing(string $file): array
    {
        $reader = new class {
            public $config = [], $aliasListing = [], $documentListing = [], $documentMap = [], $contentTypes = [], $chunkCache = [], $snippetCache = [], $pluginCache = [], $pluginEvent = [];
            public function load(string $file): void { include $file; }
        };
        $reader->load($file);
        return ['aliasListing' => $reader->aliasListing, 'documentListing' => $reader->documentListing, 'config' => $reader->config];
    }
}
