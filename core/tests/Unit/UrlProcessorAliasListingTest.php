<?php

use EvolutionCMS\Core;
use EvolutionCMS\UrlProcessor;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

function aliasListingProcessor(array $config = []): UrlProcessor
{
    $config += [
        'virtual_dir' => '',
        'friendly_url_prefix' => '',
        'friendly_url_suffix' => '',
        'site_start' => 1,
        'friendly_urls' => true,
        'friendly_alias_urls' => true,
        'use_alias_path' => true,
        'aliaslistingfolder' => true,
        'full_aliaslisting' => 1,
        'make_folders' => false,
        'base_url' => '',
    ];
    $core = test()->getMockBuilder(Core::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getConfig', 'invokeEvent'])
        ->getMock();
    $core->documentListing = [];
    $core->aliasListing = [];
    $core->virtualDir = '';
    $core->method('getConfig')->willReturnCallback(static fn ($name, $default = null) => $config[$name] ?? $default);
    $core->method('invokeEvent')->willReturn(false);

    return new UrlProcessor($core);
}

// getAliasListing() reads through the SiteContent model, so a query is caught
// at the Eloquent connection resolver rather than at Core::getDatabase().
function forbidAliasListingQueries(): void
{
    $resolver = Mockery::mock(ConnectionResolverInterface::class);
    $resolver->shouldReceive('connection')
        ->andThrow(new LogicException('The primed alias-listing test must not query the database.'));
    $resolver->shouldReceive('getDefaultConnection')->andReturn('default');
    Model::setConnectionResolver($resolver);
}

function aliasListingDatabase(array $rows): Capsule
{
    $capsule = new Capsule();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    Model::setConnectionResolver($capsule->getDatabaseManager());
    $capsule->getConnection()->getSchemaBuilder()->create('site_content', function (Blueprint $table) {
        $table->increments('id');
        $table->string('alias')->default('');
        $table->integer('parent')->default(0);
        $table->integer('isfolder')->default(0);
        $table->integer('alias_visible')->default(1);
        $table->integer('deleted')->default(0);
        $table->integer('deletedon')->default(0);
    });
    $capsule->table('site_content')->insert($rows);
    $capsule->getConnection()->enableQueryLog();

    return $capsule;
}

afterEach(function () {
    Model::unsetConnectionResolver();
    Mockery::close();
});

test('primed alias listings build friendly tree URLs without per-node queries', function () {
    forbidAliasListingQueries();
    $processor = aliasListingProcessor();

    $processor->primeAliasListings([
        ['id' => 1, 'alias' => 'articles', 'parent' => 0, 'isfolder' => 1, 'alias_visible' => 1],
        ['id' => 2, 'alias' => 'category-001', 'parent' => 1, 'isfolder' => 1, 'alias_visible' => 1],
        ['id' => 3, 'alias' => 'article-000001', 'parent' => 2, 'isfolder' => 0, 'alias_visible' => 1],
    ]);

    expect($processor->makeUrl(3))->toBe('articles/category-001/article-000001')
        ->and($processor->aliasListing[3]['path'])->toBe('articles/category-001');
});

test('primed alias listings skip a parent folder whose alias is hidden', function () {
    forbidAliasListingQueries();
    $processor = aliasListingProcessor();

    $processor->primeAliasListings([
        ['id' => 1, 'alias' => 'articles', 'parent' => 0, 'isfolder' => 1, 'alias_visible' => 1],
        ['id' => 2, 'alias' => 'hidden-folder', 'parent' => 1, 'isfolder' => 1, 'alias_visible' => 0],
        ['id' => 3, 'alias' => 'article', 'parent' => 2, 'isfolder' => 0, 'alias_visible' => 1],
    ]);

    expect($processor->makeUrl(3))->toBe('articles/article')
        ->and($processor->aliasListing[2]['path'])->toBe('articles');
});

test('primed alias listings keep entries that are already loaded', function () {
    forbidAliasListingQueries();
    $processor = aliasListingProcessor();
    $processor->aliasListing[2] = [
        'id' => 2, 'alias' => 'cached', 'path' => 'from-cache', 'parent' => 1, 'isfolder' => 0, 'alias_visible' => 1,
    ];

    $processor->primeAliasListings([
        ['id' => 2, 'alias' => 'fresh', 'parent' => 1, 'isfolder' => 0, 'alias_visible' => 1],
    ]);

    expect($processor->makeUrl(2))->toBe('from-cache/cached');
});

test('a parent that was not primed is loaded once and then reused', function () {
    $capsule = aliasListingDatabase([
        ['id' => 1, 'alias' => 'articles', 'parent' => 0, 'isfolder' => 1, 'alias_visible' => 1],
    ]);
    $processor = aliasListingProcessor();

    $processor->primeAliasListings([
        ['id' => 2, 'alias' => 'first', 'parent' => 1, 'isfolder' => 0, 'alias_visible' => 1],
        ['id' => 3, 'alias' => 'second', 'parent' => 1, 'isfolder' => 0, 'alias_visible' => 1],
    ]);

    expect($processor->makeUrl(2))->toBe('articles/first')
        ->and($processor->makeUrl(3))->toBe('articles/second')
        ->and($capsule->getConnection()->getQueryLog())->toHaveCount(1);
});

test('lazy alias listing is used only when an alias listing mode requires it', function () {
    $defaults = ['aliaslistingfolder' => false, 'full_aliaslisting' => 0];

    expect(aliasListingProcessor($defaults)->usesLazyAliasListing())->toBeFalse()
        ->and(aliasListingProcessor(['aliaslistingfolder' => true] + $defaults)->usesLazyAliasListing())->toBeTrue()
        ->and(aliasListingProcessor(['full_aliaslisting' => '1'] + $defaults)->usesLazyAliasListing())->toBeTrue();
});

test('tree node rows get the SiteContent cast types without touching strings or nulls', function () {
    $row = normalizeTreeNodeRow([
        'id' => '7', 'parent' => '1', 'isfolder' => '0', 'published' => '1', 'pub_date' => '1700000000',
        'unpub_date' => null, 'searchable' => '1', 'cacheable' => '0', 'deleted' => '0', 'template' => '3',
        'menuindex' => '12', 'alias_visible' => '1',
        'richtext' => '1', 'hide_from_tree' => 0, 'hidemenu' => '0', 'privateweb' => 1, 'privatemgr' => null,
        'type' => 'reference', 'alias' => '0012', 'pagetitle' => '42', 'contentType' => 'text/html',
        'templatename' => null,
    ]);

    expect($row)->toBe([
        'id' => 7, 'parent' => 1, 'isfolder' => 0, 'published' => 1, 'pub_date' => 1700000000,
        'unpub_date' => null, 'searchable' => 1, 'cacheable' => 0, 'deleted' => 0, 'template' => 3,
        'menuindex' => 12, 'alias_visible' => 1,
        'richtext' => true, 'hide_from_tree' => false, 'hidemenu' => false, 'privateweb' => true, 'privatemgr' => null,
        'type' => 'reference', 'alias' => '0012', 'pagetitle' => '42', 'contentType' => 'text/html',
        'templatename' => null,
    ]);
});
