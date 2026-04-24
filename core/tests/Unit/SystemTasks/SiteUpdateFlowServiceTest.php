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
    $instanceMethod->setAccessible(true);

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
