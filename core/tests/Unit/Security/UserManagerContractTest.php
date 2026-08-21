<?php

use EvolutionCMS\Auth\PipelineUserManager;
use EvolutionCMS\Interfaces\UserManagerInterface;
use EvolutionCMS\Providers\PipelineUserManagerServiceProvider;
use EvolutionCMS\UserManager\Services\UserManager;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;

/**
 * The `UserManager` container key is a documented replacement point: a site swaps the
 * provider and its own class takes over. Before the contract existed, a replacement
 * that dropped a method failed only when a manager clicked the button that needed it.
 */

test('the contract covers every operation the shipped implementation offers', function () {
    $contract = get_class_methods(UserManagerInterface::class);
    $shipped = get_class_methods(UserManager::class);

    $missingFromContract = array_values(array_diff($shipped, $contract));

    expect($missingFromContract)->toBe([])
        ->and(count($contract))->toBe(count($shipped));
});

test('the contract matches the shipped signatures exactly', function () {
    // A stricter signature here — an added type, a dropped default, a return type —
    // would make the package's own class unable to satisfy its own contract.
    $contract = new ReflectionClass(UserManagerInterface::class);
    $shipped = new ReflectionClass(UserManager::class);

    $describe = static function (ReflectionMethod $method): string {
        $parts = [];
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType() ? (string) $parameter->getType() . ' ' : '';
            $default = $parameter->isDefaultValueAvailable()
                ? ' = ' . json_encode($parameter->getDefaultValue())
                : '';
            $parts[] = $type . '$' . $parameter->getName() . $default;
        }

        return $method->getName() . '(' . implode(', ', $parts) . ')'
            . ($method->getReturnType() ? ': ' . $method->getReturnType() : '');
    };

    $mismatched = [];
    foreach ($contract->getMethods() as $method) {
        $theirs = $describe($shipped->getMethod($method->getName()));
        $ours = $describe($method);

        if ($theirs !== $ours) {
            $mismatched[] = $ours . ' != ' . $theirs;
        }
    }

    expect($mismatched)->toBe([]);
});

test('the contract declares no return types, so existing implementations stay valid', function () {
    // The package declares none. A return type in the contract would be a breaking
    // change for every third-party implementation, not a tightening.
    $withReturnTypes = [];

    foreach ((new ReflectionClass(UserManagerInterface::class))->getMethods() as $method) {
        if ($method->hasReturnType()) {
            $withReturnTypes[] = $method->getName();
        }
    }

    expect($withReturnTypes)->toBe([]);
});

test('the default implementation satisfies the contract', function () {
    // PHP enforces this at class load; the test states the intent and fails loudly if
    // the `implements` clause is ever dropped.
    expect((new ReflectionClass(PipelineUserManager::class))->implementsInterface(UserManagerInterface::class))
        ->toBeTrue();
});

test('resolving the contract and the string key yields the same instance', function () {
    $app = new Container();
    $app->instance('config', new Repository(['cms' => ['auth' => ['pipeline' => []]]]));

    (new PipelineUserManagerServiceProvider($app))->register();

    $byKey = $app->make('UserManager');
    $byContract = $app->make(UserManagerInterface::class);

    expect($byKey)->toBeInstanceOf(PipelineUserManager::class)
        ->and($byContract === $byKey)->toBeTrue()
        ->and($byKey instanceof UserManagerInterface)->toBeTrue();
});

test('the contract lives in the core, not in the package it describes', function () {
    // So that a site replacing the package still has something to implement.
    $path = dirname(__DIR__, 3) . '/src/Interfaces/UserManagerInterface.php';

    expect(is_file($path))->toBeTrue()
        ->and((string) file_get_contents($path))->toContain('namespace EvolutionCMS\Interfaces;');
});
