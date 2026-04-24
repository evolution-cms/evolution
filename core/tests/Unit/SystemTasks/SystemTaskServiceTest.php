<?php

use Carbon\Carbon;
use EvolutionCMS\Models\SystemCliTask;
use EvolutionCMS\Services\Store\CatalogService;
use EvolutionCMS\Services\SystemTasks\SchedulerHealthService;
use EvolutionCMS\Services\SystemTasks\SystemTaskService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

beforeAll(function () {
    $capsule = new Capsule();
    $capsule->addConnection([
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    Model::setConnectionResolver($capsule->getDatabaseManager());

    $schema = $capsule->schema();

    $schema->create('system_cli_tasks', function (Blueprint $table) {
        $table->increments('id');
        $table->string('uuid', 36)->unique();
        $table->string('type', 64)->default('');
        $table->string('target', 191)->default('');
        $table->string('requested_version', 191)->default('');
        $table->string('status', 32)->default('queued');
        $table->string('step', 64)->default('');
        $table->unsignedSmallInteger('progress')->default(0);
        $table->string('message', 255)->default('');
        $table->text('payload_json')->nullable();
        $table->text('result_json')->nullable();
        $table->unsignedInteger('created_by')->nullable();
        $table->string('locked_by', 191)->default('');
        $table->unsignedInteger('attempt_count')->default(0);
        $table->dateTime('lease_expires_at')->nullable();
        $table->string('worker_host', 191)->default('');
        $table->integer('worker_pid')->nullable();
        $table->string('error_code', 64)->default('');
        $table->string('catalog_snapshot_hash', 64)->default('');
        $table->text('requested_by_snapshot')->nullable();
        $table->dateTime('started_at')->nullable();
        $table->dateTime('heartbeat_at')->nullable();
        $table->dateTime('cancellation_requested_at')->nullable();
        $table->dateTime('finished_at')->nullable();
        $table->dateTime('created_at')->nullable();
        $table->dateTime('updated_at')->nullable();
    });

    $schema->create('system_cli_task_logs', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('task_id');
        $table->unsignedInteger('seq')->default(0);
        $table->string('level', 16)->default('info');
        $table->string('step', 64)->default('');
        $table->text('message');
        $table->text('context_json')->nullable();
        $table->dateTime('created_at')->nullable();
    });

    $schema->create('system_scheduler_health', function (Blueprint $table) {
        $table->unsignedTinyInteger('id')->primary();
        $table->dateTime('last_heartbeat_at')->nullable();
        $table->string('last_heartbeat_host', 191)->default('');
        $table->string('last_heartbeat_mode', 32)->default('');
        $table->dateTime('updated_at')->nullable();
    });

    $schema->create('system_worker_health', function (Blueprint $table) {
        $table->unsignedTinyInteger('id')->primary();
        $table->dateTime('last_worker_run_at')->nullable();
        $table->dateTime('last_worker_pick_at')->nullable();
        $table->dateTime('last_worker_success_at')->nullable();
        $table->dateTime('last_worker_failed_at')->nullable();
        $table->string('last_worker_error_code', 64)->default('');
        $table->string('last_worker_host', 191)->default('');
        $table->integer('last_worker_pid')->nullable();
        $table->dateTime('updated_at')->nullable();
    });
});

beforeEach(function () {
    SystemCliTask::query()->delete();
    \EvolutionCMS\Models\SystemCliTaskLog::query()->delete();
    \EvolutionCMS\Models\SystemSchedulerHealth::query()->delete();
    \EvolutionCMS\Models\SystemWorkerHealth::query()->delete();
});

function invokeSystemTaskServiceMethod(SystemTaskService $service, string $method, array $args = [])
{
    $reflection = new ReflectionClass($service);
    $instanceMethod = $reflection->getMethod($method);
    $instanceMethod->setAccessible(true);

    return $instanceMethod->invokeArgs($service, $args);
}

test('console install snapshot normalizes default branch version for composer execution', function () {
    $service = new SystemTaskService();
    $snapshot = invokeSystemTaskServiceMethod($service, 'buildConsoleInstallSnapshot', [[
        'install_method' => 'console-extra',
        'name' => 'eCodeMirror',
        'composer_name' => 'evolution-cms/ecodemirror',
        'repo_full_name' => 'evolution-cms/ecodemirror',
        'source_url' => 'https://github.com/evolution-cms/ecodemirror',
        'readme_branch' => 'main',
    ], 'main']);

    expect($snapshot['task_type'])->toBe('console_install')
        ->and($snapshot['resolved_version'])->toBe('main')
        ->and($snapshot['composer_version'])->toBe('dev-main')
        ->and($snapshot['composer_name'])->toBe('evolution-cms/ecodemirror')
        ->and($snapshot['source_kind'])->toBe('console')
        ->and($snapshot['source_label'])->toBe('')
        ->and($snapshot['source_url'])->toBe('https://github.com/evolution-cms/ecodemirror')
        ->and($snapshot['capabilities'])->toBe([
            'discover' => true,
            'publish' => true,
            'migrate' => true,
        ]);
});

test('console install snapshot keeps explicit dev versions unchanged', function () {
    $service = new SystemTaskService();
    $snapshot = invokeSystemTaskServiceMethod($service, 'buildConsoleInstallSnapshot', [[
        'name' => 'eCodeMirror',
        'composer_name' => 'evolution-cms/ecodemirror',
        'readme_branch' => 'main',
    ], 'dev-main']);

    expect($snapshot['composer_version'])->toBe('dev-main')
        ->and($snapshot['resolved_version'])->toBe('dev-main');
});

test('task status payload exposes refresh only for finished like states', function () {
    $service = new SystemTaskService();

    $queuedTask = new SystemCliTask();
    $queuedTask->setRawAttributes([
        'id' => 10,
        'uuid' => 'queued-task',
        'type' => 'console_install',
        'target' => 'evolution-cms/ecodemirror',
        'requested_version' => 'main',
        'status' => 'queued',
        'step' => 'queued',
        'progress' => 0,
        'message' => 'Queued',
        'error_code' => '',
        'payload_json' => json_encode([
            'package_name' => 'eCodeMirror',
        ]),
        'created_at' => '2026-04-12 10:00:00',
    ], true);

    $succeededTask = new SystemCliTask();
    $succeededTask->setRawAttributes([
        'id' => 11,
        'uuid' => 'succeeded-task',
        'type' => 'console_install',
        'target' => 'evolution-cms/ecodemirror',
        'requested_version' => 'main',
        'status' => 'succeeded',
        'step' => 'completed',
        'progress' => 100,
        'message' => 'Done',
        'error_code' => '',
        'created_at' => '2026-04-12 10:00:00',
        'finished_at' => '2026-04-12 10:01:00',
    ], true);

    $queuedPayload = invokeSystemTaskServiceMethod($service, 'buildTaskStatusPayload', [$queuedTask]);
    $succeededPayload = invokeSystemTaskServiceMethod($service, 'buildTaskStatusPayload', [$succeededTask]);

    expect($queuedPayload['can_refresh_state'])->toBeFalse()
        ->and($queuedPayload['status'])->toBe('queued')
        ->and($queuedPayload['display_title'])->toBe('eCodeMirror')
        ->and($queuedPayload['source_kind'])->toBe('')
        ->and($queuedPayload['source_label'])->toBe('')
        ->and($succeededPayload['can_refresh_state'])->toBeTrue()
        ->and($succeededPayload['status'])->toBe('succeeded')
        ->and($succeededPayload['progress'])->toBe(100);
});

test('task status payload prefers display title over package name', function () {
    $service = new SystemTaskService();

    $task = new SystemCliTask();
    $task->setRawAttributes([
        'id' => 12,
        'uuid' => 'display-title-task',
        'type' => 'console_uninstall',
        'target' => 'evolution-cms-extras/doclister',
        'requested_version' => '2.7.9',
        'status' => 'queued',
        'step' => 'queued',
        'progress' => 10,
        'message' => 'Queued',
        'error_code' => '',
        'payload_json' => json_encode([
            'package_name' => 'evolution-cms-extras/doclister',
            'display_title' => 'DocLister',
            'source_kind' => 'console',
            'source_label' => 'Console',
        ]),
        'created_at' => '2026-04-12 10:00:00',
    ], true);

    $payload = invokeSystemTaskServiceMethod($service, 'buildTaskStatusPayload', [$task]);

    expect($payload['display_title'])->toBe('DocLister')
        ->and($payload['source_kind'])->toBe('console')
        ->and($payload['source_label'])->toBe('Console');
});

test('task creation is denied without module execution permission', function () {
    $catalog = new class extends CatalogService {
        public function getConsoleCatalog()
        {
            return [[
                'id' => 'console-ecodemirror',
                'name' => 'eCodeMirror',
                'composer_name' => 'evolution-cms/ecodemirror',
                'version' => 'main',
                'readme_branch' => 'main',
                'url' => [
                    'fieldValue' => [
                        ['file' => 'main'],
                    ],
                ],
            ]];
        }
    };

    $service = new SystemTaskService($catalog);
    $response = $service->createTaskFromStoreRequest('console_install', [
        'catalog_item_id' => 'console-ecodemirror',
        'version' => 'main',
    ], [
        'user_id' => 7,
        'permissions' => [
            'exec_module' => false,
            'system_tasks.view' => 0,
            'system_tasks.manage_packages' => 0,
            'system_tasks.site_update' => 0,
        ],
    ], false);

    expect($response['ok'])->toBeFalse()
        ->and($response['error_code'])->toBe('ACL_DENIED');
});

test('console install task creation is denied without system task package permission', function () {
    (new SchedulerHealthService())->recordHeartbeat('tests', 'manual');

    $catalog = new class extends CatalogService {
        public function getConsoleCatalog()
        {
            return [[
                'id' => 'console-ecodemirror',
                'name' => 'eCodeMirror',
                'composer_name' => 'evolution-cms/ecodemirror',
                'version' => 'main',
                'readme_branch' => 'main',
                'url' => [
                    'fieldValue' => [
                        ['file' => 'main'],
                    ],
                ],
            ]];
        }
    };

    $service = new SystemTaskService($catalog);
    $response = $service->createTaskFromStoreRequest('console_install', [
        'catalog_item_id' => 'console-ecodemirror',
        'version' => 'main',
    ], [
        'user_id' => 7,
        'permissions' => [
            'exec_module' => true,
            'system_tasks.view' => 0,
            'system_tasks.manage_packages' => 0,
            'system_tasks.site_update' => 0,
        ],
    ], false);

    expect($response['ok'])->toBeFalse()
        ->and($response['error_code'])->toBe('ACL_DENIED');
});

test('console uninstall task creation requires system task package permission', function () {
    (new SchedulerHealthService())->recordHeartbeat('tests', 'manual');

    $catalog = new class extends CatalogService {
        public function getConsoleCatalog()
        {
            return [[
                'id' => 'console-esettings',
                'name' => 'sSettings',
                'composer_name' => 'evolution-cms/esettings',
                'version' => 'main',
                'current_version' => 'main',
                'readme_branch' => 'main',
                'url' => [
                    'fieldValue' => [
                        ['file' => 'main'],
                    ],
                ],
            ]];
        }
    };

    $service = new SystemTaskService($catalog);
    $response = $service->createTaskFromStoreRequest('console_uninstall', [
        'catalog_item_id' => 'console-esettings',
        'version' => 'main',
    ], [
        'user_id' => 7,
        'permissions' => [
            'exec_module' => true,
            'system_tasks.view' => 0,
            'system_tasks.manage_packages' => 0,
            'system_tasks.site_update' => 0,
        ],
    ], false);

    expect($response['ok'])->toBeFalse()
        ->and($response['error_code'])->toBe('ACL_DENIED');
});

test('console uninstall task creation persists composer remove snapshot', function () {
    (new SchedulerHealthService())->recordHeartbeat('tests', 'manual');

    $catalog = new class extends CatalogService {
        public function getConsoleCatalog()
        {
            return [[
                'id' => 'console-esettings',
                'name' => 'sSettings',
                'composer_name' => 'evolution-cms/esettings',
                'version' => 'main',
                'current_version' => 'main',
                'readme_branch' => 'main',
                'source_url' => 'https://github.com/evolution-cms/esettings',
                'url' => [
                    'fieldValue' => [
                        ['file' => 'main'],
                    ],
                ],
            ]];
        }
    };

    $service = new SystemTaskService($catalog);
    $response = $service->createTaskFromStoreRequest('console_uninstall', [
        'catalog_item_id' => 'console-esettings',
        'version' => 'main',
    ], [
        'user_id' => 7,
        'permissions' => [
            'exec_module' => true,
            'system_tasks.view' => 1,
            'system_tasks.manage_packages' => 1,
            'system_tasks.site_update' => 0,
        ],
    ], false);

    expect($response['ok'])->toBeTrue()
        ->and($response['task']['type'])->toBe('console_uninstall')
        ->and($response['task']['target'])->toBe('evolution-cms/esettings');

    $task = SystemCliTask::query()->find($response['task']['id']);
    expect($task)->not->toBeNull()
        ->and($task->payload_json['task_type'])->toBe('console_uninstall')
        ->and($task->payload_json['composer_name'])->toBe('evolution-cms/esettings')
        ->and($task->payload_json['capabilities'])->toBe([
            'composer_remove_only' => true,
            'artifact_cleanup' => false,
        ]);
});

test('task creation is blocked when scheduler health is unhealthy', function () {
    $catalog = new class extends CatalogService {
        public function getConsoleCatalog()
        {
            return [[
                'id' => 'console-ecodemirror',
                'name' => 'eCodeMirror',
                'composer_name' => 'evolution-cms/ecodemirror',
                'version' => 'main',
                'readme_branch' => 'main',
                'url' => [
                    'fieldValue' => [
                        ['file' => 'main'],
                    ],
                ],
            ]];
        }
    };

    $service = new SystemTaskService($catalog);
    $response = $service->createTaskFromStoreRequest('console_install', [
        'catalog_item_id' => 'console-ecodemirror',
        'version' => 'main',
    ], [
        'user_id' => 7,
        'permissions' => [
            'exec_module' => true,
            'system_tasks.view' => 1,
            'system_tasks.manage_packages' => 1,
            'system_tasks.site_update' => 0,
        ],
    ], false);

    expect($response['ok'])->toBeFalse()
        ->and($response['error_code'])->toBe('SCHEDULER_UNHEALTHY');
});

test('task creation is blocked while another mutating task is already active', function () {
    (new SchedulerHealthService())->recordHeartbeat('tests', 'manual');

    SystemCliTask::query()->create([
        'uuid' => 'active-task',
        'type' => 'console_install',
        'target' => 'evolution-cms/ecodemirror',
        'requested_version' => 'main',
        'status' => 'queued',
        'step' => 'queued',
        'progress' => 0,
        'message' => 'Queued',
        'payload_json' => [],
        'result_json' => [],
        'created_by' => 7,
        'locked_by' => '',
        'attempt_count' => 0,
        'worker_host' => '',
        'worker_pid' => null,
        'error_code' => '',
        'catalog_snapshot_hash' => '',
        'requested_by_snapshot' => ['user_id' => 7],
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);

    $catalog = new class extends CatalogService {
        public function getConsoleCatalog()
        {
            return [[
                'id' => 'console-ecodemirror',
                'name' => 'eCodeMirror',
                'composer_name' => 'evolution-cms/ecodemirror',
                'version' => 'main',
                'readme_branch' => 'main',
                'url' => [
                    'fieldValue' => [
                        ['file' => 'main'],
                    ],
                ],
            ]];
        }
    };

    $service = new SystemTaskService($catalog);
    $response = $service->createTaskFromStoreRequest('console_install', [
        'catalog_item_id' => 'console-ecodemirror',
        'version' => 'main',
    ], [
        'user_id' => 7,
        'permissions' => [
            'exec_module' => true,
            'system_tasks.view' => 1,
            'system_tasks.manage_packages' => 1,
            'system_tasks.site_update' => 0,
        ],
    ], false);

    expect($response['ok'])->toBeFalse()
        ->and($response['error_code'])->toBe('GLOBAL_LOCK_ACTIVE')
        ->and($response['active_task']['status'])->toBe('queued');
});

test('console install task creation returns warnings when worker health is not fully healthy', function () {
    (new SchedulerHealthService())->recordHeartbeat('tests', 'manual');

    $catalog = new class extends CatalogService {
        public function getConsoleCatalog()
        {
            return [[
                'id' => 'console-ecodemirror',
                'name' => 'eCodeMirror',
                'composer_name' => 'evolution-cms/ecodemirror',
                'version' => 'main',
                'readme_branch' => 'main',
                'url' => [
                    'fieldValue' => [
                        ['file' => 'main'],
                    ],
                ],
            ]];
        }
    };

    $service = new SystemTaskService($catalog);
    $response = $service->createTaskFromStoreRequest('console_install', [
        'catalog_item_id' => 'console-ecodemirror',
        'version' => 'main',
    ], [
        'user_id' => 7,
        'permissions' => [
            'exec_module' => true,
            'system_tasks.view' => 1,
            'system_tasks.manage_packages' => 1,
            'system_tasks.site_update' => 0,
        ],
        'session_hash' => 'owner-session',
    ], false);

    expect($response['ok'])->toBeTrue()
        ->and($response['warnings'])->toHaveCount(1)
        ->and($response['warnings'][0]['code'])->toBe('WORKER_STATUS_WARNING')
        ->and($response['task']['status'])->toBe('queued')
        ->and($response['task']['progress'])->toBe(10)
        ->and($response['task']['logs'])->toHaveCount(1)
        ->and($response['task']['logs'][0]['step'])->toBe('queued');
});

test('site update task creation requires dedicated site update permission', function () {
    (new SchedulerHealthService())->recordHeartbeat('tests', 'manual');

    $service = new SystemTaskService();
    $response = $service->createTaskFromStoreRequest('site_update', [
        'target_ref' => '3.5.x',
    ], [
        'user_id' => 7,
        'permissions' => [
            'exec_module' => true,
            'system_tasks.view' => 1,
            'system_tasks.manage_packages' => 0,
            'system_tasks.site_update' => 0,
        ],
        'session_hash' => 'owner-session',
    ], true);

    expect($response['ok'])->toBeFalse()
        ->and($response['error_code'])->toBe('ACL_DENIED');
});

test('site update task creation remains limited to super administrators', function () {
    (new SchedulerHealthService())->recordHeartbeat('tests', 'manual');

    $service = new SystemTaskService();
    $response = $service->createTaskFromStoreRequest('site_update', [
        'target_ref' => '3.5.x',
    ], [
        'user_id' => 7,
        'permissions' => [
            'exec_module' => true,
            'system_tasks.view' => 1,
            'system_tasks.manage_packages' => 0,
            'system_tasks.site_update' => 1,
        ],
        'session_hash' => 'owner-session',
    ], false);

    expect($response['ok'])->toBeFalse()
        ->and($response['error_code'])->toBe('ACL_DENIED');
});

test('site update task creation is blocked when worker health is unhealthy', function () {
    (new SchedulerHealthService())->recordHeartbeat('tests', 'manual');

    $service = new SystemTaskService();
    $response = $service->createTaskFromStoreRequest('site_update', [
        'target_ref' => '3.5.x',
    ], [
        'user_id' => 7,
        'permissions' => [
            'exec_module' => true,
            'system_tasks.view' => 1,
            'system_tasks.manage_packages' => 0,
            'system_tasks.site_update' => 1,
        ],
        'session_hash' => 'owner-session',
    ], true);

    expect($response['ok'])->toBeFalse()
        ->and($response['error_code'])->toBe('SITE_UPDATE_BLOCKED');
});

test('site update task snapshot keeps custom update repository', function () {
    $service = new SystemTaskService();
    $response = $service->createSiteUpdateTask('middleDuck/evo-updater-manager-flow', [
        'user_id' => 1,
    ], true, 'middleDuckAi/evolution');
    $task = SystemCliTask::query()->latest('id')->first();

    expect($response['ok'])->toBeTrue()
        ->and($response['task']['requested_version'])->toBe('middleDuck/evo-updater-manager-flow')
        ->and($task->payload_json['target_ref'])->toBe('middleDuck/evo-updater-manager-flow')
        ->and($task->payload_json['update_repository'])->toBe('middleDuckAi/evolution');
});

test('site update task snapshot keeps database backup choice', function () {
    $service = new SystemTaskService();
    $response = $service->createSiteUpdateTask('3.5.x', [
        'user_id' => 1,
    ], true, '', false);
    $task = SystemCliTask::query()->latest('id')->first();

    expect($response['ok'])->toBeTrue()
        ->and($task->payload_json['backup_database'])->toBeFalse()
        ->and($task->payload_json['capabilities']['database_backup'])->toBeFalse();
});

test('site update task rejects invalid custom update repository', function () {
    $service = new SystemTaskService();
    $response = $service->createSiteUpdateTask('3.5.x', [
        'user_id' => 1,
    ], true, 'https://github.com/middleDuckAi/evolution');

    expect($response['ok'])->toBeFalse()
        ->and($response['error_code'])->toBe('SNAPSHOT_INVALID');
});

test('task status payload denies access to another manager user', function () {
    $service = new SystemTaskService();
    $task = SystemCliTask::query()->create([
        'uuid' => 'owned-task',
        'type' => 'console_install',
        'target' => 'evolution-cms/ecodemirror',
        'requested_version' => 'main',
        'status' => 'queued',
        'step' => 'queued',
        'progress' => 0,
        'message' => 'Queued',
        'payload_json' => [],
        'result_json' => [],
        'created_by' => 7,
        'locked_by' => '',
        'attempt_count' => 0,
        'worker_host' => '',
        'worker_pid' => null,
        'error_code' => '',
        'catalog_snapshot_hash' => '',
        'requested_by_snapshot' => ['user_id' => 7, 'session_hash' => 'owner-session'],
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);

    $response = $service->getTaskStatusPayload((int) $task->id, '', [
        'user_id' => 99,
        'permissions' => [
            'exec_module' => true,
            'system_tasks.view' => 1,
            'system_tasks.manage_packages' => 0,
            'system_tasks.site_update' => 0,
        ],
        'session_hash' => 'another-session',
    ], false);

    expect($response['ok'])->toBeFalse()
        ->and($response['error_code'])->toBe('ACL_DENIED');
});

test('task status payload allows owner session with system task view permission', function () {
    $service = new SystemTaskService();
    $task = SystemCliTask::query()->create([
        'uuid' => 'owned-task-view',
        'type' => 'console_install',
        'target' => 'evolution-cms/ecodemirror',
        'requested_version' => 'main',
        'status' => 'queued',
        'step' => 'queued',
        'progress' => 0,
        'message' => 'Queued',
        'payload_json' => [],
        'result_json' => [],
        'created_by' => 7,
        'locked_by' => '',
        'attempt_count' => 0,
        'worker_host' => '',
        'worker_pid' => null,
        'error_code' => '',
        'catalog_snapshot_hash' => '',
        'requested_by_snapshot' => ['user_id' => 7, 'session_hash' => 'owner-session'],
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);

    $response = $service->getTaskStatusPayload((int) $task->id, '', [
        'user_id' => 7,
        'permissions' => [
            'exec_module' => true,
            'system_tasks.view' => 1,
            'system_tasks.manage_packages' => 0,
            'system_tasks.site_update' => 0,
        ],
        'session_hash' => 'owner-session',
    ], false);

    expect($response['ok'])->toBeTrue()
        ->and($response['task']['uuid'])->toBe('owned-task-view')
        ->and($response['task']['status'])->toBe('queued');
});

test('task status payload requires system task view permission even for owner session', function () {
    $service = new SystemTaskService();
    $task = SystemCliTask::query()->create([
        'uuid' => 'owned-task-no-view',
        'type' => 'console_install',
        'target' => 'evolution-cms/ecodemirror',
        'requested_version' => 'main',
        'status' => 'queued',
        'step' => 'queued',
        'progress' => 0,
        'message' => 'Queued',
        'payload_json' => [],
        'result_json' => [],
        'created_by' => 7,
        'locked_by' => '',
        'attempt_count' => 0,
        'worker_host' => '',
        'worker_pid' => null,
        'error_code' => '',
        'catalog_snapshot_hash' => '',
        'requested_by_snapshot' => ['user_id' => 7, 'session_hash' => 'owner-session'],
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);

    $response = $service->getTaskStatusPayload((int) $task->id, '', [
        'user_id' => 7,
        'permissions' => [
            'exec_module' => true,
            'system_tasks.view' => 0,
            'system_tasks.manage_packages' => 0,
            'system_tasks.site_update' => 0,
        ],
        'session_hash' => 'owner-session',
    ], false);

    expect($response['ok'])->toBeFalse()
        ->and($response['error_code'])->toBe('ACL_DENIED');
});
