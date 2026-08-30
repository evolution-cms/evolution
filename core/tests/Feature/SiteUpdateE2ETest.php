<?php

/*
|--------------------------------------------------------------------------
| Tier 2 update e2e: install "version N" then update to "N+1"
|--------------------------------------------------------------------------
|
| This exercises the real database side of an Evolution CMS update without
| network or Composer. It builds a populated "version N" SQLite database that
| looks like a site installed before the system task feature existed, then runs
| the same code the "php artisan make:site update" / manager update path runs:
|
|   - the core-only migration that creates the system task tables
|   - SiteUpdateCommand::runUpdateSeeders() (the install update seeders)
|   - SiteUpdateCommand::updateBundledExtrasModule()
|
| and asserts the resulting schema/data deltas. The file-replacement mechanics
| (download/extract/move) are covered separately via the static helpers, since
| the full command run mutates EVO_BASE_PATH and belongs to the Tier 3 sandbox.
*/

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use EvolutionCMS\Models\SystemCliTask;
use EvolutionCMS\Services\SystemTasks\SchedulerHealthService;
use EvolutionCMS\Services\SystemTasks\SiteUpdateFlowService;
use EvolutionCMS\Services\SystemTasks\SystemTaskService;
use EvolutionCMS\Services\SystemTasks\WorkerHealthService;

if (!defined('EVO_BASE_PATH')) {
    define('EVO_BASE_PATH', str_replace('\\', '/', dirname(__DIR__, 3)) . '/');
}
if (!defined('EVO_CORE_PATH')) {
    define('EVO_CORE_PATH', EVO_BASE_PATH . 'core/');
}

/**
 * Boot a standalone SQLite database with the DB/Schema facades wired so real
 * migrations and seeders (which use facades) can run inside the bare PHPUnit harness.
 */
function bootSiteUpdateDatabase(): Capsule
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
    Facade::setFacadeApplication($container);
    Model::setConnectionResolver($capsule->getDatabaseManager());

    // The global \DB / \Schema aliases are normally registered by Laravel's
    // AliasLoader; the bare test harness has no app, so register them for the
    // migrations that reference \DB::getTablePrefix() etc.
    if (!class_exists('DB', false)) {
        class_alias(\Illuminate\Support\Facades\DB::class, 'DB');
    }
    if (!class_exists('Schema', false)) {
        class_alias(\Illuminate\Support\Facades\Schema::class, 'Schema');
    }

    return $capsule;
}

/**
 * Create and seed a database that mirrors a site installed at "version N":
 * a healthy ACL baseline, legacy permission keys that the update seeder renames,
 * and an existing (outdated) bundled Extras module — but no system task tables.
 */
function seedVersionNDatabase(Capsule $capsule): void
{
    $schema = $capsule->getConnection()->getSchemaBuilder();

    $schema->create('permissions_groups', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name')->nullable();
        $table->string('lang_key')->nullable();
        $table->dateTime('created_at')->nullable();
        $table->dateTime('updated_at')->nullable();
    });
    $schema->create('permissions', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name')->nullable();
        $table->string('key')->nullable();
        $table->string('lang_key')->nullable();
        $table->integer('disabled')->default(0);
        $table->integer('group_id')->default(0);
        $table->dateTime('created_at')->nullable();
        $table->dateTime('updated_at')->nullable();
    });
    $schema->create('user_roles', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name')->nullable();
    });
    $schema->create('role_permissions', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('role_id');
        $table->string('permission')->nullable();
        $table->dateTime('created_at')->nullable();
        $table->dateTime('updated_at')->nullable();
    });
    $schema->create('categories', function (Blueprint $table) {
        $table->increments('id');
        $table->string('category')->nullable();
        $table->integer('rank')->default(0);
    });
    $schema->create('site_modules', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name')->nullable();
        $table->text('description')->nullable();
        $table->longText('modulecode')->nullable();
        $table->longText('properties')->nullable();
        $table->string('guid')->nullable();
        $table->integer('enable_sharedparams')->default(0);
        $table->integer('category')->default(0);
        $table->integer('disabled')->default(0);
        $table->dateTime('createdon')->nullable();
        $table->dateTime('editedon')->nullable();
    });
    // Present-but-empty so the migration's updater wiring guard returns cleanly.
    $schema->create('site_plugins', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name')->nullable();
        $table->text('description')->nullable();
        $table->longText('plugincode')->nullable();
        $table->longText('properties')->nullable();
        $table->integer('disabled')->default(0);
    });
    $schema->create('site_plugin_events', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('pluginid');
        $table->integer('evtid');
        $table->integer('priority')->default(0);
    });
    $schema->create('system_eventnames', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name')->nullable();
    });

    $db = $capsule->getConnection();

    // Healthy ACL baseline so the migration's repair self-skips (14 groups, role 1,
    // access_permissions assigned to role 1).
    for ($id = 1; $id <= 14; $id++) {
        $db->table('permissions_groups')->insert(['id' => $id, 'name' => 'Group ' . $id, 'lang_key' => 'group_' . $id]);
    }
    $db->table('user_roles')->insert(['id' => 1, 'name' => 'Administrator']);
    $db->table('permissions')->insert([
        ['name' => 'Manager access permissions', 'key' => 'access_permissions', 'lang_key' => 'manager_access_permissions', 'group_id' => 11],
        // Legacy keys the update seeder must rename.
        ['name' => 'Logout', 'key' => 'logout', 'lang_key' => 'role_logout', 'group_id' => 1],
        ['name' => 'Web access permissions', 'key' => 'web_access_permissions', 'lang_key' => 'role_web_access_permissions', 'group_id' => 11],
    ]);
    $db->table('role_permissions')->insert([
        ['role_id' => 1, 'permission' => 'access_permissions'],
        ['role_id' => 1, 'permission' => 'logout'],
        ['role_id' => 1, 'permission' => 'web_access_permissions'],
    ]);

    // An outdated bundled Extras module that the update should refresh.
    $db->table('site_modules')->insert([
        'name' => 'Extras',
        'description' => '<strong>0.1.0</strong> outdated',
        'modulecode' => 'OUTDATED MODULE CODE',
        'properties' => '',
        'guid' => 'store435243542tf542t5t',
        'enable_sharedparams' => 1,
        'category' => 0,
    ]);
}

