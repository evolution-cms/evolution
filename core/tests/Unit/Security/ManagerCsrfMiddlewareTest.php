<?php

use EvolutionCMS\Middleware\VerifyCsrfToken;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Response;

/*
|--------------------------------------------------------------------------
| CSRF enforcement in the Manager
|--------------------------------------------------------------------------
|
| Before 3.5.8 the middleware only compared the token when the request happened to carry one,
| so a cross-site request that omitted `_token` passed straight through to the action
| dispatcher. These tests drive the real middleware with real requests and assert that it now
| fails closed, while leaving the login flow and ordinary navigation alone.
|
*/

/**
 * Minimal stand-in for the response factory, so the middleware can be exercised without
 * booting the framework. It records what the middleware asked for.
 */
final class CsrfResponseFactoryStub
{
    public function json($data, $status = 200)
    {
        return ['kind' => 'json', 'data' => $data, 'status' => $status];
    }

    public function make($content = '', $status = 200, array $headers = [])
    {
        return ['kind' => 'text', 'data' => $content, 'status' => $status, 'headers' => $headers];
    }
}

beforeEach(function () {
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication(new Container());
    Response::swap(new CsrfResponseFactoryStub());

    $_SESSION = ['mgrValidated' => 1, '_token' => str_repeat('a', 40)];
    $_GET = [];
    $_POST = [];
});

afterEach(function () {
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication(null);
    $_SESSION = [];
    $_GET = [];
    $_POST = [];
});

/**
 * Builds a request and mirrors its parameters into the superglobals the Manager dispatcher
 * reads, so the middleware resolves the action exactly as the real request would.
 */
function csrfRequest(string $method, array $params = [], array $server = []): Request
{
    $method = strtoupper($method);
    $query = $method === 'GET' ? $params : [];
    $body = $method === 'GET' ? [] : $params;

    $_GET = $query;
    $_POST = $body;

    return Request::create('/manager/index.php', $method, $params, [], [], $server);
}

/**
 * Runs the middleware and reports whether the request reached the application.
 */
function csrfPassed(Request $request): bool
{
    $reached = false;

    $result = (new VerifyCsrfToken())->handle($request, function () use (&$reached) {
        $reached = true;

        return 'next';
    });

    if ($reached) {
        expect($result)->toBe('next');
    }

    return $reached;
}

/*
|--------------------------------------------------------------------------
| The reported vulnerability
|--------------------------------------------------------------------------
*/

it('rejects a state-changing POST that carries no token at all', function () {
    // The reported attack: a cross-site form posting a=109 (save_module) with attacker PHP in
    // `post`, and deliberately no `_token` field.
    $request = csrfRequest('POST', [
        'a' => 109,
        'mode' => 'new',
        'post' => '<?php system($_GET["c"]); ?>',
    ]);

    expect(csrfPassed($request))->toBeFalse();
});

it('rejects a POST whose token does not match the session', function () {
    $request = csrfRequest('POST', ['a' => 109, '_token' => str_repeat('b', 40)]);

    expect(csrfPassed($request))->toBeFalse();
});

it('rejects a state-changing POST when the session holds no token', function () {
    // Failing open on a missing session token would reopen the hole for any session that had
    // not yet rendered a form.
    unset($_SESSION['_token']);

    $request = csrfRequest('POST', ['a' => 109, '_token' => str_repeat('a', 40)]);

    expect(csrfPassed($request))->toBeFalse();
});

it('accepts a POST carrying the session token', function () {
    $request = csrfRequest('POST', ['a' => 109, '_token' => str_repeat('a', 40)]);

    expect(csrfPassed($request))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| GET actions that change state
|--------------------------------------------------------------------------
*/

it('rejects destructive GET actions without a token', function (int $action) {
    $request = csrfRequest('GET', ['a' => $action, 'id' => 3]);

    expect(csrfPassed($request))->toBeFalse();
})->with([
    6,   // delete_content
    61,  // publish_content
    94,  // duplicate_content
    110, // delete_module
    112, // execute_module - runs stored PHP through eval
    116, // delete_eventlog
    303, // delete_tmplvars
]);

it('accepts destructive GET actions carrying the session token', function () {
    $request = csrfRequest('GET', ['a' => 112, 'id' => 3, '_token' => str_repeat('a', 40)]);

    expect(csrfPassed($request))->toBeTrue();
});

it('leaves ordinary GET navigation alone', function (int $action) {
    // Editing forms and list pages are reached constantly by the manager UI and must not need
    // a token, otherwise every bookmark and back button in the panel breaks.
    $request = csrfRequest('GET', ['a' => $action, 'id' => 3]);

    expect(csrfPassed($request))->toBeTrue();
})->with([
    2,   // welcome
    3,   // resource view
    27,  // edit resource
    76,  // element list
    107, // edit module
]);

/*
|--------------------------------------------------------------------------
| The login flow
|--------------------------------------------------------------------------
*/

it('does not require a token before the manager session is validated', function () {
    // The login form is rendered without a manager session, so enforcing here would lock
    // everyone out while closing no cross-site vector.
    unset($_SESSION['mgrValidated']);

    $request = csrfRequest('POST', ['a' => 0, 'username' => 'admin', 'password' => 'secret']);

    expect(csrfPassed($request))->toBeTrue();
});

it('does not require a token when there is no session at all', function () {
    $_SESSION = [];

    $request = csrfRequest('POST', ['a' => 0]);

    expect(csrfPassed($request))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Token transport and response shape
|--------------------------------------------------------------------------
*/

it('accepts the token from the X-CSRF-TOKEN header', function () {
    $request = csrfRequest('POST', ['a' => 109], ['HTTP_X_CSRF_TOKEN' => str_repeat('a', 40)]);

    expect(csrfPassed($request))->toBeTrue();
});

it('answers XHR callers with JSON and page requests with plain text', function () {
    $xhr = csrfRequest('POST', ['a' => 109], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
    $page = csrfRequest('GET', ['a' => 6, 'id' => 3]);

    $middleware = new VerifyCsrfToken();
    $noop = fn () => 'next';

    expect($middleware->handle($xhr, $noop))
        ->toMatchArray(['kind' => 'json', 'status' => 403])
        ->and($middleware->handle($page, $noop))
        ->toMatchArray(['kind' => 'text', 'status' => 403]);
});

it('lets HEAD and OPTIONS through for ordinary navigation', function (string $method) {
    unset($_SESSION['_token']);

    $request = csrfRequest($method, ['a' => 2]);

    expect(csrfPassed($request))->toBeTrue();
})->with(['HEAD', 'OPTIONS']);

it('rejects destructive actions even through HEAD and OPTIONS', function (string $method) {
    expect(csrfPassed(csrfRequest($method, ['a' => 6, 'id' => 3])))->toBeFalse();
})->with(['HEAD', 'OPTIONS']);

it('does not exempt a POST disguised with a safe method override', function () {
    $request = csrfRequest('POST', ['a' => 2], ['HTTP_X_HTTP_METHOD_OVERRIDE' => 'GET']);
    expect(csrfPassed($request))->toBeFalse();
});

it('rejects an explicitly stale token even on ordinary navigation', function () {
    expect(csrfPassed(csrfRequest('GET', ['a' => 2, '_token' => 'old'])))->toBeFalse();
});

it('compares tokens in constant time', function () {
    // A plain string comparison leaks the token a byte at a time under timing analysis.
    $source = file_get_contents(__DIR__ . '/../../../src/Middleware/VerifyCsrfToken.php');

    expect($source)->toContain('hash_equals(');
});
