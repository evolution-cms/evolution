<?php namespace Tests\Unit\SystemTasks;

use EvolutionCMS\Services\SystemTasks\ConsoleUninstall\ConsoleUninstallRegistry;

test('console uninstall registry normalizes package names and resolves class handlers lazily', function () {
    $registry = new ConsoleUninstallRegistry([
        'Evolution-Cms/ePasskeys' => TestConsoleUninstallHandler::class,
    ]);

    expect($registry->has('evolution-cms/epasskeys'))->toBeTrue()
        ->and($registry->has('EVOLUTION-CMS/EPASSKEYS'))->toBeTrue()
        ->and($registry->resolve('evolution-cms/epasskeys'))->toBeInstanceOf(TestConsoleUninstallHandler::class);
});

test('console uninstall registry delegates preview and apply to registered handlers', function () {
    $handler = new TestConsoleUninstallHandler();
    $registry = new ConsoleUninstallRegistry([
        'evolution-cms/etinymce' => $handler,
    ]);

    $plan = $registry->preview('evolution-cms/etinymce', ['mode' => 'preview']);
    $result = $registry->apply('evolution-cms/etinymce', $plan, ['mode' => 'apply']);

    expect($plan->toArray())->toMatchArray([
        'package_name' => 'evolution-cms/etinymce',
        'supported' => true,
    ])->and($result->toArray())->toMatchArray([
        'ok' => true,
        'package_name' => 'evolution-cms/etinymce',
    ]);
});

test('console uninstall registry returns explicit unsupported payloads for unknown packages', function () {
    $registry = new ConsoleUninstallRegistry();

    $plan = $registry->preview('evolution-cms/unknown');
    $result = $registry->apply('evolution-cms/unknown', $plan);

    expect($plan->toArray())->toMatchArray([
        'package_name' => 'evolution-cms/unknown',
        'supported' => false,
    ])->and($result->toArray())->toMatchArray([
        'ok' => false,
        'package_name' => 'evolution-cms/unknown',
        'error_code' => 'CONSOLE_UNINSTALL_NOT_SUPPORTED',
    ]);
});
