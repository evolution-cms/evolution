<?php

use EvolutionCMS\Models\SiteContent;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * Documents 2..4 under folder 1; 4 is soft-deleted, 3 was deleted once (deletedon set).
 */
function bootToArraysDatabase(): Capsule
{
    $capsule = new Capsule();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'evo_']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    Model::setConnectionResolver($capsule->getDatabaseManager());

    $capsule->getConnection()->getSchemaBuilder()->create('site_content', function (Blueprint $table) {
        $table->increments('id');
        $table->string('pagetitle')->default('');
        $table->string('alias')->default('');
        $table->integer('parent')->default(0);
        $table->integer('published')->default(1);
        $table->integer('pub_date')->default(0);
        $table->integer('menuindex')->default(0);
        $table->integer('hidemenu')->default(0);
        $table->integer('richtext')->default(1);
        $table->integer('deleted')->default(0);
        $table->integer('deletedon')->default(0);
        $table->text('content')->nullable();
    });
    foreach ([
        ['id' => 1, 'pagetitle' => 'Folder', 'alias' => 'folder', 'parent' => 0, 'menuindex' => 0, 'content' => null],
        ['id' => 2, 'pagetitle' => 'Second', 'alias' => 'second', 'parent' => 1, 'menuindex' => 2, 'pub_date' => 1700000000, 'hidemenu' => 1, 'content' => 'b'],
        ['id' => 3, 'pagetitle' => 'First', 'alias' => 'first', 'parent' => 1, 'menuindex' => 1, 'deletedon' => 1790625541, 'content' => 'a'],
        ['id' => 4, 'pagetitle' => 'Gone', 'alias' => 'gone', 'parent' => 1, 'menuindex' => 3, 'deleted' => 1, 'content' => 'c'],
    ] as $row) {
        Capsule::table('site_content')->insert($row);
    }

    return $capsule;
}

afterEach(fn () => Capsule::connection()->disconnect());

test('a listing of scalar columns is the same as get()->toArray()', function () {
    bootToArraysDatabase();
    $query = fn () => SiteContent::query()
        ->select(['site_content.id', 'site_content.pagetitle', 'site_content.pub_date', 'site_content.hidemenu', 'site_content.richtext'])
        ->where('site_content.parent', 1)
        ->orderBy('menuindex');

    $rows = SiteContent::toArrays($query());

    expect($rows)->toBe($query()->get()->toArray())
        ->and($rows)->toBe([
            ['id' => 3, 'pagetitle' => 'First', 'pub_date' => 0, 'hidemenu' => false, 'richtext' => true],
            ['id' => 2, 'pagetitle' => 'Second', 'pub_date' => 1700000000, 'hidemenu' => true, 'richtext' => true],
        ]);
});

test('whole rows with the deleted-at column are the same as get()->toArray()', function () {
    bootToArraysDatabase();
    $query = fn () => SiteContent::query()->withTrashed()->orderBy('id');

    $rows = SiteContent::toArrays($query());

    expect($rows)->toBe($query()->get()->toArray())
        ->and($rows[2]['deletedon'])->toBe(1790625541)
        ->and($rows[0]['content'])->toBeNull()
        ->and(array_column($rows, 'id'))->toBe([1, 2, 3, 4]);
});

test('global scopes and limits apply as they do to get()', function () {
    bootToArraysDatabase();

    $rows = SiteContent::toArrays(SiteContent::query()->where('parent', 1)->orderBy('id')->limit(1));

    expect(array_column($rows, 'id'))->toBe([2])
        ->and(SiteContent::toArrays(SiteContent::query()->where('parent', 1)))->toHaveCount(2);
});

test('an empty result is an empty array', function () {
    bootToArraysDatabase();

    expect(SiteContent::toArrays(SiteContent::query()->where('parent', 99)))->toBe([]);
});

test('a model with an accessor on a selected column still goes through the model', function () {
    bootToArraysDatabase();
    $model = new class extends SiteContent {
        public function getPagetitleAttribute($value): string
        {
            return strtoupper((string) $value);
        }
    };
    $query = fn () => $model->newQuery()->select(['site_content.id', 'site_content.pagetitle'])->where('site_content.id', 2);

    expect(SiteContent::toArrays($query()))->toBe($query()->get()->toArray())
        ->and(SiteContent::toArrays($query())[0]['pagetitle'])->toBe('SECOND');
});
