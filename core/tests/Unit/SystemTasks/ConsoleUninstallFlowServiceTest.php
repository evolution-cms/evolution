<?php

use EvolutionCMS\Services\SystemTasks\ConsoleUninstallFlowService;

if (!defined('EVO_CORE_PATH')) {
    define('EVO_CORE_PATH', dirname(__DIR__, 3) . '/');
}

function invokeConsoleUninstallMethod(ConsoleUninstallFlowService $service, string $method, array $args = [])
{
    $reflection = new ReflectionClass($service);
    $instanceMethod = $reflection->getMethod($method);
    $instanceMethod->setAccessible(true);

    return $instanceMethod->invokeArgs($service, $args);
}

function removeConsoleUninstallTestTree(string $path): void
{
    if ($path === '' || !file_exists($path)) {
        return;
    }

    if (is_file($path) || is_link($path)) {
        @unlink($path);
        return;
    }

    foreach (scandir($path) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        removeConsoleUninstallTestTree($path . '/' . $entry);
    }

    @rmdir($path);
}

test('console uninstall artisan args preserve positional command structure', function () {
    $service = new ConsoleUninstallFlowService();
    $arguments = invokeConsoleUninstallMethod($service, 'buildArtisanProcessArguments', [
        'package:removerequire',
        [
            'key' => 'evolution-cms/esettings',
            'composer_run' => 1,
        ],
    ]);

    expect($arguments)->toBe([
        PHP_BINARY,
        EVO_CORE_PATH . 'artisan',
        'package:removerequire',
        'evolution-cms/esettings',
        '1',
    ]);
});

test('console uninstall resolves generated provider config files from package composer metadata', function () {
    $service = new ConsoleUninstallFlowService();
    $files = invokeConsoleUninstallMethod($service, 'resolveProviderConfigFiles', [[
        'extra' => [
            'laravel' => [
                'providers' => [
                    'EvolutionCMS\\eTinyMCE\\eTinyMCEServiceProvider',
                    'Vendor\\Package\\MainServiceProvider',
                ],
                'priority' => [
                    'Vendor\\Package\\MainServiceProvider' => 7,
                ],
            ],
        ],
    ]]);

    expect($files)->toContain(
        EVO_CORE_PATH . 'custom/config/app/providers/eTinyMCEServiceProvider.php',
        EVO_CORE_PATH . 'custom/config/app/providers/007_MainServiceProvider.php',
        EVO_CORE_PATH . 'custom/config/app/providers/MainServiceProvider.php'
    );
});

test('console uninstall resolves generated alias config files from package composer metadata', function () {
    $service = new ConsoleUninstallFlowService();
    $files = invokeConsoleUninstallMethod($service, 'resolveAliasConfigFiles', [[
        'extra' => [
            'laravel' => [
                'aliases' => [
                    'sTask' => 'Seiger\\sTask\\Facades\\sTask',
                    'TinyMCE' => 'EvolutionCMS\\eTinyMCE\\Facades\\TinyMCE',
                ],
            ],
        ],
    ]]);

    expect($files)->toBe([
        EVO_CORE_PATH . 'custom/config/app/aliases/sTask.php',
        EVO_CORE_PATH . 'custom/config/app/aliases/TinyMCE.php',
    ]);
});

test('console uninstall captures cleanup files from snapshot even when composer metadata is incomplete', function () {
    $service = new ConsoleUninstallFlowService();
    $taskRoot = sys_get_temp_dir() . '/console-uninstall-snapshot-' . uniqid();
    $providerFile = $taskRoot . '/custom/config/app/providers/LaravelFilemanagerServiceProvider.php';
    $servicesFile = $taskRoot . '/storage/bootstrap/services.php';

    mkdir(dirname($providerFile), 0777, true);
    mkdir(dirname($servicesFile), 0777, true);
    file_put_contents($providerFile, '<?php return TestProvider::class;');
    file_put_contents($servicesFile, '<?php return [];');

    $serviceReflection = new ReflectionClass($service);
    foreach ([
        'providersDir' => $taskRoot . '/custom/config/app/providers/',
        'aliasesDir' => $taskRoot . '/custom/config/app/aliases/',
        'servicesCache' => $servicesFile,
    ] as $property => $value) {
        $instanceProperty = $serviceReflection->getProperty($property);
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue($service, $value);
    }

    $artifacts = invokeConsoleUninstallMethod($service, 'captureDiscoveryArtifacts', [
        'vendor/package',
        [
            'cleanup_files' => [
                $providerFile,
            ],
        ],
    ]);

    expect($artifacts)->toHaveKey($providerFile)
        ->and($artifacts)->toHaveKey($servicesFile);

    removeConsoleUninstallTestTree($taskRoot);
});

test('console uninstall purges stale provider and alias artifacts before composer remove', function () {
    $service = new ConsoleUninstallFlowService();
    $taskRoot = sys_get_temp_dir() . '/console-uninstall-prune-' . uniqid();
    $providersDir = $taskRoot . '/custom/config/app/providers/';
    $aliasesDir = $taskRoot . '/custom/config/app/aliases/';
    $servicesFile = $taskRoot . '/storage/bootstrap/services.php';
    $aliasesCache = $taskRoot . '/includes/aliases.inc.php';

    mkdir($providersDir, 0777, true);
    mkdir($aliasesDir, 0777, true);
    mkdir(dirname($servicesFile), 0777, true);
    mkdir(dirname($aliasesCache), 0777, true);

    $staleProvider = $providersDir . 'DocListerServiceProvider.php';
    $freshProvider = $providersDir . 'DateTimeProvider.php';
    $staleAlias = $aliasesDir . 'DocLister.php';
    $missingProviderClass = 'Vendor\\Missing\\GhostProvider' . str_replace('.', '', uniqid('', true));
    $missingAliasClass = 'Vendor\\Missing\\GhostAlias' . str_replace('.', '', uniqid('', true));

    file_put_contents($staleProvider, "<?php\nreturn '" . addslashes($missingProviderClass) . "';");
    file_put_contents($freshProvider, "<?php\nreturn DateTime::class;");
    file_put_contents($staleAlias, "<?php\nreturn '" . addslashes($missingAliasClass) . "';");
    file_put_contents($servicesFile, '<?php return [];');
    file_put_contents($aliasesCache, '<?php return [];');

    $serviceReflection = new ReflectionClass($service);
    foreach ([
        'providersDir' => $providersDir,
        'aliasesDir' => $aliasesDir,
        'servicesCache' => $servicesFile,
        'legacyAliasesCache' => $aliasesCache,
    ] as $property => $value) {
        $instanceProperty = $serviceReflection->getProperty($property);
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue($service, $value);
    }

    invokeConsoleUninstallMethod($service, 'purgeInvalidDiscoveryArtifacts', [null]);
    clearstatcache(true, $staleProvider);
    clearstatcache(true, $staleAlias);
    clearstatcache(true, $servicesFile);
    clearstatcache(true, $aliasesCache);
    clearstatcache(true, $freshProvider);

    expect(file_exists($staleProvider))->toBeFalse()
        ->and(file_exists($staleAlias))->toBeFalse()
        ->and(file_exists($servicesFile))->toBeFalse()
        ->and(file_exists($aliasesCache))->toBeFalse()
        ->and(file_exists($freshProvider))->toBeTrue();

    removeConsoleUninstallTestTree($taskRoot);
});
