<?php

// Constants are process-global, so every case runs in its own PHP process.
function sessionAtShutdown(string $cookie = '0', string $manager = '0', string $worker = '0', string $setCookie = '0', string $sync = 'shutdown', string $console = '0'): array
{
    $out = evoRunPhp(dirname(__DIR__) . '/Mocks/session_shutdown_worker.php', [$cookie, $manager, $worker, $setCookie, $sync, $console]);
    $result = json_decode($out, true);
    expect($result)->toBeArray("worker output: $out");

    return $result;
}

test('a page without a session cookie in or out saves no session nobody can read back', function () {
    expect(sessionAtShutdown()['saves'])->toBe([]);
});

test('a session is saved when the visitor can come back to it', function (array $case) {
    $result = sessionAtShutdown(...$case);

    expect($result['saves'])->toBe(['save'])
        ->and($result['data'])->toBe(['visitor' => 'x']);
})->with([
    'the request carries the cookie' => [['cookie' => '1']],
    'the response sets the cookie' => [['setCookie' => '1']],
    'the manager' => [['manager' => '1']],
    'a worker, which emits the cookie after its shutdown jobs' => [['worker' => '1']],
    'the middleware path, where StartSession sends the cookie' => [['sync' => 'direct']],
]);

test('a console command saves no session: no cookie can bring it back', function (array $case) {
    $result = sessionAtShutdown(...$case + ['console' => '1']);

    expect($result['saves'])->toBe([])
        // the command still works with its session in the process
        ->and($result['data'])->toBe(['visitor' => 'x']);
})->with([
    'artisan, which runs in manager mode' => [['manager' => '1']],
    'a script outside the manager' => [[]],
    'whatever headers it printed' => [['manager' => '1', 'setCookie' => '1']],
]);

test('a worker keeps saving its sessions whatever the SAPI says', function () {
    expect(sessionAtShutdown(worker: '1', console: '1')['saves'])->toBe(['save']);
});

test('native session keys that start with an underscore are kept, the store\'s own keys stay the store\'s', function () {
    $out = evoRunPhp(dirname(__DIR__) . '/Mocks/session_native_keys_worker.php', []);
    $result = json_decode($out, true);
    expect($result)->toBeArray("worker output: $out");

    expect($result['seen']['_flashMessages'])->toBe(['success' => ['Goodbye!']])
        ->and($result['data']['_flashMessages'])->toBe(['success' => ['Thanks']])
        ->and($result['data'])->not->toHaveKey('_native_stale')
        ->and($result['data']['_token'])->toBe('new-token')
        ->and($result['data']['visitor'])->toBe('x');
});

test('the session is synced before the shutdown decides, and only the shutdown registers the check', function () {
    $proxy = file_get_contents(dirname(__DIR__, 2) . '/functions/session_proxy.php');
    $middleware = file_get_contents(dirname(__DIR__, 2) . '/src/Middleware/SessionProxy.php');

    expect($proxy)->toContain("evo_register_shutdown([self::class, 'syncBackAtShutdown'])")
        ->and($middleware)->toContain('\EvoSessionProxy::syncBack();');
});
