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
