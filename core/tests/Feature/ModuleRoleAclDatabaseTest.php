<?php

/*
|--------------------------------------------------------------------------
| Per-role module ACL against a real database
|--------------------------------------------------------------------------
|
| The rules themselves are unit tested in ModuleRoleAccessTest; what this file
| covers is the part that only a database can answer: the SQL the scope builds,
| and the migration that restricts an already installed Extras module.
*/

use EvolutionCMS\Models\SiteModule;
use EvolutionCMS\Models\SiteModuleRole;
use EvolutionCMS\Support\ModuleAccess;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;

// Both this file and the update e2e file boot their own capsule and point the
// facades at it; drop the resolved instances afterwards so whichever runs next
// resolves against its own container instead of this one.
afterEach(function () {
    Facade::clearResolvedInstances();
});

/**
 * A standalone SQLite database with the facades wired, so the real migration
 * and the real Eloquent scope can run inside the bare PHPUnit harness.
 */
function bootModuleRoleAclDatabase(): Capsule
{
    $capsule = new Capsule();
    $capsule->addConnection([
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);
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
    });
    $schema->create('site_modules', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name')->nullable();
        $table->integer('disabled')->default(0);
        $table->integer('locked')->default(0);
        $table->integer('category')->default(0);
    });

    $db = $capsule->getConnection();
    $db->table('user_roles')->insert([
        ['id' => 1, 'name' => 'Administrator'],
        ['id' => 2, 'name' => 'Editor'],
        ['id' => 3, 'name' => 'Publisher'],
    ]);
    $db->table('site_modules')->insert([
        ['id' => 1, 'name' => 'Extras'],
        ['id' => 2, 'name' => 'Reports'],
    ]);

    return $capsule;
}

/**
 * @return string[] Names of the modules the role is allowed to run.
 */
function moduleNamesForRole(int $roleId): array
{
    return SiteModule::query()
        ->allowedForRole($roleId)
        ->orderBy('name')
        ->pluck('name')
        ->all();
}

it('restricts an already installed Extras module to administrators when the table is created', function () {
    $capsule = bootModuleRoleAclDatabase();

    (new \CreateSiteModuleRolesTable())->up();

    $rows = $capsule->getConnection()->table('site_module_roles')->get()->all();

    expect($rows)->toHaveCount(1)
        ->and((int) $rows[0]->module)->toBe(1)
        ->and((int) $rows[0]->role)->toBe(ModuleAccess::ADMIN_ROLE);
});

it('hides a restricted module from the roles it is not granted to', function () {
    bootModuleRoleAclDatabase();
    (new \CreateSiteModuleRolesTable())->up();

    // Editor and Publisher lose Extras but keep every unrestricted module
    expect(moduleNamesForRole(2))->toBe(['Reports'])
        ->and(moduleNamesForRole(3))->toBe(['Reports'])
        ->and(moduleNamesForRole(ModuleAccess::ADMIN_ROLE))->toBe(['Extras', 'Reports']);
});

it('grants a restricted module to a role that is listed on it', function () {
    bootModuleRoleAclDatabase();
    (new \CreateSiteModuleRolesTable())->up();

    SiteModuleRole::query()->create(['module' => 1, 'role' => 3]);

    expect(moduleNamesForRole(3))->toBe(['Extras', 'Reports'])
        ->and(moduleNamesForRole(2))->toBe(['Reports']);
});

it('keeps a module with no rows available to every role', function () {
    bootModuleRoleAclDatabase();
    (new \CreateSiteModuleRolesTable())->up();

    SiteModuleRole::query()->where('module', 1)->delete();

    expect(moduleNamesForRole(2))->toBe(['Extras', 'Reports']);
});

it('counts a module as restricted even when only other roles are listed', function () {
    bootModuleRoleAclDatabase();
    (new \CreateSiteModuleRolesTable())->up();

    // Reports denied to the Editor by listing everybody else
    SiteModuleRole::query()->create(['module' => 2, 'role' => 1]);
    SiteModuleRole::query()->create(['module' => 2, 'role' => 3]);

    expect(moduleNamesForRole(2))->toBe([])
        ->and(moduleNamesForRole(3))->toBe(['Reports'])
        ->and(moduleNamesForRole(ModuleAccess::ADMIN_ROLE))->toBe(['Extras', 'Reports']);
});

it('applies the shipped default when an install path creates a bundled module', function () {
    $capsule = bootModuleRoleAclDatabase();
    (new \CreateSiteModuleRolesTable())->up();
    $capsule->getConnection()->table('site_module_roles')->delete();

    SiteModuleRole::applyDefaultsFor(1, 'Extras');
    SiteModuleRole::applyDefaultsFor(2, 'Reports');

    expect(SiteModuleRole::query()->where('module', 1)->pluck('role')->all())->toBe([1])
        ->and(SiteModuleRole::query()->where('module', 2)->count())->toBe(0);
});

it('leaves a bundled module alone once an administrator has changed its restriction', function () {
    bootModuleRoleAclDatabase();
    (new \CreateSiteModuleRolesTable())->up();

    // an administrator opens Extras up to the Publisher role, then a later
    // update runs the same default again
    SiteModuleRole::query()->create(['module' => 1, 'role' => 3]);
    SiteModuleRole::applyDefaultsFor(1, 'Extras');
    (new \CreateSiteModuleRolesTable())->up();

    expect(SiteModuleRole::query()->where('module', 1)->orderBy('role')->pluck('role')->all())->toBe([1, 3]);
});

it('skips a default for a role that does not exist on the site', function () {
    $capsule = bootModuleRoleAclDatabase();
    (new \CreateSiteModuleRolesTable())->up();
    $capsule->getConnection()->table('site_module_roles')->delete();
    $capsule->getConnection()->table('user_roles')->where('id', 1)->delete();

    SiteModuleRole::applyDefaultsFor(1, 'Extras');

    expect(SiteModuleRole::query()->count())->toBe(0);
});
