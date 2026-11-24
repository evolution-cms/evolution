<?php

// Constants are process-global, so every case runs in its own PHP process.
function sessionAtShutdown(string $cookie = '0', string $manager = '0', string $setCookie = '0', string $sync = 'shutdown', string $console = '0'): array
{
    $out = evoRunPhp(dirname(__DIR__) . '/Mocks/session_shutdown_worker.php', [$cookie, $manager, $setCookie, $sync, $console]);
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

test('native session keys that start with an underscore are kept, the store\'s own keys stay the store\'s', function () {
    $out = evoRunPhp(dirname(__DIR__) . '/Mocks/session_native_keys_worker.php', []);
    $result = json_decode($out, true);
    expect($result)->toBeArray("worker output: $out");

    expect($result['seen']['_flashMessages'])->toBe(['success' => ['Goodbye!']])
        ->and($result['data']['_flashMessages'])->toBe(['success' => ['Thanks']])
        ->and($result['data'])->not->toHaveKey('_native_stale')
        ->and($result['data']['_native_new'])->toBe(['queued' => 1])
        ->and($result['data']['_token'])->toBe('new-token')
        ->and($result['data']['visitor'])->toBe('x');
});

test('an underscore key only Laravel created, changed or removed during the request keeps the store value', function () {
    $out = evoRunPhp(dirname(__DIR__) . '/Mocks/session_native_keys_worker.php', []);
    $result = json_decode($out, true);
    expect($result)->toBeArray("worker output: $out");

    expect($result['seen']['_laravel_changed'])->toBe('before')
        ->and($result['seen']['_laravel_gone'])->toBe('remove me in Laravel')
        ->and($result['data']['_laravel_new'])->toBe('created in Laravel')
        ->and($result['data']['_laravel_changed'])->toBe('after')
        ->and($result['data'])->not->toHaveKey('_laravel_gone');
});

function sessionOfRequests(string $native, string $routed): array
{
    $out = evoRunPhp(dirname(__DIR__) . '/Mocks/session_requests_worker.php', [$native, $routed]);
    $result = json_decode($out, true);
    expect($result)->toBeArray("worker output: $out");

    return $result;
}

dataset('kinds of requests', [
    'routes, native session file' => ['1', '1'],
    'routes, worker' => ['0', '1'],
    'pages, native session file' => ['1', '0'],
    'pages, worker' => ['0', '0'],
]);

test('a key with a dot comes back under its own name', function (string $native, string $routed) {
    ['second' => $second, 'third' => $third] = sessionOfRequests($native, $routed);

    expect($second['seen']['shop.cart'] ?? null)->toBe(['a.b' => 1])
        ->and($second['seen']['shop'])->toBe('open')
        ->and($second['seen']['_native.dotted'])->toBe('native')
        ->and($second['seen']['gone.soon'])->toBe('x')
        // session('shop.cart') of Laravel code reads it as well
        ->and($second['result']['cart'])->toBe(['a.b' => 1])
        ->and($third['seen']['shop.cart'])->toBe(['a.b' => 2])
        ->and(array_intersect(['gone', 'gone.soon'], array_keys($third['seen'])))->toBe([]);
})->with('kinds of requests');

test('the creation time of a session is kept from one request to the next', function (string $native, string $routed) {
    ['third' => $third] = sessionOfRequests($native, $routed);

    expect($third['seen']['evo.session.created.time'])->toBe(1000)
        ->and(array_intersect(['evo'], array_keys($third['seen'])))->toBe([]);
})->with('kinds of requests');

test('what Laravel code puts, changes and forgets is kept', function (string $native, string $routed) {
    ['second' => $second, 'third' => $third] = sessionOfRequests($native, $routed);

    expect($second['seen']['laravel'] ?? null)->toBe('put')
        ->and($second['result']['laravel'] ?? null)->toBe('put')
        ->and($second['result']['nested'] ?? null)->toBe(['key' => 'value'])
        ->and($third['seen']['both'])->toBe('laravel 2')
        ->and(array_intersect(['forgotten'], array_keys($third['seen'])))->toBe([]);
})->with('kinds of requests');

test('what native code changes is kept, an object changed in place included', function (string $native, string $routed) {
    $result = sessionOfRequests($native, $routed);

    expect($result['object'])->toBe(['in place' => 2])
        ->and($result['third']['result'])->toBe('laravel 2');
})->with('kinds of requests');

test('flash data lasts for exactly one more request', function (string $native, string $routed) {
    ['second' => $second, 'third' => $third] = sessionOfRequests($native, $routed);

    expect($second['result']['notice'] ?? null)->toBe('saved')
        ->and($second['result']['_old_input'] ?? null)->toBe(['name' => 'typed'])
        ->and(array_intersect(['notice', '_old_input'], array_keys($third['seen'])))->toBe([]);
})->with('kinds of requests');

test('an invalidated session stays empty', function (string $native, string $routed) {
    ['third' => $third, 'fourth' => $fourth, 'fifth' => $fifth] = sessionOfRequests($native, $routed);

    expect($fourth['id'])->not->toBe($third['id'])
        ->and(array_intersect(['shop', 'shop.cart', 'both', 'laravel', 'object'], array_keys($fifth['seen'])))->toBe([]);
})->with('kinds of requests');

test('the session is synced before the shutdown decides, and only the shutdown registers the check', function () {
    $proxy = file_get_contents(dirname(__DIR__, 2) . '/functions/session_proxy.php');
    $middleware = file_get_contents(dirname(__DIR__, 2) . '/src/Middleware/SessionProxy.php');

    expect($proxy)->toContain("register_shutdown_function([self::class, 'syncBackAtShutdown'])")
        ->and($middleware)->toContain('\EvoSessionProxy::syncBack();');
});
