<?php

use EvolutionCMS\Controllers\UserRoles\UserRole as UserRoleController;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Tests\Mocks\CacheRefreshHarness;

/*
|--------------------------------------------------------------------------
| Deleting a role that is still assigned to users
|--------------------------------------------------------------------------
|
| The role editor's delete action (a=35/36/38, action=delete, UserRoles\UserRole)
| removed the role and its permission rows outright. It did not reassign the users
| who held the role, so they were left pointing at a role id that no longer exists -
| a manager account with no resolvable permissions. The delete now refuses while any
| user still holds the role. (The legacy delete_role.processor.php already refused;
| the controller path, which the role UI actually uses, did not.)
|
*/

/** Minimal stand-in manager theme: alertAndQuit aborts, as the real one does. */
class RoleInUseThemeStub
{
    public ?string $quitMessage = null;

    public function alertAndQuit($message, $lexicon = true, $url = ''): void
    {
        $this->quitMessage = (string) $message;
        throw new RuntimeException('alertAndQuit:' . $message);
    }
}

function roleInUseController(RoleInUseThemeStub $theme): UserRoleController
{
    // The delete branch only needs $_REQUEST (getElementId) and $this->managerTheme,
    // so the heavy controller constructor can be bypassed.
    $controller = (new ReflectionClass(UserRoleController::class))->newInstanceWithoutConstructor();
    $property = new ReflectionProperty(UserRoleController::class, 'managerTheme');
    $property->setAccessible(true);
    $property->setValue($controller, $theme);

    return $controller;
}

beforeEach(function () {
    $this->capsule = CacheRefreshHarness::boot(':memory:');
    $schema = $this->capsule->getConnection()->getSchemaBuilder();
    $schema->create('user_roles', function (Blueprint $t) {
        $t->increments('id');
        $t->string('name')->default('');
        $t->text('description')->nullable();
    });
    $schema->create('role_permissions', function (Blueprint $t) {
        $t->increments('id');
        $t->unsignedInteger('role_id')->default(0);
        $t->string('permission')->default('');
        $t->timestamps();
    });
    $schema->create('user_attributes', function (Blueprint $t) {
        $t->increments('id');
        $t->unsignedInteger('internalKey')->default(0);
        $t->unsignedInteger('role')->default(0);
    });

    $this->capsule->table('user_roles')->insert(['id' => 5, 'name' => 'Content team', 'description' => '']);
    $this->capsule->table('role_permissions')->insert(['role_id' => 5, 'permission' => 'edit_document']);
});

afterEach(function () {
    Capsule::connection()->disconnect();
    foreach (['action', 'id'] as $k) {
        unset($_GET[$k], $_REQUEST[$k]);
    }
});

it('refuses to delete a role while a user still holds it, and keeps the role and its permissions', function () {
    // one manager account assigned to role 5
    $this->capsule->table('user_attributes')->insert(['internalKey' => 1, 'role' => 5]);

    $_GET['action'] = $_REQUEST['action'] = 'delete';
    $_GET['id'] = $_REQUEST['id'] = 5;

    $theme = new RoleInUseThemeStub();
    $controller = roleInUseController($theme);

    expect(fn () => $controller->process())->toThrow(RuntimeException::class, 'alertAndQuit:role_in_use_message');

    // the refusal uses the lexicon key, and nothing was deleted
    expect($theme->quitMessage)->toBe('role_in_use_message')
        ->and($this->capsule->table('user_roles')->where('id', 5)->exists())->toBeTrue()
        ->and($this->capsule->table('role_permissions')->where('role_id', 5)->exists())->toBeTrue();
});

it('allows deleting a role that no user holds', function () {
    // role 5 exists but nobody is assigned to it
    $_GET['action'] = $_REQUEST['action'] = 'delete';
    $_GET['id'] = $_REQUEST['id'] = 5;

    $theme = new RoleInUseThemeStub();
    $controller = roleInUseController($theme);

    // site_module_roles / user_role_vars are touched on the success path too
    $schema = $this->capsule->getConnection()->getSchemaBuilder();
    $schema->create('site_module_roles', function (Blueprint $t) {
        $t->increments('id');
        $t->unsignedInteger('role')->default(0);
    });
    $schema->create('user_role_vars', function (Blueprint $t) {
        $t->increments('id');
        $t->unsignedInteger('roleid')->default(0);
    });

    @$controller->process();

    expect($theme->quitMessage)->toBeNull()
        ->and($this->capsule->table('user_roles')->where('id', 5)->exists())->toBeFalse()
        ->and($this->capsule->table('role_permissions')->where('role_id', 5)->exists())->toBeFalse();
});

test('the delete branch refuses an in-use role before any delete, via the lexicon', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Controllers/UserRoles/UserRole.php');

    $delete = strpos($source, "\$_GET['action'] == 'delete'");
    $check = strpos($source, "Models\\UserAttribute::where('role', \$id)->exists()", $delete);
    $quit = strpos($source, "alertAndQuit('role_in_use_message')", $delete);
    $roleDelete = strpos($source, "Models\\UserRole::query()->where('id', \$id)->delete()", $delete);

    expect($delete)->not->toBeFalse()
        ->and($check)->not->toBeFalse()
        ->and($quit)->not->toBeFalse()
        ->and($roleDelete)->not->toBeFalse()
        // the in-use refusal sits before the role is removed
        ->and($check)->toBeLessThan($roleDelete)
        ->and($quit)->toBeLessThan($roleDelete);
});

test('the refusal message is defined in the English lexicon', function () {
    $en = (string) file_get_contents(dirname(__DIR__, 3) . '/lang/en/global.php');

    expect($en)->toContain('$_lang["role_in_use_message"]');
});