/**
 * Run the database-affecting steps of an update, exactly as the update command does.
 */
function runSiteUpdateDatabaseSteps(): void
{
    // 1. Core-only migration that ships the system task tables and permissions.
    (new \CreateSystemCliTasksTables())->up();

    // 2 + 3. The real update command steps, with console output suppressed.
    $command = new class extends \EvolutionCMS\Console\SiteUpdateCommand {
        public function line($string, $style = null, $verbosity = null)
        {
            // Suppress: there is no console output bound in the test harness.
        }

        public function applyUpdateSeeders(): void
        {
            $this->runUpdateSeeders();
        }

        public function applyExtrasModule(): void
        {
            $this->updateBundledExtrasModule();
        }
    };

    $command->applyUpdateSeeders();
    $command->applyExtrasModule();
}

/**
 * Build an isolated core tree whose Artisan entry point records the requested target as the
 * installed version. SiteUpdateFlowService still launches it through the real process boundary.
 */
function managerUpgradeCoreFixture(string $currentVersion): string
{
    $core = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-manager-upgrade-' . bin2hex(random_bytes(8)) . '/core';
    mkdir($core . '/factory', 0777, true);
    file_put_contents(
        $core . '/factory/version.php',
        "<?php\nreturn " . var_export(['version' => $currentVersion], true) . ";\n"
    );
    file_put_contents($core . '/artisan', <<<'PHP'
<?php

if (($argv[1] ?? '') !== 'make:site' || ($argv[2] ?? '') !== 'update' || empty($argv[3])) {
    fwrite(STDERR, "Unexpected update command.\n");
    exit(2);
}

$targetVersion = (string) $argv[3];
file_put_contents(
    __DIR__ . '/factory/version.php',
    "<?php\nreturn " . var_export(['version' => $targetVersion], true) . ";\n"
);
echo "Evolution CMS $targetVersion updated\n";
PHP
    );

    return $core . '/';
}

test('update from version N to N+1 applies migrations, update seeders and refreshes Extras', function () {
    $capsule = bootSiteUpdateDatabase();
    seedVersionNDatabase($capsule);
    $db = $capsule->getConnection();

    // Pre-state sanity: version "N" has no system task tables and legacy permission keys.
    expect($db->getSchemaBuilder()->hasTable('system_cli_tasks'))->toBeFalse()
        ->and($db->table('permissions')->where('key', 'logout')->exists())->toBeTrue();

    runSiteUpdateDatabaseSteps();

    // Migration created the system task tables.
    expect($db->getSchemaBuilder()->hasTable('system_cli_tasks'))->toBeTrue()
        ->and($db->getSchemaBuilder()->hasTable('system_cli_task_logs'))->toBeTrue()
        ->and($db->getSchemaBuilder()->hasTable('system_scheduler_health'))->toBeTrue()
        ->and($db->getSchemaBuilder()->hasTable('system_worker_health'))->toBeTrue();

    // Migration registered + granted the system task permissions to the admin role.
    expect($db->table('permissions')->where('key', 'system_tasks.site_update')->exists())->toBeTrue()
        ->and($db->table('role_permissions')->where('role_id', 1)->where('permission', 'system_tasks.view')->exists())->toBeTrue();

    // Update seeder renamed the legacy permission keys (both definitions and grants).
    expect($db->table('permissions')->where('key', 'logout')->exists())->toBeFalse()
        ->and($db->table('permissions')->where('key', 'widget_recent_info')->exists())->toBeTrue()
        ->and($db->table('permissions')->where('key', 'web_access_permissions')->exists())->toBeFalse()
        ->and($db->table('permissions')->where('key', 'manage_groups')->exists())->toBeTrue()
        ->and($db->table('permissions')->where('key', 'manage_document_permissions')->exists())->toBeTrue()
        ->and($db->table('role_permissions')->where('permission', 'logout')->exists())->toBeFalse()
        ->and($db->table('role_permissions')->where('permission', 'widget_recent_info')->exists())->toBeTrue()
        ->and($db->table('role_permissions')->where('permission', 'manage_groups')->exists())->toBeTrue();

    // Bundled Extras module refreshed from install/assets/modules/store.tpl.
    $extras = $db->table('site_modules')->where('name', 'Extras')->first();
    expect($extras->description)->toContain('<strong>0.2.0</strong>')
        ->and($extras->modulecode)->toContain('store/core.php')
        ->and($extras->modulecode)->not->toBe('OUTDATED MODULE CODE');
});

