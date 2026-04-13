<?php

use EvolutionCMS\Services\SystemTasks\ConsoleInstallFlowService;

function invokeConsoleInstallFlowMethod(ConsoleInstallFlowService $service, string $method, array $args = [])
{
    $reflection = new ReflectionClass($service);
    $instanceMethod = $reflection->getMethod($method);
    $instanceMethod->setAccessible(true);

    return $instanceMethod->invokeArgs($service, $args);
}

test('buildArtisanProcessArguments preserves positional args flags and key value options', function () {
    if (!defined('EVO_CORE_PATH')) {
        define('EVO_CORE_PATH', dirname(__DIR__, 3) . '/');
    }

    $service = new ConsoleInstallFlowService();

    $arguments = invokeConsoleInstallFlowMethod($service, 'buildArtisanProcessArguments', [
        'package:installrequire',
        [
            'key' => 'evolution-cms/ecodemirror',
            'value' => 'dev-main',
            '--ansi' => true,
            '--profile' => false,
            '--provider' => 'Vendor\\Package\\ServiceProvider',
        ],
    ]);

    expect($arguments[0])->toBe(PHP_BINARY)
        ->and($arguments[1])->toContain('/core/artisan')
        ->and($arguments[2])->toBe('package:installrequire')
        ->and($arguments)->toContain('evolution-cms/ecodemirror')
        ->and($arguments)->toContain('dev-main')
        ->and($arguments)->toContain('--ansi')
        ->and($arguments)->toContain('--provider=Vendor\\Package\\ServiceProvider')
        ->and($arguments)->not->toContain('--profile');
});

test('extractProviders merges laravel and evolution providers without duplicates', function () {
    $service = new ConsoleInstallFlowService();

    $providers = invokeConsoleInstallFlowMethod($service, 'extractProviders', [[
        'extra' => [
            'laravel' => [
                'providers' => [
                    'Vendor\\Package\\PrimaryServiceProvider',
                ],
            ],
            'evolution' => [
                'providers' => [
                    'Vendor\\Package\\PrimaryServiceProvider',
                    'Vendor\\Package\\SecondaryServiceProvider',
                ],
            ],
        ],
    ]]);

    expect($providers)->toBe([
        'Vendor\\Package\\PrimaryServiceProvider',
        'Vendor\\Package\\SecondaryServiceProvider',
    ]);
});
