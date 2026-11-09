<?php

use EvolutionCMS\Controllers\UserRoles\UserRole as UserRoleController;
use EvolutionCMS\Support\RoleGuard;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Tests\Mocks\CacheRefreshHarness;

/*
|--------------------------------------------------------------------------
| Role reach follows the grants that authorize at runtime
|--------------------------------------------------------------------------
|
| A non-administrator may save, delete or assign only a role whose grants it holds itself. At
| login the session receives every stored grant of the role, defined by a permissions row or not
| (a plugin's own key), so both sides are compared by those runtime grants: the actor's through
| its own hasPermission(), the target's as stored. Two kinds of grant set no reach:
| - the obsolete keys the earlier default seeders left on every role (role_actionok, action_ok),
|   which nothing checks; counting them locked default roles away from role managers holding
|   every real permission of them;
| - always-on permissions, which a role save stores on every role whoever saves it.
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
    public function __construct(public RoleReachCoreStub $core)
    {
    }

    public function getCore(): RoleReachCoreStub
    {
        return $this->core;
    }

    public function alertAndQuit($message, $lexicon = true, $url = ''): void
    {
        throw new RuntimeException('alertAndQuit:' . $message);
    }
}

/** The role manager (role 2): its role holds $grants, which is what its session answers. */
function roleReachActor(array $grants): RoleReachCoreStub
{
    $_SESSION['mgrRole'] = 2;
    Capsule::table('role_permissions')->where('role_id', 2)->delete();
    foreach ($grants as $key) {
        Capsule::table('role_permissions')->insert(['role_id' => 2, 'permission' => $key]);
    }

    return new RoleReachCoreStub($grants);
}

function roleReachController(RoleReachCoreStub $actor): UserRoleController
{
    $controller = (new ReflectionClass(UserRoleController::class))->newInstanceWithoutConstructor();
    $property = new ReflectionProperty(UserRoleController::class, 'managerTheme');
    $property->setAccessible(true);
    $property->setValue($controller, new RoleReachThemeStub($actor));

    return $controller;
}

/** guardRoleReach(), which role save (a=35/36) runs before anything else */
function roleReachMaySave(RoleReachCoreStub $actor, int $role): bool
{
    try {
        (new ReflectionMethod(UserRoleController::class, 'guardRoleReach'))->invoke(roleReachController($actor), $role);

        return true;
    } catch (RuntimeException $e) {
        return false;
    }
}

/** The check EditOrNewUser makes before it assigns $role to a user. */
function roleReachMayAssign(RoleReachCoreStub $actor, int $role): bool
{
    return RoleGuard::mayManageRole(2, RoleGuard::heldBy(fn ($key) => $actor->hasPermission($key)), $role, RoleGuard::reachOf($role));
}

/** The real delete branch; true when the role is gone. */
function roleReachDelete(RoleReachCoreStub $actor, int $role): bool
{
    $_GET['action'] = $_REQUEST['action'] = 'delete';
    $_GET['id'] = $_REQUEST['id'] = $role;
    try {
        // header() after the delete only warns in the CLI
        @roleReachController($actor)->process();
    } catch (RuntimeException $e) {
    }

    return !Capsule::table('user_roles')->where('id', $role)->exists();
}

/** @return array{save: bool, assign: bool, delete: bool} delete last: it removes the role */
function roleReachOutcome(array $actorGrants, int $role = 3): array
{
    $actor = roleReachActor($actorGrants);

    return [
        'save' => roleReachMaySave($actor, $role),
        'assign' => roleReachMayAssign($actor, $role),
        'delete' => roleReachDelete($actor, $role),
    ];
}

const ROLE_REACH_ALL = ['save' => true, 'assign' => true, 'delete' => true];
const ROLE_REACH_NONE = ['save' => false, 'assign' => false, 'delete' => false];
const ROLE_REACH_MANAGER = ['save_role', 'delete_role', 'frames', 'home'];

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

    // a fresh install: core permissions, frames/home/error_dialog always-on, a plugin's defined key
    foreach (['save_role' => 0, 'delete_role' => 0, 'edit_document' => 0, 'save_document' => 0, 'settings' => 0,
                 'shop_orders' => 0, 'frames' => 1, 'home' => 1, 'error_dialog' => 1] as $key => $disabled) {
        $this->capsule->table('permissions')->insert(['key' => $key, 'disabled' => $disabled]);
    }
    $this->capsule->table('user_roles')->insert(['id' => 3, 'name' => 'Editor', 'description' => '']);
    $this->grant = function (array $keys, int $role = 3) {
        foreach ($keys as $key) {
            $this->capsule->table('role_permissions')->insert(['role_id' => $role, 'permission' => $key]);
        }
    };
    ($this->grant)(['edit_document', 'save_document', 'frames', 'home', 'error_dialog']);
    // what an install with the earlier default seeders still has
    $this->upgrade = function () {
        $this->capsule->table('permissions')->insert(['key' => 'action_ok', 'disabled' => 1]);
        ($this->grant)(['role_actionok', 'action_ok']);
    };
});

