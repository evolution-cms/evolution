<?php

use EvolutionCMS\Controllers\UserRoles\UserRole as UserRoleController;
use EvolutionCMS\Support\RoleGuard;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Tests\Mocks\CacheRefreshHarness;

/*
|--------------------------------------------------------------------------
| Role reach on a site installed with the earlier default seeders
|--------------------------------------------------------------------------
|
| A non-administrator may edit, delete or assign only a role whose grants it holds itself. The
| actor's side lists defined permissions, so a grant no permission defines (role_actionok, which
| the earlier default seeders gave every role and no update removes) could never be matched, and
| a role manager holding every real permission of a default role was refused it. The same held
| for an always-on permission that a later save stored on the target but not on the actor's role.
| The comparison now takes the target's grants of defined, optional permissions only
| (RoleGuard::reachOf()); every real permission still counts.
|
*/

class RoleReachCoreStub
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

class RoleReachThemeStub
{
    public ?string $quitMessage = null;

    public function __construct(public RoleReachCoreStub $core)
    {
    }

    public function getCore(): RoleReachCoreStub
    {
        return $this->core;
    }

    public function alertAndQuit($message, $lexicon = true, $url = ''): void
    {
        $this->quitMessage = (string) $message;
        throw new RuntimeException('alertAndQuit:' . $message);
    }
}

/** A role manager (role 2) holding every real permission of the default Editor role 3, and no more. */
function roleReachController(array $permissions = ['save_role', 'delete_role', 'edit_document', 'save_document']): UserRoleController
{
    $_SESSION['mgrRole'] = 2;
    $controller = (new ReflectionClass(UserRoleController::class))->newInstanceWithoutConstructor();
    $property = new ReflectionProperty(UserRoleController::class, 'managerTheme');
    $property->setAccessible(true);
    $property->setValue($controller, new RoleReachThemeStub(new RoleReachCoreStub($permissions)));

    return $controller;
}

/** guardRoleReach(), which role save (a=35/36) runs before anything else */
function roleReachMaySave(UserRoleController $controller, int $role): bool
{
    try {
        (new ReflectionMethod(UserRoleController::class, 'guardRoleReach'))->invoke($controller, $role);

        return true;
    } catch (RuntimeException $e) {
        return false;
    }
}

function roleReachDelete(UserRoleController $controller, int $role): bool
{
    $_GET['action'] = $_REQUEST['action'] = 'delete';
    $_GET['id'] = $_REQUEST['id'] = $role;
    try {
        // header() after the delete only warns in the CLI
        @$controller->process();
    } catch (RuntimeException $e) {
    }

    return !Capsule::table('user_roles')->where('id', $role)->exists();
}

/** The check EditOrNewUser makes before it assigns $role to a user. */
function roleReachMayAssign(array $permissions, int $role): bool
{
    return RoleGuard::mayManageRole(2, $permissions, $role, RoleGuard::reachOf($role));
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
    $schema->create('permissions', function (Blueprint $t) {
        $t->increments('id');
        $t->string('key')->default('');
        $t->unsignedTinyInteger('disabled')->default(0);
    });
    foreach (['user_attributes' => 'role', 'site_module_roles' => 'role', 'user_role_vars' => 'roleid'] as $table => $column) {
        $schema->create($table, function (Blueprint $t) use ($column) {
            $t->increments('id');
            $t->unsignedInteger($column)->default(0);
        });
    }

    // as an upgraded site has them: action_ok is still defined (always-on), role_actionok never was
    foreach (['save_role' => 0, 'delete_role' => 0, 'edit_document' => 0, 'save_document' => 0, 'settings' => 0,
                 'action_ok' => 1, 'error_dialog' => 1] as $key => $disabled) {
        $this->capsule->table('permissions')->insert(['key' => $key, 'disabled' => $disabled]);
    }
    $this->capsule->table('user_roles')->insert(['id' => 3, 'name' => 'Editor', 'description' => '']);
    foreach (['edit_document', 'save_document', 'role_actionok', 'action_ok', 'error_dialog'] as $key) {
        $this->capsule->table('role_permissions')->insert(['role_id' => 3, 'permission' => $key]);
    }
});

afterEach(function () {
    Capsule::connection()->disconnect();
    foreach (['action', 'id'] as $k) {
        unset($_GET[$k], $_REQUEST[$k]);
    }
    unset($_SESSION['mgrRole']);
});

it('counts only the grants of defined, optional permissions', function () {
    expect(RoleGuard::reachOf(3))->toEqualCanonicalizing(['edit_document', 'save_document'])
        ->and(RoleGuard::reachOf(99))->toBe([]);
});

it('lets a role manager with every real permission of a legacy default role save, assign and delete it', function () {
    $permissions = ['save_role', 'delete_role', 'edit_document', 'save_document'];

    expect(roleReachMaySave(roleReachController($permissions), 3))->toBeTrue()
        ->and(roleReachMayAssign($permissions, 3))->toBeTrue()
        ->and(roleReachDelete(roleReachController($permissions), 3))->toBeTrue();
});

it('still refuses the legacy role when the actor lacks one of its real permissions', function (array $permissions, ?string $extraGrant) {
    if ($extraGrant !== null) {
        $this->capsule->table('role_permissions')->insert(['role_id' => 3, 'permission' => $extraGrant]);
    }

    expect(roleReachMaySave(roleReachController($permissions), 3))->toBeFalse()
        ->and(roleReachMayAssign($permissions, 3))->toBeFalse()
        ->and(roleReachDelete(roleReachController($permissions), 3))->toBeFalse()
        ->and($this->capsule->table('role_permissions')->where('role_id', 3)->count())->toBeGreaterThan(0);
})->with([
    'a permission of the role'          => [['save_role', 'delete_role', 'edit_document'], null],
    'a stronger permission on the role' => [['save_role', 'delete_role', 'edit_document', 'save_document'], 'settings'],
]);

it('keeps the administrator role out of reach', function () {
    $permissions = ['save_role', 'delete_role', 'edit_document', 'save_document', 'settings'];

    expect(roleReachMayAssign($permissions, RoleGuard::ADMIN_ROLE))->toBeFalse()
        ->and(roleReachMaySave(roleReachController($permissions), RoleGuard::ADMIN_ROLE))->toBeFalse();
});

test('both reach checks read the target grants through reachOf()', function () {
    $src = dirname(__DIR__, 3) . '/src/Controllers/';

    expect((string) file_get_contents($src . 'UserRoles/UserRole.php'))->toContain('$targetPermissions = RoleGuard::reachOf($id);')
        ->and((string) file_get_contents($src . 'Users/EditOrNewUser.php'))
        ->toContain('$newRolePermissions = \EvolutionCMS\Support\RoleGuard::reachOf($newRole);');
});
