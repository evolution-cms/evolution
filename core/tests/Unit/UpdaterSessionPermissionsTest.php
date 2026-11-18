<?php

/*
| A manager session keeps the permissions it was given at login. The live update window rebuilt them
| only when its own poll saw the task succeed; a poll lost while the update replaced the manager
| left them stale until the next login.
*/

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

function loadUpdaterBuildRefresh(): void
{
    if (function_exists('updaterRefreshPermissionsAfterBuildChange')) {
        return;
    }

    $source = (string) file_get_contents(dirname(__DIR__, 2) . '/../assets/plugins/updater/plugin.updater.php');
    $start = strpos($source, 'function updaterRefreshPermissionsAfterBuildChange(');
    $depth = 0;
    for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
        $depth += $source[$i] === '{' ? 1 : ($source[$i] === '}' ? -1 : 0);
        if ($depth === 0) {
            break;
        }
    }
    eval(substr($source, $start, $i - $start + 1));
}

beforeEach(function () {
    loadUpdaterBuildRefresh();
    $this->session = $_SESSION ?? null;
    $this->resolver = Model::getConnectionResolver();

    $capsule = new Capsule();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    // the connection itself: Capsule::schema() and ::table() go through the global instance
    $db = $capsule->getConnection();
    $schema = $db->getSchemaBuilder();
    $schema->create('user_roles', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name')->default('');
        $table->string('description')->default('');
        $table->timestamps();
    });
    $schema->create('role_permissions', function (Blueprint $table) {
        $table->increments('id');
        $table->string('permission');
        $table->unsignedInteger('role_id');
        $table->timestamps();
    });
    $db->table('user_roles')->insert(['id' => 1, 'name' => 'Administrator']);
    $db->table('role_permissions')->insert([
        ['permission' => 'settings', 'role_id' => 1],
        ['permission' => 'system_tasks.site_update', 'role_id' => 1],
    ]);
    Model::setConnectionResolver($capsule->getDatabaseManager());

    $_SESSION = ['mgrValidated' => 1, 'mgrRole' => 1, 'mgrPermissions' => ['settings' => 1]];
});

afterEach(function () {
    if ($this->resolver === null) {
        Model::unsetConnectionResolver();
    } else {
        Model::setConnectionResolver($this->resolver);
    }
    if ($this->session === null) {
        unset($_SESSION);
    } else {
        $_SESSION = $this->session;
    }
});

test('the first dashboard of a session only notes the build', function () {
    expect(updaterRefreshPermissionsAfterBuildChange(['version' => '3.5.9', 'commit' => 'e8a4913']))->toBeFalse()
        ->and($_SESSION['updater_build'])->toBe('3.5.9@e8a4913')
        ->and($_SESSION['mgrPermissions'])->toBe(['settings' => 1]);

    expect(updaterRefreshPermissionsAfterBuildChange(['version' => '3.5.9', 'commit' => 'e8a4913']))->toBeFalse();
});

test('a new commit of the same version rebuilds the session permissions', function () {
    $_SESSION['updater_build'] = '3.5.9@e8a4913';

    expect(updaterRefreshPermissionsAfterBuildChange(['version' => '3.5.9', 'commit' => 'b796f06']))->toBeTrue()
        ->and($_SESSION['updater_build'])->toBe('3.5.9@b796f06')
        ->and($_SESSION['mgrPermissions'])->toHaveKey('system_tasks.site_update', 1);
});

test('a release that drops the branch note counts as a new build too', function () {
    $_SESSION['updater_build'] = '3.5.9@e8a4913';

    expect(updaterRefreshPermissionsAfterBuildChange(['version' => '3.5.10']))->toBeTrue()
        ->and($_SESSION['updater_build'])->toBe('3.5.10@');
});

test('the dashboard asks for the refresh before the widget renders', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 2) . '/../assets/plugins/updater/plugin.updater.php');

    expect($source)->toContain("if (isset(\$modx->Event->name) && \$modx->Event->name === 'OnManagerWelcomeHome') {\n    updaterRefreshPermissionsAfterBuildChange((array)\$modx->getVersionData());")
        ->and(strpos($source, 'updaterRefreshPermissionsAfterBuildChange((array)'))
        ->toBeLessThan(strpos($source, "if (\$e->name == 'OnManagerWelcomeHome') {"));
});

test('a refresh that fails keeps the old build noted, so the next dashboard tries again', function () {
    $_SESSION['updater_build'] = '3.5.9@e8a4913';
    // refreshCurrentManagerPermissions() refuses a session that is not validated
    unset($_SESSION['mgrValidated']);

    expect(updaterRefreshPermissionsAfterBuildChange(['version' => '3.5.9', 'commit' => 'b796f06']))->toBeFalse()
        ->and($_SESSION['updater_build'])->toBe('3.5.9@e8a4913')
        ->and($_SESSION['mgrPermissions'])->toBe(['settings' => 1]);

    $_SESSION['mgrValidated'] = 1;

    expect(updaterRefreshPermissionsAfterBuildChange(['version' => '3.5.9', 'commit' => 'b796f06']))->toBeTrue()
        ->and($_SESSION['updater_build'])->toBe('3.5.9@b796f06')
        ->and($_SESSION['mgrPermissions'])->toHaveKey('system_tasks.site_update', 1);
});

test('a refresh that throws keeps the old build noted too', function () {
    $_SESSION['updater_build'] = '3.5.9@e8a4913';
    // no role tables: the permission query throws
    $capsule = new Capsule();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    Model::setConnectionResolver($capsule->getDatabaseManager());

    expect(updaterRefreshPermissionsAfterBuildChange(['version' => '3.5.9', 'commit' => 'b796f06']))->toBeFalse()
        ->and($_SESSION['updater_build'])->toBe('3.5.9@e8a4913');
});