test('manager system upgrade action queues work and finishes on the requested system version', function () {
    $capsule = bootSiteUpdateDatabase();
    seedVersionNDatabase($capsule);
    (new \CreateSystemCliTasksTables())->up();
    (new SchedulerHealthService())->recordHeartbeat('feature-test', 'manual');
    $workerHealth = new WorkerHealthService();
    $workerHealth->markRun('feature-worker', 1234);
    $workerHealth->markSuccess('feature-worker', 1234);

    $currentVersion = '3.5.8';
    $targetVersion = '3.5.9';
    $corePath = managerUpgradeCoreFixture($currentVersion);
    $taskService = new SystemTaskService();

    try {
        $versionBeforeUpdate = include $corePath . 'factory/version.php';
        expect($versionBeforeUpdate['version'])->toBe($currentVersion);

        // This is the service call made by updaterHandleSystemTaskRequest() when an authenticated
        // super administrator presses the manager's system-update button.
        $queued = $taskService->createTaskFromStoreRequest('site_update', [
            'target_ref' => $targetVersion,
            'backup_database' => '0',
        ], [
            'user_id' => 1,
            'permissions' => [
                'exec_module' => 1,
                'system_tasks.view' => 1,
                'system_tasks.site_update' => 1,
            ],
            'session_hash' => hash('sha256', 'authenticated-manager-cookie'),
        ], true);

        expect($queued['ok'])->toBeTrue()
            ->and($queued['task']['status'])->toBe('queued')
            ->and($queued['task']['requested_version'])->toBe($targetVersion);

        $task = $taskService->acquireNextQueuedTask('feature-worker', 'feature-host', 1234);
        expect($task)->toBeInstanceOf(SystemCliTask::class);

        $result = (new SiteUpdateFlowService($corePath))->execute($task);
        $taskService->markTaskSucceeded($task, $result['message'], $result['result']);

        $installedVersion = include $corePath . 'factory/version.php';
        $completedTask = $task->fresh();

        expect($completedTask->status)->toBe('succeeded')
            ->and($completedTask->result_json['target_ref'])->toBe($targetVersion)
            ->and($installedVersion['version'])->toBe($targetVersion);
    } finally {
        (new Filesystem())->deleteDirectory(dirname(rtrim($corePath, '/')));
    }
});

test('moveFiles replaces files into the destination tree', function () {
    $base = sys_get_temp_dir() . '/evo_update_' . uniqid();
    $src = $base . '/src';
    $dest = $base . '/dest';
    mkdir($src . '/core', 0777, true);
    mkdir($dest . '/core', 0777, true);
    file_put_contents($src . '/index.php', 'NEW');
    file_put_contents($src . '/core/file.php', 'NEW CORE');
    file_put_contents($dest . '/index.php', 'OLD');

    \EvolutionCMS\Console\SiteUpdateCommand::moveFiles($src, $dest);

    expect(file_get_contents($dest . '/index.php'))->toBe('NEW')
        ->and(file_get_contents($dest . '/core/file.php'))->toBe('NEW CORE');

    \EvolutionCMS\Console\SiteUpdateCommand::rmdirs($base);
});

test('rmdirs removes a directory tree recursively', function () {
    $dir = sys_get_temp_dir() . '/evo_rmdirs_' . uniqid();
    mkdir($dir . '/nested', 0777, true);
    file_put_contents($dir . '/nested/leaf.txt', 'x');

    \EvolutionCMS\Console\SiteUpdateCommand::rmdirs($dir);

    expect(is_dir($dir))->toBeFalse();
});
