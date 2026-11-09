<?php

/*
|--------------------------------------------------------------------------
| The default Publisher preset against a real database
|--------------------------------------------------------------------------
|
| Unit/Install/PublisherDefaultPresetTest pins the seeder source. This file runs
| the real core baseline-repair seeder (Database\Seeders\UserRolesTableSeeder, the
| one the 2026_04_12 migration invokes on every core migrate) against SQLite, to
| verify the behaviour directly:
|   - a fresh install grants Publisher neither settings nor bk_manager;
|   - a repeated run is idempotent (no duplicate rows, preset unchanged);
|   - an existing site keeps its settings / bk_manager / custom grants - the
|     seeder only inserts, it never revokes - while the role description is upserted.
*/

use Database\Seeders\UserRolesTableSeeder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;

afterEach(function () {
    Facade::clearResolvedInstances();
});

/** A SQLite database with the DB/Schema facades wired, so the real seeder can run. */
function bootPublisherPresetDatabase(): Capsule
{
    $capsule = new Capsule();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    $container = $capsule->getContainer();
    $container->instance('db', $capsule->getDatabaseManager());
    $container->bind('db.schema', fn () => $capsule->getConnection()->getSchemaBuilder());
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($container);
    Model::setConnectionResolver($capsule->getDatabaseManager());

    $schema = $capsule->getConnection()->getSchemaBuilder();
    $schema->create('user_roles', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name')->nullable();
        $table->text('description')->nullable();
    });
    $schema->create('role_permissions', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('role_id')->default(0);
        $table->string('permission')->default('');
        $table->timestamps();
    });

    return $capsule;
}

/** @return string[] the permission keys granted to a role */
function presetGrantsFor(Capsule $capsule, int $roleId): array
{
    return $capsule->getConnection()->table('role_permissions')
        ->where('role_id', $roleId)->pluck('permission')->all();
}

it('grants a fresh install Publisher neither settings nor bk_manager, and the administrator both', function () {
    $capsule = bootPublisherPresetDatabase();

    (new UserRolesTableSeeder())->run();

    $publisher = presetGrantsFor($capsule, 3);
    $administrator = presetGrantsFor($capsule, 1);

    expect($publisher)->not->toContain('settings')
        ->and($publisher)->not->toContain('bk_manager')
        // still a working content role
        ->and($publisher)->toContain('save_document')
        ->and($publisher)->toContain('delete_chunk')
        // the administrator keeps both
        ->and($administrator)->toContain('settings')
        ->and($administrator)->toContain('bk_manager');
});

it('is idempotent: a repeated run adds no duplicate rows and keeps the preset', function () {
    $capsule = bootPublisherPresetDatabase();
    $db = $capsule->getConnection();

    (new UserRolesTableSeeder())->run();
    $afterFirst = $db->table('role_permissions')->count();

    (new UserRolesTableSeeder())->run();

    expect($db->table('role_permissions')->count())->toBe($afterFirst)
        // no (role_id, permission) pair is duplicated
        ->and(
            $db->table('role_permissions')
                ->selectRaw('role_id, permission, COUNT(*) as c')
                ->groupBy('role_id', 'permission')
                ->havingRaw('COUNT(*) > 1')->count()
        )->toBe(0)
        ->and(presetGrantsFor($capsule, 3))->not->toContain('settings')
        ->and(presetGrantsFor($capsule, 3))->not->toContain('bk_manager');
});

it('preserves settings, bk_manager and custom grants on an existing site, while upserting the description', function () {
    $capsule = bootPublisherPresetDatabase();
    $db = $capsule->getConnection();

    // an existing site: Publisher already holds settings + bk_manager and a custom grant,
    // under an operator-renamed role
    $db->table('user_roles')->insert(['id' => 3, 'name' => 'Senior Publisher', 'description' => 'our own wording']);
    $db->table('role_permissions')->insert([
        ['role_id' => 3, 'permission' => 'settings', 'created_at' => null, 'updated_at' => null],
        ['role_id' => 3, 'permission' => 'bk_manager', 'created_at' => null, 'updated_at' => null],
        ['role_id' => 3, 'permission' => 'my_custom_perm', 'created_at' => null, 'updated_at' => null],
    ]);

    (new UserRolesTableSeeder())->run();

    $publisher = presetGrantsFor($capsule, 3);
    // grants are preserved - the seeder never revokes
    expect($publisher)->toContain('settings')
        ->and($publisher)->toContain('bk_manager')
        ->and($publisher)->toContain('my_custom_perm');

    // the description (and name) are upserted, so they do change on an existing site
    $role = $db->table('user_roles')->where('id', 3)->first();
    expect($role->name)->toBe('Publisher')
        ->and($role->description)->toBe('Editor with expanded permissions including manage users and update Elements');
});
