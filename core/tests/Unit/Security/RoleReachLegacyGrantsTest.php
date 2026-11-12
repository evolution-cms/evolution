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
| A non-administrator may save, delete or assign only a role whose grants it holds itself, and
| stores on a role only grants it holds. At login the session receives every stored grant of the
| role, defined by a permissions row or not (a plugin's own key), always-on ones included, so both
| sides are compared by those runtime grants: the actor's through its own hasPermission(), the
| target's as stored. Only the obsolete keys the earlier default seeders left on every role
| (role_actionok, action_ok), which nothing checks, set no reach; counting them locked default
| roles away from role managers holding every real permission of them.
|
| Each operation also needs its own permission: save_role to save or assign, delete_role to delete.
| The cases run the real role save (updateOrCreate) and delete, and RoleGuard::mayAssignRole(),
| which EditOrNewUser asks before it changes a user's role.
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

    public function getManagerApi(): object
    {
        return new class {
            public function saveFormValues(): void
            {
            }

            public function clearSavedFormValues(): void
            {
            }
        };
    }

    public function webAlertAndQuit($message, $url = ''): void
    {
        throw new RuntimeException('webAlertAndQuit:' . $message);
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

/** The acting manager (role 2): its role holds $grants, which is what its session answers. */
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
    $property->setValue($controller, new RoleReachThemeStub($actor));

    return $controller;
}

function roleReachRun(callable $request): bool
{
    try {
        // header() after a save or delete only warns in the CLI
        @$request();

        return true;
    } catch (RuntimeException $e) {
        return false;
    }
}

/** The role form posted for role 3, asking for $keys; true when the save went through. */
function roleReachSave(RoleReachCoreStub $actor, array $keys): bool
{
    $_GET = [];
    $_REQUEST = ['id' => 3];
    $_POST = ['a' => '35', 'id' => 3, 'name' => 'Editor (saved)', 'description' => '', 'tvsDirty' => '0',
        'permissions' => array_fill_keys($keys, 1)];
    $saved = roleReachRun(fn () => roleReachController($actor)->process());
    $_POST = [];

    return $saved && Capsule::table('user_roles')->where('id', 3)->value('name') === 'Editor (saved)';
}

function roleReachDelete(RoleReachCoreStub $actor): bool
{
    $_GET = ['action' => 'delete', 'id' => 3];
    $_REQUEST = $_GET;
    roleReachRun(fn () => roleReachController($actor)->process());
    $_GET = $_REQUEST = [];

    return !Capsule::table('user_roles')->where('id', 3)->exists();
}

/**
 * Assign, save (asking for the role's own grants), then delete role 3.
 *
 * @return array{assign: bool, save: bool, delete: bool}
 */
function roleReachOutcome(array $actorGrants): array
{
    $actor = roleReachActor($actorGrants);
    $roleKeys = Capsule::table('role_permissions')->where('role_id', 3)->pluck('permission')->all();

    return [
        'assign' => RoleGuard::mayAssignRole(2, fn ($key) => $actor->hasPermission($key), 3),
        'save' => roleReachSave($actor, $roleKeys),
        'delete' => roleReachDelete($actor),
    ];
}

/** every real grant of the default Editor role, always-on ones included */
const ROLE_REACH_EDITOR = ['edit_document', 'save_document', 'frames', 'home', 'error_dialog'];
const ROLE_REACH_ALL = ['assign' => true, 'save' => true, 'delete' => true];
const ROLE_REACH_NONE = ['assign' => false, 'save' => false, 'delete' => false];

beforeEach(function () {
    $this->capsule = CacheRefreshHarness::boot(':memory:');
    $schema = $this->capsule->getConnection()->getSchemaBuilder();
    $schema->create('user_roles', function (Blueprint $t) {
        $t->increments('id');
        $t->string('name')->default('');
        $t->text('description')->nullable();
        $t->timestamps();
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
    $this->grant = function (array $keys) {
        foreach ($keys as $key) {
            $this->capsule->table('role_permissions')->insert(['role_id' => 3, 'permission' => $key]);
        }
    };
    ($this->grant)(ROLE_REACH_EDITOR);
    // what an install with the earlier default seeders still has
    $this->upgrade = function () {
        $this->capsule->table('permissions')->insert(['key' => 'action_ok', 'disabled' => 1]);
        ($this->grant)(['role_actionok', 'action_ok']);
    };
});

afterEach(function () {
    Capsule::connection()->disconnect();
    $_GET = $_POST = $_REQUEST = [];
    unset($_SESSION['mgrRole']);
});

it('lets each operation through with its own permission, on a fresh and an upgraded install', function (bool $upgraded, array $rights, array $expected) {
    if ($upgraded) {
        ($this->upgrade)();
    }

    expect(roleReachOutcome([...$rights, ...ROLE_REACH_EDITOR]))->toBe($expected);
})->with([false, true])->with([
    'save_role and delete_role' => [['save_role', 'delete_role'], ROLE_REACH_ALL],
    'save_role only'            => [['save_role'], ['assign' => true, 'save' => true, 'delete' => false]],
    'delete_role only'          => [['delete_role'], ['assign' => false, 'save' => false, 'delete' => true]],
    'neither'                   => [[], ROLE_REACH_NONE],
]);

it('refuses every operation on a role whose runtime grants exceed the actor', function (array $rights, array $extra, array $actorExtra) {
    ($this->upgrade)();
    ($this->grant)($extra);

    expect(roleReachOutcome([...$rights, ...array_diff(ROLE_REACH_EDITOR, ['home']), ...$actorExtra]))->toBe(ROLE_REACH_NONE)
        ->and($this->capsule->table('user_roles')->where('id', 3)->value('name'))->toBe('Editor');
})->with([
    'save_role and delete_role' => [['save_role', 'delete_role']],
    'save_role only'            => [['save_role']],
    'delete_role only'          => [['delete_role']],
])->with([
    // the actor's role was saved before home became always-on
    'an always-on grant'           => [[], []],
    'a stronger core permission'   => [['settings'], ['home']],
    'a defined custom permission'  => [['shop_orders'], ['home']],
    'a custom grant with no row'   => [['shop_refunds'], ['home']],
]);

it('allows a role carrying custom grants the actor holds', function (string $custom) {
    ($this->upgrade)();
    ($this->grant)([$custom]);

    expect(roleReachOutcome(['save_role', 'delete_role', ...ROLE_REACH_EDITOR, $custom]))->toBe(ROLE_REACH_ALL);
})->with(['shop_orders', 'shop_refunds']);

it('stores on a saved role only grants the actor holds, and drops the obsolete ones', function () {
    ($this->upgrade)();
    $actor = roleReachActor(['save_role', 'edit_document', 'save_document', 'frames', 'home', 'error_dialog']);

    expect(roleReachSave($actor, ['edit_document', 'settings', 'shop_refunds']))->toBeTrue()
        ->and($this->capsule->table('role_permissions')->where('role_id', 3)->pluck('permission')->all())
        ->toEqualCanonicalizing(['edit_document', 'frames', 'home', 'error_dialog']);
});

it('keeps the administrator role out of reach', function () {
    $actor = roleReachActor(['save_role', 'delete_role', 'settings', ...ROLE_REACH_EDITOR]);

    expect(RoleGuard::mayAssignRole(2, fn ($key) => $actor->hasPermission($key), RoleGuard::ADMIN_ROLE))->toBeFalse();
});

it('leaves out of reachOf() only the obsolete keys', function () {
    ($this->upgrade)();
    ($this->grant)(['shop_refunds']);

    expect(RoleGuard::reachOf(3))->toEqualCanonicalizing([...ROLE_REACH_EDITOR, 'shop_refunds'])
        ->and(RoleGuard::reachOf(99))->toBe([]);
});

test('role save, delete and assignment ask the same helpers', function () {
    $src = dirname(__DIR__, 3) . '/src/Controllers/';
    $role = (string) file_get_contents($src . 'UserRoles/UserRole.php');
    $user = (string) file_get_contents($src . 'Users/EditOrNewUser.php');

    expect($role)->toContain('$targetPermissions = RoleGuard::reachOf($id);')
        ->and($role)->toContain('return RoleGuard::heldBy(static fn ($key) => $core->hasPermission($key));')
        ->and($user)->toContain('\EvolutionCMS\Support\RoleGuard::mayAssignRole($actorRole, static fn ($key) => EvolutionCMS()->hasPermission($key), $newRole)');
});
