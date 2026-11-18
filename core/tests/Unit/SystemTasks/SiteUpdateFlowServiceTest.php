<?php

use EvolutionCMS\Models\SystemCliTask;
use EvolutionCMS\Services\SystemTasks\SiteUpdateFlowService;

if (!defined('EVO_CORE_PATH')) {
    define('EVO_CORE_PATH', dirname(__DIR__, 3) . '/');
}

function invokeSiteUpdateFlowMethod(SiteUpdateFlowService $service, string $method, array $args = [])
{
    $reflection = new ReflectionClass($service);
    $instanceMethod = $reflection->getMethod($method);

    return $instanceMethod->invokeArgs($service, $args);
}

test('site update flow builds make site update artisan command with target ref', function () {
    $service = new SiteUpdateFlowService(EVO_CORE_PATH);
    $arguments = invokeSiteUpdateFlowMethod($service, 'buildArtisanProcessArguments', [
        'make:site',
        [
            'command_site' => 'update',
            'version' => '3.5.6',
        ],
    ]);

    expect($arguments)->toBe([
        PHP_BINARY,
        EVO_CORE_PATH . 'artisan',
        'make:site',
        'update',
        '3.5.6',
    ]);
});

test('site update flow passes custom update repository to artisan command', function () {
    $service = new SiteUpdateFlowService(EVO_CORE_PATH);
    $arguments = invokeSiteUpdateFlowMethod($service, 'buildArtisanProcessArguments', [
        'make:site',
        [
            'command_site' => 'update',
            'version' => 'middleDuck/evo-updater-manager-flow',
            'repository' => '--repository=middleDuckAi/evolution',
        ],
    ]);

    expect($arguments)->toBe([
        PHP_BINARY,
        EVO_CORE_PATH . 'artisan',
        'make:site',
        'update',
        'middleDuck/evo-updater-manager-flow',
        '--repository=middleDuckAi/evolution',
    ]);
});

test('site update flow resolves target ref from payload before requested version', function () {
    $service = new SiteUpdateFlowService(EVO_CORE_PATH);
    $task = new SystemCliTask();
    $task->setRawAttributes([
        'requested_version' => '3.5.x',
        'payload_json' => json_encode([
            'target_ref' => "3.5.6\n",
        ]),
    ], true);

    $targetRef = invokeSiteUpdateFlowMethod($service, 'resolveTargetRef', [
        $task,
        $task->payload_json,
    ]);

    expect($targetRef)->toBe('3.5.6');
});

test('site update flow resolves custom repository from payload', function () {
    $service = new SiteUpdateFlowService(EVO_CORE_PATH);

    $repository = invokeSiteUpdateFlowMethod($service, 'resolveUpdateRepository', [[
        'update_repository' => " middleDuckAi/evolution\n",
    ]]);

    expect($repository)->toBe('middleDuckAi/evolution');
});

test('site update flow creates database backup by default', function () {
    $service = new SiteUpdateFlowService(EVO_CORE_PATH);

    expect(invokeSiteUpdateFlowMethod($service, 'shouldCreateDatabaseBackup', [[]]))->toBeTrue()
        ->and(invokeSiteUpdateFlowMethod($service, 'shouldCreateDatabaseBackup', [[
            'backup_database' => '1',
        ]]))->toBeTrue();
});

test('site update flow can skip database backup from task payload', function () {
    $service = new SiteUpdateFlowService(EVO_CORE_PATH);

    expect(invokeSiteUpdateFlowMethod($service, 'shouldCreateDatabaseBackup', [[
        'backup_database' => false,
    ]]))->toBeFalse()
        ->and(invokeSiteUpdateFlowMethod($service, 'shouldCreateDatabaseBackup', [[
            'backup_database' => '0',
        ]]))->toBeFalse();
});

/** make:site replaced by a PHP script that prints what the test gives it. */
function streamingSiteUpdateFlow(string $script): SiteUpdateFlowService
{
    return new class ($script) extends SiteUpdateFlowService {
        protected int $heartbeatSeconds = 1;

        public function __construct(private string $script)
        {
            parent::__construct(sys_get_temp_dir());
        }

        protected function createProcess(array $arguments): \Symfony\Component\Process\Process
        {
            return new \Symfony\Component\Process\Process([PHP_BINARY, '-r', $this->script]);
        }
    };
}

test('site update flow reports make site output while it runs, with milestones and heartbeats', function () {
    $service = streamingSiteUpdateFlow(<<<'PHP'
        echo "Start download Evolution CMS 3.5.x\n";
        usleep(300000);
        echo "\033[32mStart unpacking Evolution CMS\033[0m\n";
        echo "half a line";
        fwrite(STDERR, "Install Composer dependencies\n");
        usleep(2600000);
        echo " finished\n  - Installing vendor/package (1.0.0)\n";
        echo "Run Core Migrations\n";
        exit(3);
        PHP);
    $reports = [];
    $process = invokeSiteUpdateFlowMethod($service, 'createProcess', [[]]);

    $exitCode = invokeSiteUpdateFlowMethod($service, 'runStreaming', [
        $process,
        function ($step, $progress, $message, $level, array $context = []) use (&$reports) {
            $reports[] = [$progress, $message, $level];
        },
        40,
        ['target_ref' => '3.5.x'],
    ]);

    $logged = array_values(array_filter($reports, fn ($report) => $report[2] !== null));
    $heartbeats = array_values(array_filter($reports, fn ($report) => $report[2] === null));

    expect($exitCode)->toBe(3)
        ->and($logged)->toBe([
            [45, 'Start download Evolution CMS 3.5.x', 'info'],
            [50, 'Start unpacking Evolution CMS', 'info'],
            [62, 'Install Composer dependencies', 'info'],
            // the stdout line waited for its end instead of taking in the stderr line
            [62, 'half a line finished', 'info'],
            [62, '- Installing vendor/package (1.0.0)', 'info'],
            [70, 'Run Core Migrations', 'info'],
        ])
        ->and($heartbeats)->not->toBeEmpty()
        ->and($heartbeats[0][0])->toBe(62)
        ->and($heartbeats[0][1])->toMatch('/^Install Composer dependencies \(0:0\d\)$/');
});

test('site update flow passes a heartbeat to the worker without a log line', function () {
    $service = new SiteUpdateFlowService(sys_get_temp_dir());
    $seen = [];

    invokeSiteUpdateFlowMethod($service, 'report', [
        function ($step, $progress, $message, $level) use (&$seen) {
            $seen[] = $level;
        },
        'site_update', 62, 'Install Composer dependencies (0:15)', null,
    ]);
    invokeSiteUpdateFlowMethod($service, 'report', [
        function ($step, $progress, $message, $level) use (&$seen) {
            $seen[] = $level;
        },
        'site_update', 62, 'Install Composer dependencies', 'info',
    ]);

    expect($seen)->toBe([null, 'info']);
});
