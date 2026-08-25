<?php

use Carbon\Carbon;
use EvolutionCMS\Interfaces\SystemTaskHandlerInterface;
use EvolutionCMS\Models\SystemCliTask;
use EvolutionCMS\Services\SystemTasks\SystemTaskRegistry;
use EvolutionCMS\Services\SystemTasks\SystemTaskService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * A handler standing in for one a package would register.
 *
 * Declared once at file scope rather than inline per test so a class-string
 * registration has something real to resolve.
 */
final class RegistryTestHandler implements SystemTaskHandlerInterface
{
    public function execute(SystemCliTask $task, ?callable $report = null)
    {
        if ($report !== null) {
            $report('working', 50, 'Halfway.', 'info', []);
        }

        return ['message' => 'Registry test handler finished.', 'result' => ['ok' => true]];
    }
}

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

/** The types these tests register. The registry is process-wide, so they are cleaned up around every test. */
function registryTestTypes(): array
{
    return ['pkg.batch', 'pkg.exclusive', 'pkg.instance', 'pkg.factory', 'pkg.bogus', 'pkg.temp'];
}

function forgetRegistryTestTypes(): void
{
    foreach (registryTestTypes() as $type) {
        SystemTaskRegistry::forget($type);
    }
}

beforeEach(function () {
    SystemCliTask::query()->delete();
    \EvolutionCMS\Models\SystemCliTaskLog::query()->delete();
    \EvolutionCMS\Models\SystemSchedulerHealth::query()->delete();
    \EvolutionCMS\Models\SystemWorkerHealth::query()->delete();
    forgetRegistryTestTypes();

    // A concurrent type with room for three, used by most of the queue tests.
    SystemTaskRegistry::register('pkg.batch', RegistryTestHandler::class, [
        'mode' => SystemTaskRegistry::MODE_CONCURRENT,
        'parallelism' => 3,
        'permissions' => ['pkg.run'],
        'label' => 'Package batch',
    ]);
});

afterEach(function () {
    forgetRegistryTestTypes();
});

