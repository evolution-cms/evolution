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

    // EVO_CORE_PATH is built from __DIR__, which is backslashed on Windows, so
    // the path arrives as "C:\...\core/artisan". Which separator the host uses
    // is not what this test is about - Symfony's Process takes either.
    $artisanPath = str_replace('\\', '/', $arguments[1]);

    expect($arguments[0])->toBe(PHP_BINARY)
        ->and($artisanPath)->toEndWith('/core/artisan')
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

test('summarizeOutput reads the failure from the tail when asked', function () {
    $service = new ConsoleInstallFlowService();

    $output = implode("\n", [
        'Evolution CMS 3.5.8',
        'Lock file operations: 1 install, 62 updates, 0 removals',
        '  - Upgrading composer/ca-bundle (1.5.13 => 1.5.14)',
        'Your requirements could not be resolved to an installable set of packages.',
        '  - elcreator/aimage 1.0.0 requires ext-imagick * -> it is missing from your system.',
    ]);

    expect(invokeConsoleInstallFlowMethod($service, 'summarizeOutput', [$output]))
        ->toStartWith('Lock file operations')
        ->and(invokeConsoleInstallFlowMethod($service, 'summarizeOutput', [$output, true]))
        ->toContain('ext-imagick');
});

/**
 * Flow service that records the artisan commands it would run instead of running them.
 */
final class RecordingConsoleInstallFlowService extends ConsoleInstallFlowService
{
    public array $commands = [];

    protected function runArtisanCommand($command, array $arguments, $step, $progress, $message, ?callable $report = null)
    {
        $this->commands[] = [$command, $arguments];
    }

    protected function getPublishProviders($packageName)
    {
        return [];
    }
}

function installRequireArgumentsFor(array $payload): array
{
    if (!defined('EVO_CORE_PATH')) {
        define('EVO_CORE_PATH', dirname(__DIR__, 3) . '/');
    }

    $task = new \EvolutionCMS\Models\SystemCliTask();
    $task->target = $payload['composer_name'];
    $task->requested_version = $payload['resolved_version'];
    $task->payload_json = $payload;

    $service = new RecordingConsoleInstallFlowService();
    $service->execute($task);

    return $service->commands[0][1];
}

test('an uploaded archive is installed without updating installed dependencies', function () {
    $arguments = installRequireArgumentsFor([
        'composer_name' => 'evodemo/hello-evo',
        'resolved_version' => '1.0.0',
        'composer_version' => '1.0.0',
        'source_kind' => 'artifact',
    ]);

    expect($arguments['key'])->toBe('evodemo/hello-evo')
        ->and($arguments['value'])->toBe('1.0.0')
        ->and($arguments['--keep-dependencies'])->toBeTrue();
});

test('a catalog package still updates its dependencies', function () {
    $arguments = installRequireArgumentsFor([
        'composer_name' => 'seiger/sgallery',
        'resolved_version' => 'v1.5.2',
        'composer_version' => 'v1.5.2',
        'source_kind' => 'console',
    ]);

    expect($arguments['--keep-dependencies'])->toBeFalse();
});