afterEach(function () {
    Capsule::connection()->disconnect();
    foreach (['action', 'id'] as $k) {
        unset($_GET[$k], $_REQUEST[$k]);
    }
    unset($_SESSION['mgrRole']);
});

it('lets a role manager holding every real permission of the default role manage it', function (bool $upgraded) {
    if ($upgraded) {
        ($this->upgrade)();
    }

    expect(roleReachOutcome([...ROLE_REACH_MANAGER, 'edit_document', 'save_document']))->toBe(ROLE_REACH_ALL);
})->with(['fresh install' => [false], 'upgraded install' => [true]]);

it('refuses the default role when the actor lacks one of its real permissions', function (bool $upgraded) {
    if ($upgraded) {
        ($this->upgrade)();
    }

    expect(roleReachOutcome([...ROLE_REACH_MANAGER, 'edit_document']))->toBe(ROLE_REACH_NONE)
        ->and($this->capsule->table('role_permissions')->where('role_id', 3)->count())->toBeGreaterThan(0);
})->with(['fresh install' => [false], 'upgraded install' => [true]]);

it('counts custom permissions, defined or only granted, as runtime authorizes them', function (string $custom, bool $actorHoldsIt, array $expected) {
    // shop_orders has a permissions row; shop_refunds is a plugin's key that only a grant carries
    ($this->upgrade)();
    ($this->grant)([$custom]);
    $actor = [...ROLE_REACH_MANAGER, 'edit_document', 'save_document', ...($actorHoldsIt ? [$custom] : [])];

    expect(roleReachOutcome($actor))->toBe($expected);
})->with([
    'defined, held by the actor'      => ['shop_orders', true, ROLE_REACH_ALL],
    'defined, not held by the actor'  => ['shop_orders', false, ROLE_REACH_NONE],
    'undefined, held by the actor'    => ['shop_refunds', true, ROLE_REACH_ALL],
    'undefined, not held by the actor' => ['shop_refunds', false, ROLE_REACH_NONE],
]);

it('refuses a role holding a stronger core permission', function () {
    ($this->grant)(['settings']);

    expect(roleReachOutcome([...ROLE_REACH_MANAGER, 'edit_document', 'save_document']))->toBe(ROLE_REACH_NONE);
});

it('ignores only the obsolete keys and the always-on permissions', function () {
    ($this->upgrade)();
    ($this->grant)(['shop_refunds']);

    expect(RoleGuard::grantsOf(3))->toContain('role_actionok', 'action_ok', 'frames', 'shop_refunds')
        ->and(RoleGuard::reachOf(3))->toEqualCanonicalizing(['edit_document', 'save_document', 'shop_refunds'])
        ->and(RoleGuard::reachOf(99))->toBe([]);
});

it('keeps the administrator role out of reach', function () {
    $actor = roleReachActor([...ROLE_REACH_MANAGER, 'edit_document', 'save_document', 'settings']);

    expect(roleReachMaySave($actor, RoleGuard::ADMIN_ROLE))->toBeFalse()
        ->and(roleReachMayAssign($actor, RoleGuard::ADMIN_ROLE))->toBeFalse();
});

test('role save, delete and assignment ask the same two helpers', function () {
    $src = dirname(__DIR__, 3) . '/src/Controllers/';
    $role = (string) file_get_contents($src . 'UserRoles/UserRole.php');
    $user = (string) file_get_contents($src . 'Users/EditOrNewUser.php');

    expect($role)->toContain('$targetPermissions = RoleGuard::reachOf($id);')
        ->and($role)->toContain('return RoleGuard::heldBy(static fn ($key) => $core->hasPermission($key));')
        ->and($user)->toContain('$newRolePermissions = \EvolutionCMS\Support\RoleGuard::reachOf($newRole);')
        ->and($user)->toContain('$actorPermissions = \EvolutionCMS\Support\RoleGuard::heldBy(');
});
