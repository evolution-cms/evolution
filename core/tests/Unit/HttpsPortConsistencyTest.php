<?php

use EvolutionCMS\ExceptionHandler;

/**
 * HTTPS_PORT is the port TLS is served on: it tells an https request apart. It is not the port a
 * URL may leave out - that stays 443 - and the URL's own port comes from the Host header.
 */
test('the error report shows the url the browser asked for', function (array $server, string $expected) {
    expect(ExceptionHandler::requestUrl($server + ['REQUEST_URI' => '/page?x=1']))->toBe($expected);
})->with([
    'published container port' => [
        ['HTTP_HOST' => 'localhost:8101', 'SERVER_PORT' => '80'],
        'http://localhost:8101/page?x=1',
    ],
    // TLS on 443 at a proxy, php behind it on 8080: the host header has no port, nor has the url
    'proxy in front' => [
        ['HTTP_HOST' => 'site.test', 'SERVER_PORT' => '8080', 'REQUEST_SCHEME' => 'https'],
        'https://site.test/page?x=1',
    ],
    'https on a custom TLS port' => [
        ['HTTP_HOST' => 'site.test:8443', 'SERVER_PORT' => '8443', 'REQUEST_SCHEME' => 'https'],
        'https://site.test:8443/page?x=1',
    ],
]);

test('every https check reads the TLS port from HTTPS_PORT', function () {
    $core = dirname(__DIR__, 2);

    expect((string) file_get_contents($core . '/functions/session_proxy.php'))
        ->toContain("(int) \$_SERVER['SERVER_PORT'] === (int) (defined('HTTPS_PORT') ? HTTPS_PORT : 443)")
        ->and((string) file_get_contents($core . '/functions/preload.php'))
        ->toContain("\$secure = (\$_SERVER['SERVER_PORT'] == HTTPS_PORT);")
        ->and((string) file_get_contents($core . '/src/ExceptionHandler.php'))
        ->not->toContain('(int) HTTPS_PORT');
});
