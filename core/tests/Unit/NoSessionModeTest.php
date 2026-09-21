<?php

use EvolutionCMS\Middleware\SessionProxy;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;

// Constants are process-global, so every combination boots in its own PHP process.
function noSessionRun(string $noSession, string $manager): array
{
    $worker = dirname(__DIR__) . '/Mocks/no_session_worker.php';
    $out = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($worker) . " $noSession $manager 2>&1");
    $result = json_decode((string) $out, true);
    expect($result)->toBeArray("worker output: $out");

    return $result;
}

test('NO_SESSION on the front end opens no store, writes nothing and drops the session middleware', function () {
    $r = noSessionRun('1', '0');

    expect($r['disabled'])->toBeTrue()
        ->and($r['store'])->toBe([])
        ->and($r['sessionIsArray'])->toBeTrue()
        ->and($r['middleware'])->toBe([SubstituteBindings::class]);
});

test('the manager keeps its session under NO_SESSION', function () {
    $r = noSessionRun('1', '1');

    expect($r['disabled'])->toBeFalse()
        ->and($r['store'])->toBe(['start', 'put', 'save'])
        ->and($r['middleware'])->toBe([StartSession::class, SessionProxy::class, SubstituteBindings::class]);
});

test('sessions stay on when NO_SESSION is absent or false', function () {
    foreach (['-', '0'] as $value) {
        $r = noSessionRun($value, '0');
        expect($r['disabled'])->toBeFalse()->and($r['store'])->toBe(['start', 'put', 'save']);
    }
});

test('front-end entry points and bootstrap honour the NO_SESSION gate', function () {
    $root = dirname(__DIR__, 3);
    expect(file_get_contents("$root/core/bootstrap.php"))->toContain('EvoSessionProxy::disabled()')
        ->and(file_get_contents("$root/core/src/Core.php"))->toContain('EvoSessionProxy::filterMiddleware(')
        ->and(file_get_contents("$root/core/src/Providers/TracyServiceProvider.php"))->toContain('EvoSessionProxy::disabled()');
});
