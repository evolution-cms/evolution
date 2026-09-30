<?php

use EvolutionCMS\Models\SiteContent;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

afterEach(fn () => Facade::clearResolvedInstances());

/**
 * TVs: 1 price (default "0"), 2 sku (no default), 3 unused (default "u").
 * Documents 10 and 11 carry values, 12 has none, 11 stores an empty price.
 * A prefixed connection, as on a real install, so the table aliases are exercised.
 */
function bootTvValuesDatabase(): Capsule
{
    $capsule = new Capsule();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'evo_']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    Model::setConnectionResolver($capsule->getDatabaseManager());

    $schema = $capsule->getConnection()->getSchemaBuilder();
    $schema->create('site_tmplvars', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name')->default('');
        $table->text('default_text')->nullable();
    });
    $schema->create('site_tmplvar_contentvalues', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('tmplvarid');
        $table->integer('contentid');
        $table->text('value')->nullable();
        $table->unique(['tmplvarid', 'contentid']);
    });

    Capsule::table('site_tmplvars')->insert([
        ['id' => 1, 'name' => 'price', 'default_text' => '0'],
        ['id' => 2, 'name' => 'sku', 'default_text' => ''],
        ['id' => 3, 'name' => 'unused', 'default_text' => 'u'],
    ]);
    Capsule::table('site_tmplvar_contentvalues')->insert([
        ['tmplvarid' => 1, 'contentid' => 10, 'value' => '19.90'],
        ['tmplvarid' => 2, 'contentid' => 10, 'value' => 'A-10'],
        ['tmplvarid' => 3, 'contentid' => 10, 'value' => 'not asked'],
        ['tmplvarid' => 1, 'contentid' => 11, 'value' => ''],
        ['tmplvarid' => 2, 'contentid' => 11, 'value' => 'A-11'],
        ['tmplvarid' => 1, 'contentid' => 99, 'value' => 'other document'],
    ]);

    return $capsule;
}

test('reads only the named TVs of every document in one query', function () {
    $capsule = bootTvValuesDatabase();
    $capsule->getConnection()->enableQueryLog();

    $values = SiteContent::getTvValues([10, 11, 12], ['price', 'sku']);

    expect($values)->toBe([
        10 => ['price' => '19.90', 'sku' => 'A-10'],
        11 => ['price' => '0', 'sku' => 'A-11'],
        12 => ['price' => '0', 'sku' => ''],
    ])->and($capsule->getConnection()->getQueryLog())->toHaveCount(1);
});

test('without defaults a missing or empty value is an empty string', function () {
    bootTvValuesDatabase();

    expect(SiteContent::getTvValues([11, 12], ['price'], false))->toBe([
        11 => ['price' => ''],
        12 => ['price' => ''],
    ]);
});

test('accepts TV ids and string document ids', function () {
    bootTvValuesDatabase();

    expect(SiteContent::getTvValues(['10', 10], [1, '2']))->toBe([
        10 => ['price' => '19.90', 'sku' => 'A-10'],
    ]);
});

test('returns nothing without documents, without names or when no TV matches', function () {
    bootTvValuesDatabase();

    expect(SiteContent::getTvValues([], ['price']))->toBe([])
        ->and(SiteContent::getTvValues([10], ['', ' ']))->toBe([])
        ->and(SiteContent::getTvValues([10], ['missing']))->toBe([]);
});

test('getTvList keeps its result shape on top of the bulk read', function () {
    bootTvValuesDatabase();
    $docs = new Collection([['id' => 10], ['id' => 12]]);

    expect(SiteContent::getTvList($docs, ['price']))->toBe([
        10 => ['price' => '19.90'],
        12 => ['price' => '0'],
    ])->and(SiteContent::getTvList($docs, []))->toBe([]);
});