function makeRegistryTask(string $type, string $status, ?Carbon $lease = null): SystemCliTask
{
    static $counter = 0;
    $counter++;

    return SystemCliTask::query()->create([
        'uuid' => 'registry-task-' . $counter,
        'type' => $type,
        'target' => '',
        'requested_version' => '',
        'status' => $status,
        'step' => $status,
        'progress' => 0,
        'message' => '',
        'payload_json' => [],
        'result_json' => [],
        'created_by' => 1,
        'locked_by' => '',
        'attempt_count' => 0,
        'lease_expires_at' => $lease,
        'worker_host' => '',
        'worker_pid' => null,
        'error_code' => '',
        'catalog_snapshot_hash' => '',
        'requested_by_snapshot' => ['user_id' => 1],
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);
}

function runRegistryPreflight(SystemTaskService $service, string $type, array $snapshot, bool $isSuperAdmin = false): array
{
    $method = new ReflectionMethod($service, 'runCreatePreflight');
    $method->setAccessible(true);

    return $method->invoke($service, $type, $snapshot, $isSuperAdmin);
}

function registryAdminSnapshot(): array
{
    return [
        'user_id' => 7,
        'permissions' => [
            'exec_module' => true,
            'system_tasks.view' => 1,
            'system_tasks.manage_packages' => 1,
            'system_tasks.site_update' => 1,
            'pkg.run' => 1,
        ],
    ];
}

// ---------------------------------------------------------------------------
// The built-in declarations must reproduce the switch statements they replaced
// ---------------------------------------------------------------------------

test('the three built-in task types stay registered and exclusive', function () {
    expect(SystemTaskRegistry::has('console_install'))->toBeTrue()
        ->and(SystemTaskRegistry::has('console_uninstall'))->toBeTrue()
        ->and(SystemTaskRegistry::has('site_update'))->toBeTrue()
        ->and(SystemTaskRegistry::isExclusive('console_install'))->toBeTrue()
        ->and(SystemTaskRegistry::isExclusive('console_uninstall'))->toBeTrue()
        ->and(SystemTaskRegistry::isExclusive('site_update'))->toBeTrue();
});

test('built-in permissions match the checks the registry replaced', function () {
    expect(SystemTaskRegistry::permissions('console_install'))->toBe(['system_tasks.manage_packages'])
        ->and(SystemTaskRegistry::permissions('console_uninstall'))->toBe(['system_tasks.manage_packages'])
        ->and(SystemTaskRegistry::permissions('site_update'))->toBe(['system_tasks.site_update'])
        ->and(SystemTaskRegistry::requiresSuperAdmin('site_update'))->toBeTrue()
        ->and(SystemTaskRegistry::requiresSuperAdmin('console_install'))->toBeFalse();
});

test('an unknown type is unregistered and assumed exclusive', function () {
    expect(SystemTaskRegistry::has('nope'))->toBeFalse()
        ->and(SystemTaskRegistry::isExclusive('nope'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Registration
// ---------------------------------------------------------------------------

test('a malformed task type is refused', function () {
    expect(fn () => SystemTaskRegistry::register('', RegistryTestHandler::class))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => SystemTaskRegistry::register(str_repeat('a', 65), RegistryTestHandler::class))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => SystemTaskRegistry::register('Bad_Type', RegistryTestHandler::class))
        ->toThrow(InvalidArgumentException::class);
});

test('an unknown concurrency mode is refused', function () {
    expect(fn () => SystemTaskRegistry::register('pkg.temp', RegistryTestHandler::class, ['mode' => 'whenever']))
        ->toThrow(InvalidArgumentException::class);
});

test('a built-in type cannot be redefined or forgotten', function () {
    expect(fn () => SystemTaskRegistry::register('site_update', RegistryTestHandler::class))
        ->toThrow(InvalidArgumentException::class);

    SystemTaskRegistry::forget('site_update');

    expect(SystemTaskRegistry::has('site_update'))->toBeTrue();
});

test('an exclusive registration cannot claim a parallelism', function () {
    SystemTaskRegistry::register('pkg.exclusive', RegistryTestHandler::class, ['parallelism' => 9]);

    expect(SystemTaskRegistry::parallelism('pkg.exclusive'))->toBe(1)
        ->and(SystemTaskRegistry::parallelism('pkg.batch'))->toBe(3);
});

test('a handler resolves from a class string, an instance or a factory', function () {
    SystemTaskRegistry::register('pkg.instance', new RegistryTestHandler());
    SystemTaskRegistry::register('pkg.factory', fn () => new RegistryTestHandler());

    expect(SystemTaskRegistry::handler('pkg.batch'))->toBeInstanceOf(RegistryTestHandler::class)
        ->and(SystemTaskRegistry::handler('pkg.instance'))->toBeInstanceOf(RegistryTestHandler::class)
        ->and(SystemTaskRegistry::handler('pkg.factory'))->toBeInstanceOf(RegistryTestHandler::class);
});

test('a registered class that is not a handler is refused when resolved', function () {
    SystemTaskRegistry::register('pkg.bogus', stdClass::class);

    expect(fn () => SystemTaskRegistry::handler('pkg.bogus'))->toThrow(InvalidArgumentException::class);
    expect(fn () => SystemTaskRegistry::handler('nope'))->toThrow(InvalidArgumentException::class);
});

test('exclusiveTypes omits concurrent registrations', function () {
    SystemTaskRegistry::register('pkg.exclusive', RegistryTestHandler::class);

    $types = SystemTaskRegistry::exclusiveTypes();

    expect($types)->toContain('site_update')
        ->and($types)->toContain('pkg.exclusive')
        ->and($types)->not->toContain('pkg.batch');
});

// ---------------------------------------------------------------------------
// Queue concurrency
// ---------------------------------------------------------------------------

test('a concurrent task is picked while an exclusive one is running', function () {
    makeRegistryTask('site_update', 'running', Carbon::now()->addMinutes(10));
    $queued = makeRegistryTask('pkg.batch', 'queued');

    $picked = (new SystemTaskService())->acquireNextQueuedTask('worker-1');

    expect($picked)->not->toBeNull()
        ->and((int) $picked->id)->toBe((int) $queued->id);
});

test('an exclusive task waits while another exclusive one is running', function () {
    makeRegistryTask('site_update', 'running', Carbon::now()->addMinutes(10));
    makeRegistryTask('console_install', 'queued');

    expect((new SystemTaskService())->acquireNextQueuedTask('worker-1'))->toBeNull();
});

test('a blocked exclusive task does not stall a concurrent one behind it', function () {
    makeRegistryTask('site_update', 'running', Carbon::now()->addMinutes(10));
    makeRegistryTask('console_install', 'queued');
    $batch = makeRegistryTask('pkg.batch', 'queued');

    $picked = (new SystemTaskService())->acquireNextQueuedTask('worker-1');

    expect($picked)->not->toBeNull()
        ->and((int) $picked->id)->toBe((int) $batch->id);
});

test('an expired lease stops holding the queue', function () {
    makeRegistryTask('site_update', 'picked', Carbon::now()->subMinutes(30));
    $queued = makeRegistryTask('console_install', 'queued');

    $picked = (new SystemTaskService())->acquireNextQueuedTask('worker-1');

    expect($picked)->not->toBeNull()
        ->and((int) $picked->id)->toBe((int) $queued->id);
});

test('parallelism caps how many tasks of one concurrent type run at once', function () {
    makeRegistryTask('pkg.batch', 'running', Carbon::now()->addMinutes(10));
    makeRegistryTask('pkg.batch', 'running', Carbon::now()->addMinutes(10));
    makeRegistryTask('pkg.batch', 'running', Carbon::now()->addMinutes(10));
    makeRegistryTask('pkg.batch', 'queued');

    expect((new SystemTaskService())->acquireNextQueuedTask('worker-1'))->toBeNull();
});

test('a free slot under the parallelism cap is used', function () {
    makeRegistryTask('pkg.batch', 'running', Carbon::now()->addMinutes(10));
    makeRegistryTask('pkg.batch', 'running', Carbon::now()->addMinutes(10));
    $queued = makeRegistryTask('pkg.batch', 'queued');

    $picked = (new SystemTaskService())->acquireNextQueuedTask('worker-1');

    expect($picked)->not->toBeNull()
        ->and((int) $picked->id)->toBe((int) $queued->id);
});

test('a task whose package is gone is still picked, so the worker can fail it', function () {
    $queued = makeRegistryTask('gone.package', 'queued');

    $picked = (new SystemTaskService())->acquireNextQueuedTask('worker-1');

    expect($picked)->not->toBeNull()
        ->and((int) $picked->id)->toBe((int) $queued->id);
});

test('only one worker can claim a task', function () {
    makeRegistryTask('console_install', 'queued');

    $service = new SystemTaskService();
    $first = $service->acquireNextQueuedTask('worker-1');
    $second = $service->acquireNextQueuedTask('worker-2');

    expect($first)->not->toBeNull()
        ->and($first->status)->toBe('picked')
        ->and((int) $first->attempt_count)->toBe(1)
        ->and($first->lease_expires_at)->not->toBeNull()
        ->and($second)->toBeNull();
});

// ---------------------------------------------------------------------------
// Preflight
// ---------------------------------------------------------------------------

test('an unknown type is refused before any health verdict is disclosed', function () {
    $result = runRegistryPreflight(new SystemTaskService(), 'gone.package', registryAdminSnapshot(), true);

    expect($result['ok'])->toBeFalse()
        ->and($result['error_code'])->toBe('TASK_TYPE_NOT_ALLOWED');
});

test('a package type is gated by the permission it declared', function () {
    $snapshot = ['user_id' => 7, 'permissions' => ['exec_module' => true]];

    $result = runRegistryPreflight(new SystemTaskService(), 'pkg.batch', $snapshot);

    expect($result['ok'])->toBeFalse()
        ->and($result['error_code'])->toBe('ACL_DENIED')
        ->and($result['message'])->toContain('pkg.run');
});

test('a queued exclusive task still blocks another exclusive one', function () {
    makeRegistryTask('site_update', 'queued');

    $result = runRegistryPreflight(new SystemTaskService(), 'console_install', registryAdminSnapshot());

    expect($result['ok'])->toBeFalse()
        ->and($result['error_code'])->toBe('GLOBAL_LOCK_ACTIVE');
});

test('an exclusive task no longer blocks a concurrent one', function () {
    makeRegistryTask('site_update', 'queued');

    $result = runRegistryPreflight(new SystemTaskService(), 'pkg.batch', registryAdminSnapshot());

    // Scheduler health is unseeded here, so the call is expected to stop on a
    // health verdict. What matters is that it got past the global lock.
    expect($result['error_code'] ?? '')->not->toBe('GLOBAL_LOCK_ACTIVE');
});

test('a running concurrent task no longer blocks an exclusive one', function () {
    makeRegistryTask('pkg.batch', 'running', Carbon::now()->addMinutes(10));

    $result = runRegistryPreflight(new SystemTaskService(), 'console_install', registryAdminSnapshot());

    expect($result['error_code'] ?? '')->not->toBe('GLOBAL_LOCK_ACTIVE');
});
