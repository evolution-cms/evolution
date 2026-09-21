<?php

use EvolutionCMS\Legacy\Permissions;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Exposes the protected lookup used to pick a default parent.
 */
class PermissionsDocumentAccessProbe extends Permissions
{
    public static function firstAccessible()
    {
        return static::findFirstAccessibleDocument();
    }
}

/**
 * Builds the small tree the expectations below are written against:
 *
 *  20 private folder       (root, menuindex 0, document group 5)
 *   └ 21 private child     (document group 5)
 *  10 public folder        (root, menuindex 1)
 *   └ 11 public child
 *  30 trashed public       (root, menuindex 2)
 */
function bootPermissionsFixture(): void
{
    $capsule = new Capsule();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    $schema = Capsule::schema();
    $schema->dropIfExists('site_content');
    $schema->dropIfExists('document_groups');
    $schema->create('site_content', function ($table) {
        $table->increments('id');
        $table->integer('parent')->default(0);
        $table->string('pagetitle')->default('');
        $table->integer('menuindex')->default(0);
        $table->integer('privatemgr')->default(0);
        $table->integer('deleted')->default(0);
    });
    $schema->create('document_groups', function ($table) {
        $table->increments('id');
        $table->integer('document_group');
        $table->integer('document');
    });

    Capsule::table('site_content')->insert([
        ['id' => 10, 'parent' => 0, 'menuindex' => 1, 'privatemgr' => 0, 'deleted' => 0, 'pagetitle' => 'public folder'],
        ['id' => 11, 'parent' => 10, 'menuindex' => 0, 'privatemgr' => 0, 'deleted' => 0, 'pagetitle' => 'public child'],
        ['id' => 20, 'parent' => 0, 'menuindex' => 0, 'privatemgr' => 1, 'deleted' => 0, 'pagetitle' => 'private folder'],
        ['id' => 21, 'parent' => 20, 'menuindex' => 0, 'privatemgr' => 1, 'deleted' => 0, 'pagetitle' => 'private child'],
        ['id' => 30, 'parent' => 0, 'menuindex' => 2, 'privatemgr' => 0, 'deleted' => 1, 'pagetitle' => 'trashed'],
    ]);
    Capsule::table('document_groups')->insert([
        ['document_group' => 5, 'document' => 20],
        ['document_group' => 5, 'document' => 21],
    ]);
}

test('the document lookup is limited to the document being checked', function () {
    bootPermissionsFixture();
    $_SESSION['mgrDocgroups'] = [];

    expect(Permissions::documentIsAccessible(10))->toBeTrue()
        ->and(Permissions::documentIsAccessible(11))->toBeTrue()
        // before the fix the group query counted every row and answered yes for anything
        ->and(Permissions::documentIsAccessible(20))->toBeFalse()
        ->and(Permissions::documentIsAccessible(21))->toBeFalse()
        ->and(Permissions::documentIsAccessible(999))->toBeFalse();
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('private documents open up for the groups the user belongs to', function () {
    bootPermissionsFixture();
    $_SESSION['mgrDocgroups'] = [5];

    expect(Permissions::documentIsAccessible(20))->toBeTrue()
        ->and(Permissions::documentIsAccessible(21))->toBeTrue()
        ->and(Permissions::documentIsAccessible(10))->toBeTrue();
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('trashed documents stay reachable so undelete and publish keep their target', function () {
    bootPermissionsFixture();
    $_SESSION['mgrDocgroups'] = [];

    expect(Permissions::documentIsAccessible(30))->toBeTrue();
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('the default parent is the topmost document the user can reach', function () {
    bootPermissionsFixture();

    // the private folder sorts first but is skipped for a user outside its group
    $_SESSION['mgrDocgroups'] = [];
    expect(PermissionsDocumentAccessProbe::firstAccessible())->toBe(10);

    // a member of group 5 gets that private folder, it sorts before the public one
    $_SESSION['mgrDocgroups'] = [5];
    expect(PermissionsDocumentAccessProbe::firstAccessible())->toBe(20);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('trashed documents are never offered as a default parent', function () {
    bootPermissionsFixture();
    $_SESSION['mgrDocgroups'] = [];
    Capsule::table('site_content')->whereIn('id', [10, 11])->update(['deleted' => 1]);

    // 30 is trashed as well, so nothing public is left
    expect(PermissionsDocumentAccessProbe::firstAccessible())->toBeNull();
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');

test('a document in several groups is not returned twice by the join', function () {
    bootPermissionsFixture();
    Capsule::table('document_groups')->insert([
        ['document_group' => 9, 'document' => 20],
    ]);
    $_SESSION['mgrDocgroups'] = [5, 9];

    expect(Permissions::documentIsAccessible(20))->toBeTrue()
        ->and(PermissionsDocumentAccessProbe::firstAccessible())->toBe(20);
})->skip(!extension_loaded('pdo_sqlite'), 'pdo_sqlite is required');
