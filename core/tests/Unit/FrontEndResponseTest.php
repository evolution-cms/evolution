<?php

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Process\Process;

/** The site of this file, served once; $stop takes it down again. */
function frontEndSite(bool $stop = false): ?string
{
    static $server = null, $address = null, $sessions = null;

    if ($stop) {
        if ($server !== null) {
            $server->stop(1);
            array_map('unlink', glob("$sessions/*") ?: []);
            rmdir($sessions);
        }
        return $server = null;
    }
    if ($server !== null) {
        return "http://$address";
    }

    $sessions = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-front-end-' . bin2hex(random_bytes(8));
    mkdir($sessions);

    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $address = stream_socket_get_name($socket, false);
    fclose($socket);

    $mocks = dirname(__DIR__) . '/Mocks';
    $server = new Process([
        PHP_BINARY, '-d', 'opcache.enable=0',
        // the native session that mirrors the store must not land among those of the machine
        '-d', "session.save_path=$sessions",
        '-S', $address, '-t', $mocks, "$mocks/front_end_page.php",
    ], null, ['FRONT_END_SESSIONS' => $sessions]);
    $server->start();

    for ($attempt = 0; $attempt < 100; $attempt++) {
        $connection = @fsockopen('127.0.0.1', (int) substr(strrchr($address, ':'), 1), $code, $message, 0.1);
        if ($connection !== false) {
            fclose($connection);
            break;
        }
        usleep(50000);
    }

    return "http://$address";
}

/** A visitor who has never been here. */
function frontEndVisitor(): Client
{
    return new Client([
        'base_uri' => frontEndSite(),
        'cookies' => new CookieJar(),
        'http_errors' => false,
        'allow_redirects' => false,
        'timeout' => 30,
    ]);
}

/** @return array{status: int, cookies: string[], body: string, token: string} */
function frontEndGet(Client $visitor, string $path): array
{
    $response = $visitor->get($path);
    $body = (string) $response->getBody();
    preg_match('/name="_token" value="([^"]+)"/', $body, $match);

    return [
        'status' => $response->getStatusCode(),
        'cookies' => array_values(preg_grep('/^evo_session=/', $response->getHeader('Set-Cookie'))),
        'body' => $body,
        'token' => $match[1] ?? '',
    ];
}

function frontEndPost(Client $visitor, string $path, string $token): string
{
    return (string) $visitor->post($path, ['form_params' => ['_token' => $token]])->getBody();
}

afterAll(fn () => frontEndSite(true));

test('the first form of a new visitor is accepted, with one session cookie', function (string $path, string $action) {
    $visitor = frontEndVisitor();
    $page = frontEndGet($visitor, $path);

    expect($page['status'])->toBe(200, $page['body'])
        ->and($page['cookies'])->toHaveCount(1)
        ->and($page['token'])->not->toBe('')
        ->and(frontEndPost($visitor, $action, $page['token']))->toBe('accepted');
})->with([
    'a routed response' => ['/routed', '/routed'],
    'a plain document' => ['/document', '/document'],
    'a plain page that ends in exit' => ['/document?do=exit', '/document'],
    'a route that ends in exit' => ['/routed-exit', '/routed'],
]);

test('a token of another visitor is rejected', function (string $path) {
    $token = frontEndGet(frontEndVisitor(), $path)['token'];
    $other = frontEndVisitor();
    frontEndGet($other, $path);

    expect($token)->not->toBe('')
        ->and(frontEndPost($other, $path, $token))->toBe('rejected');
})->with(['/routed', '/document']);

test('the session stays open for the rest of the page that asked for the cookie', function () {
    $visitor = frontEndVisitor();
    frontEndGet($visitor, '/document');

    expect(frontEndGet($visitor, '/document?do=later')['body'])->toBe('written after the token');
});

test('a visitor who came with the cookie is sent no other by a document', function () {
    $visitor = frontEndVisitor();
    $first = frontEndGet($visitor, '/document');
    $second = frontEndGet($visitor, '/document');

    expect($second['cookies'])->toBe([])
        ->and($second['token'])->toBe($first['token']);
});

test('a document without a token gives a guest no cookie', function () {
    $page = frontEndGet(frontEndVisitor(), '/document?do=none');

    expect($page['body'])->toBe('no form here')
        ->and($page['cookies'])->toBe([]);
});

test('NO_SESSION sends no cookie with a token and stores no session', function (string $path) {
    $visitor = frontEndVisitor();
    $page = frontEndGet($visitor, "$path?no_session=1");

    expect($page['status'])->toBe(200, $page['body'])
        ->and($page['cookies'])->toBe([])
        ->and($page['token'])->not->toBe('')
        ->and(frontEndPost($visitor, "$path?no_session=1", $page['token']))->toBe('rejected');
})->with(['/routed', '/document']);

test('abort() answers with its status under NO_SESSION', function () {
    expect(frontEndVisitor()->get('/routed-abort/403?no_session=1')->getStatusCode())->toBe(403);
});

test('a redirect after the token sends the cookie once', function () {
    $page = frontEndGet(frontEndVisitor(), '/document?do=redirect');

    expect($page['status'])->toBe(302, $page['body'])
        ->and($page['cookies'])->toHaveCount(1);
});

test('abort() in a route answers with its status, and nothing printed before it', function (int $status) {
    $response = frontEndVisitor()->get("/routed-abort/$status");

    expect((string) $response->getBody())->toBe('refused <b>here</b>')
        ->and($response->getStatusCode())->toBe($status)
        ->and($response->getHeaderLine('Content-Type'))->toStartWith('text/plain')
        ->and($response->getHeaderLine('X-Reason'))->toBe('test');
})->with([403, 419, 503]);

test('abort() with a response answers with that response', function () {
    $response = frontEndVisitor()->get('/routed-abort/response');

    expect($response->getStatusCode())->toBe(409)
        ->and((string) $response->getBody())->toBe('a response of its own');
});

test('abort(404) in a route leaves the request to the parser', function () {
    expect((string) frontEndVisitor()->get('/routed-abort/404?do=none')->getBody())->toBe('no form here');
});

test('abort(404) in a document shows the error page, also when a route left the request to it', function (string $path) {
    $response = frontEndVisitor()->get("$path?do=abort&status=404");

    expect((string) $response->getBody())->toBe('the error page of the site')
        ->and($response->getStatusCode())->toBe(404);
})->with(['/document', '/routed-abort/404']);

test('abort() in a document answers with its status', function (string $path) {
    $response = frontEndVisitor()->get("$path?do=abort&status=403");

    expect((string) $response->getBody())->toBe('Forbidden')
        ->and($response->getStatusCode())->toBe(403);
})->with(['/document', '/routed-abort/404']);

test('abort_if() and abort_unless() abort on their condition only', function () {
    abort_if(false, 403);
    abort_unless(true, 403);

    expect(fn () => abort_if(true, 403, 'no'))->toThrow(HttpException::class, 'no')
        ->and(fn () => abort_unless(false, 404))->toThrow(NotFoundHttpException::class);
});
