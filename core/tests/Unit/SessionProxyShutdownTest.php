<?php

// Constants are process-global, so every case runs in its own PHP process.
function sessionAtShutdown(string $cookie = '0', string $manager = '0', string $setCookie = '0', string $sync = 'shutdown'): array
{
    $out = evoRunPhp(dirname(__DIR__) . '/Mocks/session_shutdown_worker.php', [$cookie, $manager, $setCookie, $sync]);
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
    'the middleware path, where StartSession sends the cookie' => [['sync' => 'direct']],
]);

test('the session is synced before the shutdown decides, and only the shutdown registers the check', function () {
    $proxy = file_get_contents(dirname(__DIR__, 2) . '/functions/session_proxy.php');
    $middleware = file_get_contents(dirname(__DIR__, 2) . '/src/Middleware/SessionProxy.php');

    expect($proxy)->toContain("register_shutdown_function([self::class, 'syncBackAtShutdown'])")
        ->and($middleware)->toContain('\EvoSessionProxy::syncBack();');
});
