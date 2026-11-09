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

/** The acting manager: hasPermission() answers from its own keys, as Core does (1 or 0). */
class RoleInUseCoreStub
{
    /** @param string[] $permissions */
    public function __construct(public array $permissions)
    {
    }

    public function hasPermission($key): int
    {
        return in_array($key, $this->permissions, true) ? 1 : 0;
    }
}

/** Minimal stand-in manager theme: alertAndQuit aborts, as the real one does. */
class RoleInUseThemeStub
{
    public ?string $quitMessage = null;

    public function __construct(public RoleInUseCoreStub $core)
    {
    }

    public function getCore(): RoleInUseCoreStub
    {
        return $this->core;
    }

    public function alertAndQuit($message, $lexicon = true, $url = ''): void
    {
        $this->quitMessage = (string) $message;
        throw new RuntimeException('alertAndQuit:' . $message);
    }
}

/**
 * A non-admin manager (role 2) by default, holding delete_role and every permission of role 5,
 * so the delete_role check and the reach guard pass and only the in-use rule decides.
 *
 * @param string[] $permissions
 */
function roleInUseTheme(array $permissions = ['delete_role', 'edit_document'], int $actorRole = 2): RoleInUseThemeStub
{
    $_SESSION['mgrRole'] = $actorRole;

    return new RoleInUseThemeStub(new RoleInUseCoreStub($permissions));
}

function roleInUseController(RoleInUseThemeStub $theme): UserRoleController
{
    // The delete branch only needs $_REQUEST (getElementId), $_SESSION['mgrRole'] and
    // $this->managerTheme, so the heavy controller constructor can be bypassed.
    $controller = (new ReflectionClass(UserRoleController::class))->newInstanceWithoutConstructor();
    $property = new ReflectionProperty(UserRoleController::class, 'managerTheme');
    $property->setAccessible(true);
    $property->setValue($controller, $theme);

    return $controller;
}

function roleInUseRequestDelete(int $id): void
{
    $_GET['action'] = $_REQUEST['action'] = 'delete';
    $_GET['id'] = $_REQUEST['id'] = $id;
}

function roleFiveSurvives($capsule): bool
{
    return $capsule->table('user_roles')->where('id', 5)->exists()
        && $capsule->table('role_permissions')->where('role_id', 5)->exists();
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
    // the permission keys the reach guard checks the actor against
    $schema->create('permissions', function (Blueprint $t) {
        $t->increments('id');
        $t->string('key')->default('');
        $t->unsignedTinyInteger('disabled')->default(0);
    });
    // touched on the success path
    $schema->create('site_module_roles', function (Blueprint $t) {
        $t->increments('id');
        $t->unsignedInteger('role')->default(0);
    });
    $schema->create('user_role_vars', function (Blueprint $t) {
        $t->increments('id');
        $t->unsignedInteger('roleid')->default(0);
    });
    foreach (['delete_role', 'edit_document', 'settings'] as $key) {
        $this->capsule->table('permissions')->insert(['key' => $key]);
    }

    $this->capsule->table('user_roles')->insert(['id' => 5, 'name' => 'Content team', 'description' => '']);
    $this->capsule->table('role_permissions')->insert(['role_id' => 5, 'permission' => 'edit_document']);
});

afterEach(function () {
    Capsule::connection()->disconnect();
    foreach (['action', 'id'] as $k) {
        unset($_GET[$k], $_REQUEST[$k]);
    }
    unset($_SESSION['mgrRole']);
});

it('refuses to delete a role while a user still holds it, and keeps the role and its permissions', function () {
    // one manager account assigned to role 5
    $this->capsule->table('user_attributes')->insert(['internalKey' => 1, 'role' => 5]);
    roleInUseRequestDelete(5);

    $theme = roleInUseTheme();
    $controller = roleInUseController($theme);

    expect(fn () => $controller->process())->toThrow(RuntimeException::class, 'alertAndQuit:role_in_use_message');

    // the refusal uses the lexicon key, and nothing was deleted
    expect($theme->quitMessage)->toBe('role_in_use_message')
        ->and(roleFiveSurvives($this->capsule))->toBeTrue();
});

it('allows deleting a role that no user holds', function () {
    // role 5 exists but nobody is assigned to it
    roleInUseRequestDelete(5);

    $theme = roleInUseTheme();
    $controller = roleInUseController($theme);

    // header() after the delete only warns in the CLI
    @$controller->process();

    expect($theme->quitMessage)->toBeNull()
        ->and($this->capsule->table('user_roles')->where('id', 5)->exists())->toBeFalse()
        ->and($this->capsule->table('role_permissions')->where('role_id', 5)->exists())->toBeFalse();
});

it('refuses the delete without delete_role before looking at who holds the role', function () {
    $this->capsule->table('user_attributes')->insert(['internalKey' => 1, 'role' => 5]);
    roleInUseRequestDelete(5);

    $theme = roleInUseTheme(['edit_document']);

    expect(fn () => roleInUseController($theme)->process())->toThrow(RuntimeException::class, 'alertAndQuit:error_no_privileges');
    expect(roleFiveSurvives($this->capsule))->toBeTrue();
});

it('refuses to delete an unused role beyond the actor reach', function () {
    // role 5 carries a permission the actor does not hold
    $this->capsule->table('role_permissions')->insert(['role_id' => 5, 'permission' => 'settings']);
    roleInUseRequestDelete(5);

    $theme = roleInUseTheme();

    expect(fn () => roleInUseController($theme)->process())->toThrow(RuntimeException::class, 'alertAndQuit:error_no_privileges');
    expect(roleFiveSurvives($this->capsule))->toBeTrue();
});

test('the delete branch refuses an in-use role before any delete, via the lexicon', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Controllers/UserRoles/UserRole.php');

    $delete = strpos($source, "\$_GET['action'] == 'delete'");
    $check = strpos($source, "Models\\UserAttribute::where('role', \$id)->exists()", $delete);
    $quit = strpos($source, "alertAndQuit('role_in_use_message')", $delete);
    $roleDelete = strpos($source, "Models\\UserRole::query()->where('id', \$id)->delete()", $delete);
    $permission = strpos($source, "hasPermission('delete_role')", $delete);
    $reach = strpos($source, '$this->guardRoleReach($id);', $delete);
    $firstDelete = strpos($source, '->delete();', $delete);

    expect($delete)->not->toBeFalse()
        ->and($check)->not->toBeFalse()
        ->and($quit)->not->toBeFalse()
        ->and($roleDelete)->not->toBeFalse()
        // the in-use refusal sits before the role is removed
        ->and($check)->toBeLessThan($roleDelete)
        ->and($quit)->toBeLessThan($roleDelete)
        // delete_role and the reach guard come first, and every check precedes every delete
        ->and($permission)->not->toBeFalse()
        ->and($reach)->not->toBeFalse()
        ->and($permission)->toBeLessThan($reach)
        ->and($reach)->toBeLessThan($check)
        ->and($quit)->toBeLessThan($firstDelete);
});

test('the refusal message is defined in the English lexicon', function () {
    $en = (string) file_get_contents(dirname(__DIR__, 3) . '/lang/en/global.php');

    expect($en)->toContain('$_lang["role_in_use_message"]');
});
