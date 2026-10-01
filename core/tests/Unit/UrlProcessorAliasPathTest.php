<?php

use EvolutionCMS\Core;
use EvolutionCMS\UrlProcessor;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * articles (2) > category-042 (44) > article-1 (100); a deleted "old" (45) and
 * a deleted duplicate "category-042" (46) under articles; "other" (3) at the root.
 * A prefixed connection, so the table aliases of the joins are exercised.
 */
beforeEach(function () {
    $capsule = new Capsule();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'evo_']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    Model::setConnectionResolver($capsule->getDatabaseManager());
    $capsule->getConnection()->getSchemaBuilder()->create('site_content', function (Blueprint $table) {
        $table->increments('id');
        $table->string('alias')->default('');
        $table->integer('parent')->default(0);
        $table->integer('deleted')->default(0);
        $table->integer('alias_visible')->default(1);
    });
    foreach ([
        ['id' => 2, 'alias' => 'articles', 'parent' => 0],
        ['id' => 3, 'alias' => 'other', 'parent' => 0],
        ['id' => 44, 'alias' => 'category-042', 'parent' => 2],
        ['id' => 45, 'alias' => 'old', 'parent' => 2, 'deleted' => 1],
        ['id' => 46, 'alias' => 'category-042', 'parent' => 3, 'deleted' => 1],
        ['id' => 100, 'alias' => 'article-1', 'parent' => 44],
    ] as $row) {
        Capsule::table('site_content')->insert($row);
    }
    $this->connection = $capsule->getConnection();
});

afterEach(fn () => Capsule::connection()->disconnect());

function aliasPathProcessor(): UrlProcessor
{
    $config = ['use_alias_path' => true];
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

test('an alias path is resolved with one query', function () {
    $processor = aliasPathProcessor();
    $this->connection->enableQueryLog();

    expect($processor->findIdByAliasPath('articles/category-042/article-1'))->toBe(100)
        ->and($this->connection->getQueryLog())->toHaveCount(1)
        ->and($processor->findIdByAliasPath('articles/category-042'))->toBe(44)
        ->and($processor->findIdByAliasPath('/articles/'))->toBe(2);
});

test('a path that is not made of live aliases from the root is not resolved', function () {
    $processor = aliasPathProcessor();

    expect($processor->findIdByAliasPath('articles/old'))->toBeNull()
        ->and($processor->findIdByAliasPath('other/category-042'))->toBeNull()
        ->and($processor->findIdByAliasPath('category-042'))->toBeNull()
        ->and($processor->findIdByAliasPath('articles/44'))->toBeNull()
        ->and($processor->findIdByAliasPath('articles//category-042'))->toBeNull()
        ->and($processor->findIdByAliasPath(''))->toBeNull();
});

test('getIdFromAlias still walks aliases and ids and returns integers', function () {
    $processor = aliasPathProcessor();

    expect($processor->getIdFromAlias('articles/category-042'))->toBe(44)
        ->and($processor->getIdFromAlias('articles/44/article-1'))->toBe(100)
        ->and($processor->getIdFromAlias('.'))->toBe(0);
});
