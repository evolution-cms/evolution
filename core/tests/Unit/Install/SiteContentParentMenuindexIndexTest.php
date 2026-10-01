<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;

afterEach(function () {
    Facade::clearResolvedInstances();
    Capsule::connection()->disconnect();
});

/** A prefixed SQLite database with the facades the migration uses wired to it. */
function bootParentMenuindexDatabase(): Capsule
{
    $capsule = new Capsule();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'evo_']);
    $capsule->setAsGlobal();

    $container = $capsule->getContainer();
    $container->instance('db', $capsule->getDatabaseManager());
    $container->bind('db.schema', fn () => $capsule->getConnection()->getSchemaBuilder());
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($container);
    class_exists('DB', false) || class_alias(\Illuminate\Support\Facades\DB::class, 'DB');

    $capsule->getConnection()->getSchemaBuilder()->create('site_content', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('parent')->default(0)->index('evo_site_content_parent');
        $table->integer('menuindex')->default(0);
    });

    return $capsule;
}

/** @return array<string, list<string>> index name => columns */
function siteContentIndexes(Capsule $capsule): array
{
    $indexes = [];
    foreach ($capsule->getConnection()->getSchemaBuilder()->getIndexes('site_content') as $index) {
        $indexes[$index['name']] = $index['columns'];
    }

    return $indexes;
}

test('the migration indexes children in menu order, once', function () {
    $capsule = bootParentMenuindexDatabase();
    class_exists('AddParentMenuindexIndexToSiteContent', false)
        || require dirname(__DIR__, 3) . '/database/migrations/2026_10_01_000000_add_parent_menuindex_index_to_site_content.php';
    $migration = new AddParentMenuindexIndexToSiteContent();

    $migration->up();
    $migration->up();

    $indexes = siteContentIndexes($capsule);
    expect($indexes['evo_site_content_parent_menuindex'] ?? null)->toBe(['parent', 'menuindex'])
        // The single-column index stays for queries that filter by parent only.
        ->and($indexes['evo_site_content_parent'] ?? null)->toBe(['parent'])
        ->and(array_filter($indexes, fn ($columns) => $columns === ['parent', 'menuindex']))->toHaveCount(1);
});

test('rolling the migration back drops only its index', function () {
    $capsule = bootParentMenuindexDatabase();
    class_exists('AddParentMenuindexIndexToSiteContent', false)
        || require dirname(__DIR__, 3) . '/database/migrations/2026_10_01_000000_add_parent_menuindex_index_to_site_content.php';
    $migration = new AddParentMenuindexIndexToSiteContent();

    $migration->up();
    $migration->down();
    $migration->down();

    expect(siteContentIndexes($capsule))->not->toHaveKey('evo_site_content_parent_menuindex')
        ->and(siteContentIndexes($capsule))->toHaveKey('evo_site_content_parent');
});
