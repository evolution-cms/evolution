<?php

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
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

test('a redirect after the token sends the cookie once', function () {
    $page = frontEndGet(frontEndVisitor(), '/document?do=redirect');

    expect($page['status'])->toBe(302, $page['body'])
        ->and($page['cookies'])->toHaveCount(1);
});
