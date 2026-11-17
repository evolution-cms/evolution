<?php

/*
| The dashboard update button posts to index.php?a=2&updater_system_task=1. It sent only its own
| updater_task_token, so VerifyCsrfToken answered every click with 403 "token expired".
*/

use EvolutionCMS\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;

$root = dirname(__DIR__, 4);

test('every system task request of the update widget carries the manager CSRF token', function () use ($root) {
    $source = (string) file_get_contents($root . '/assets/plugins/updater/plugin.updater.php');
    $request = substr($source, strpos($source, 'function request(action, data) {'), 1200);

    expect($request)
        ->toContain("var csrfToken = config.csrfToken || (csrfMeta ? csrfMeta.getAttribute('content') : '');")
        ->toContain('data._token = csrfToken;')
        ->and(strpos($request, 'data._token = csrfToken;'))->toBeLessThan(strpos($request, 'body: qs(data)'))
        ->and($source)->toContain("'csrfToken' => \$csrfToken,");
});

test('the middleware lets the widget request through only with the session token', function () {
    $session = $_SESSION ?? null;
    $_SESSION = ['mgrValidated' => 1, '_token' => str_repeat('b', 40)];
    $url = 'https://example.test/manager/index.php?a=2&updater_system_task=1';
    $body = ['updater_system_task' => 1, 'updater_task_action' => 'status', 'updater_task_token' => 'abc'];

    try {
        $passed = (new VerifyCsrfToken())->handle(
            Request::create($url, 'POST', $body + ['_token' => str_repeat('b', 40)]),
            fn () => 'queued'
        );
        expect($passed)->toBe('queued');

        $check = new ReflectionMethod(VerifyCsrfToken::class, 'tokensMatch');
        foreach ([$body, $body + ['_token' => 'stale']] as $sent) {
            expect($check->invoke(new VerifyCsrfToken(), Request::create($url, 'POST', $sent)))->toBeFalse();
        }
    } finally {
        if ($session === null) {
            unset($_SESSION);
        } else {
            $_SESSION = $session;
        }
    }
});
