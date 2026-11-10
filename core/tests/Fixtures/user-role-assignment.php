<?php

/**
 * Runs the real EditOrNewUser::process() (a=89) in a clean process for a manager of role 2, and
 * stores a role through the real UserSetRole service, against SQLite or MySQL/MariaDB in the
 * sql_mode the installer configures ('strict' => false, which coerces '0.5' to 1 in an INT column).
 *
 * argv: base64 JSON {permissions: string[], post: array, mysql?: {host, port, database, username, password}}
 * Prints JSON: {quit: alert message or null, roles: user id => stored role, checked: role ids whose
 *               grants were read for the reach check, tvRoles: role ids whose TVs were loaded}
 */

[, $payload] = $argv;
$options = json_decode(base64_decode($payload), true);

define('IN_MANAGER_MODE', true);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', true);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

final class RoleAssignmentStubEvo
{
    public function __construct(private array $permissions)
    {
    }

    public function hasPermission($key)
    {
        return in_array($key, $this->permissions, true) ? 1 : 0;
    }

    public function getManagerApi()
    {
        return new class {
            public function saveFormValues(...$args): void
            {
            }
        };
    }

    public function webAlertAndQuit($message, $url = '')
    {
        $GLOBALS['roleAssignmentQuit'] = $message;
        exit;
    }
}

$GLOBALS['roleAssignmentEvo'] = new RoleAssignmentStubEvo($options['permissions']);
function evo()
{
    return $GLOBALS['roleAssignmentEvo'];
}
function evolutionCMS()
{
    return evo();
}

final class Lang
{
    public static function get($key, array $replace = [])
    {
        return $key;
    }
}

final class DB
{
    public static function raw($value)
    {
        return Capsule::raw($value);
    }
}

/** The facade as EditOrNewUser calls it; setRole() is the real service. */
final class UserManager
{
    public static function create(array $data)
    {
        $user = EvolutionCMS\Models\User::query()->create(['username' => $data['username']]);
        $user->attributes()->create(['email' => $data['email'] ?? '']);

        return $user->refresh();
    }

    public static function edit(array $data)
    {
        return EvolutionCMS\Models\User::query()->findOrFail($data['id']);
    }

    public static function setRole(array $data)
    {
        return (new EvolutionCMS\UserManager\Services\Users\UserSetRole($data, false, false))->process();
    }

    public static function __callStatic($name, $arguments)
    {
    }
}

$capsule = new Capsule();
$mysql = $options['mysql'] ?? null;
$capsule->addConnection(is_array($mysql)
    ? ['driver' => 'mysql', 'host' => $mysql['host'], 'port' => $mysql['port'], 'database' => $mysql['database'],
        'username' => $mysql['username'], 'password' => $mysql['password'], 'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => false]
    : ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
$capsule->setEventDispatcher(new Illuminate\Events\Dispatcher());
$capsule->setAsGlobal();
$capsule->bootEloquent();
$db = $capsule->getConnection();
$schema = $db->getSchemaBuilder();
$tables = [
    'users' => function (Blueprint $t) {
        $t->increments('id');
        $t->string('username')->default('');
        $t->string('password')->default('');
        $t->string('cachepwd')->default('');
    },
    'user_attributes' => function (Blueprint $t) {
        $t->increments('id');
        $t->unsignedInteger('internalKey')->default(0);
        $t->string('email')->default('');
        $t->integer('role')->default(0);
        $t->unsignedTinyInteger('verified')->default(0);
        $t->unsignedInteger('createdon')->default(0);
        $t->unsignedInteger('editedon')->default(0);
    },
    'user_roles' => function (Blueprint $t) {
        $t->increments('id');
        $t->string('name')->default('');
    },
    'role_permissions' => function (Blueprint $t) {
        $t->increments('id');
        $t->unsignedInteger('role_id')->default(0);
        $t->string('permission')->default('');
    },
    'permissions' => function (Blueprint $t) {
        $t->increments('id');
        $t->string('key')->default('');
        $t->unsignedTinyInteger('disabled')->default(0);
    },
    'site_tmplvars' => function (Blueprint $t) {
        $t->increments('id');
        $t->string('name')->default('');
        $t->string('type')->default('text');
    },
    'user_role_vars' => function (Blueprint $t) {
        $t->unsignedInteger('tmplvarid')->default(0);
        $t->unsignedInteger('roleid')->default(0);
    },
    'user_values' => function (Blueprint $t) {
        $t->unsignedInteger('userid')->default(0);
        $t->unsignedInteger('tmplvarid')->default(0);
        $t->text('value')->nullable();
    },
    'member_groups' => function (Blueprint $t) {
        $t->increments('id');
        $t->unsignedInteger('user_group')->default(0);
        $t->unsignedInteger('member')->default(0);
    },
];
foreach (array_keys($tables) as $table) {
    $schema->dropIfExists($table);
}
foreach ($tables as $table => $definition) {
    $schema->create($table, $definition);
}

// role 2 (the actor's) holds what the actor holds; role 3 holds settings, which the actor lacks
foreach ([1 => 'Administrator', 2 => 'Editor', 3 => 'Publisher'] as $id => $name) {
    $db->table('user_roles')->insert(['id' => $id, 'name' => $name]);
}
foreach ($options['permissions'] as $key) {
    $db->table('permissions')->insert(['key' => $key]);
    $db->table('role_permissions')->insert(['role_id' => 2, 'permission' => $key]);
}
$db->table('role_permissions')->insert([['role_id' => 3, 'permission' => 'edit_document'], ['role_id' => 3, 'permission' => 'settings']]);
// user 10 is the actor, user 11 a user of role 2 it edits
foreach ([10 => 'actor', 11 => 'target'] as $id => $name) {
    $db->table('users')->insert(['id' => $id, 'username' => $name]);
    $db->table('user_attributes')->insert(['internalKey' => $id, 'role' => 2]);
}

$checked = $tvRoles = [];
$db->listen(function ($query) use (&$checked, &$tvRoles) {
    if (str_contains($query->sql, 'role_permissions') && str_contains($query->sql, 'role_id')) {
        $checked[] = $query->bindings[0];
    }
    if (str_contains($query->sql, 'user_role_vars')) {
        $tvRoles[] = end($query->bindings);
    }
});

register_shutdown_function(function () use ($db, &$checked, &$tvRoles) {
    echo json_encode([
        'quit' => $GLOBALS['roleAssignmentQuit'] ?? null,
        'roles' => $db->table('user_attributes')->orderBy('internalKey')->pluck('role', 'internalKey')->map(fn ($role) => (int) $role)->all(),
        'checked' => $checked,
        'tvRoles' => $tvRoles,
    ]);
    foreach (['users', 'user_attributes', 'user_roles', 'role_permissions', 'permissions', 'site_tmplvars', 'user_role_vars', 'user_values', 'member_groups'] as $table) {
        $db->getSchemaBuilder()->dropIfExists($table);
    }
});

$_SESSION = ['mgrRole' => 2, 'mgrInternalKey' => 10];
$_POST = $options['post'] + ['newpassword' => '0', 'stay' => '', 'passwordnotifymethod' => '', 'email' => 'x@example.test'];

$controller = (new ReflectionClass(EvolutionCMS\Controllers\Users\EditOrNewUser::class))->newInstanceWithoutConstructor();
// header() after the save only warns in the CLI
@$controller->process();
