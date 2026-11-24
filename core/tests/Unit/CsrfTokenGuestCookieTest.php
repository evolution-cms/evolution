<?php

use Symfony\Component\Process\Process;

/** @return array{cookies: string[], body: array} */
function guestPage(string $call, string $cookie = ''): array
{
    static $server = null, $address = null;

    if ($server === null) {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $address = stream_socket_get_name($socket, false);
        fclose($socket);

        $server = new Process([PHP_BINARY, '-d', 'opcache.enable=0', '-S', $address, '-t', dirname(__DIR__) . '/Mocks']);
        $server->start();

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $connection = @fsockopen('127.0.0.1', (int) substr(strrchr($address, ':'), 1), $code, $message, 0.1);
            if ($connection !== false) {
                fclose($connection);
                break;
            }
            usleep(50000);
        }
    }

    $context = stream_context_create(['http' => [
        'ignore_errors' => true,
        'header' => $cookie === '' ? '' : "Cookie: $cookie\r\n",
    ]]);
    $stream = fopen("http://$address/csrf_cookie_page.php?call=$call", 'r', false, $context);
    $out = (string) stream_get_contents($stream);
    $headers = stream_get_meta_data($stream)['wrapper_data'];
    fclose($stream);
    $body = json_decode($out, true);
    expect($body)->toBeArray("page output: $out");

    return [
        'cookies' => array_values(preg_grep('/^Set-Cookie: evo_session=/i', $headers)),
        'body' => $body,
    ];
}

test('a guest who is handed a token can come back with its session', function () {
    $page = guestPage('token');

    expect($page['cookies'])->toHaveCount(1)
        ->and($page['cookies'][0])->toContain('evo_session=guestsessionid')
        ->and($page['body']['token'])->toBe('store-token')
        ->and($page['body']['saves'])->toBe(1)
        // the session stays open for the rest of the page
        ->and($page['body']['data']['later'])->toBe('written after the token');
});

test('a page without a token still gives a guest no cookie and stores no session', function () {
    $page = guestPage('none');

    expect($page['cookies'])->toBe([])
        ->and($page['body']['saves'])->toBe(0);
});

test('a visitor who came with the cookie is not sent another', function () {
    $page = guestPage('token', 'evo_session=guestsessionid');

    expect($page['cookies'])->toBe([])
        ->and($page['body']['saves'])->toBe(1);
});

test('a redirect after the token sends the cookie once', function () {
    expect(guestPage('redirect')['cookies'])->toHaveCount(1);
});
