<?php

/**
 * EVO_SITE_URL is built once at boot and every asset URL, redirect and the
 * manager's referer check hang off it, so the host header has to survive the
 * trip intact. It used to be stripped with str_replace(':' . SERVER_PORT, ''),
 * which turns "localhost:8080" into "localhost80" whenever the server itself
 * listens on 80 - the shape every published container port has.
 */
function resolveSiteUrlInFreshProcess(array $server): string
{
    $rootDir = dirname(__DIR__, 3);

    $code = '<?php' . "\n"
        // The boot code only reads the host header outside CLI, and the suite
        // itself runs on CLI - so the sapi check is answered before helper.php
        // gets a chance to define it.
        . 'function is_cli() { return false; }' . "\n"
        . '$_SERVER = ' . var_export($server + [
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
            'REQUEST_METHOD' => 'GET',
        ], true) . ';' . "\n"
        . 'define("IN_INSTALL_MODE", false);' . "\n"
        . 'define("IN_MANAGER_MODE", false);' . "\n"
        . 'define("EVO_API_MODE", true);' . "\n"
        . 'require ' . var_export($rootDir . '/core/vendor/autoload.php', true) . ';' . "\n"
        . 'require ' . var_export($rootDir . '/core/functions/helper.php', true) . ';' . "\n"
        . 'require ' . var_export($rootDir . '/core/functions/preload.php', true) . ';' . "\n"
        . 'require ' . var_export($rootDir . '/core/includes/define.inc.php', true) . ';' . "\n"
        . 'echo EVO_SITE_URL;';

    $scriptPath = tempnam(sys_get_temp_dir(), 'evo-site-url-') . '.php';
    file_put_contents($scriptPath, $code);

    $output = [];
    $status = 0;
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($scriptPath) . ' 2>&1', $output, $status);
    @unlink($scriptPath);

    expect($status)->toBe(0, implode("\n", $output));

    return trim(implode("\n", $output));
}

test('site url keeps the port the browser asked for', function (array $server, string $expected) {
    expect(resolveSiteUrlInFreshProcess($server))->toBe($expected);
})->with([
    // The regression: nginx/apache listen on 80 inside the container, the
    // browser reaches it on the published 8080.
    'published container port' => [
        ['HTTP_HOST' => 'localhost:8080', 'SERVER_PORT' => '80'],
        'http://localhost:8080/',
    ],
    'ipv6 literal with a port' => [
        ['HTTP_HOST' => '[::1]:8080', 'SERVER_PORT' => '80'],
        'http://[::1]:8080/',
    ],
    'plain http on 80' => [
        ['HTTP_HOST' => 'example.test', 'SERVER_PORT' => '80'],
        'http://example.test/',
    ],
    'http on a non default port' => [
        ['HTTP_HOST' => 'example.test:8080', 'SERVER_PORT' => '8080'],
        'http://example.test:8080/',
    ],
    'https on 443' => [
        ['HTTP_HOST' => 'example.test', 'SERVER_PORT' => '443', 'HTTPS' => 'on'],
        'https://example.test/',
    ],
    'https on a non default port' => [
        ['HTTP_HOST' => 'example.test:8443', 'SERVER_PORT' => '8443', 'HTTPS' => 'on'],
        'https://example.test:8443/',
    ],
    // TLS terminated in front of php: the port php answers on says nothing
    // about the URL the browser used.
    'proxied https, php on 80' => [
        ['HTTP_HOST' => 'example.test', 'SERVER_PORT' => '80', 'HTTP_X_FORWARDED_PROTO' => 'https'],
        'https://example.test/',
    ],
    'proxied https on a non default port' => [
        ['HTTP_HOST' => 'example.test:8443', 'SERVER_PORT' => '80', 'HTTP_X_FORWARDED_PROTO' => 'https'],
        'https://example.test:8443/',
    ],
    // Nothing to trust but the listening port.
    'no host header' => [
        ['SERVER_PORT' => '8080'],
        'http://localhost:8080/',
    ],
]);

/**
 * Whatever survives the host header ends up in every URL the site prints, so a
 * header that is not a plain host:port is not worth guessing at - the boot
 * falls back to the listening port, the same as a request with no host at all.
 */
test('a host header that is not a bare host:port is refused', function (string $host) {
    expect(resolveSiteUrlInFreshProcess(['HTTP_HOST' => $host, 'SERVER_PORT' => '80']))
        ->toBe('http://localhost/');
})->with([
    // Would become the userinfo of http://localhost@evil.example/ and take the
    // visitor to evil.example instead.
    'userinfo separator' => ['localhost@evil.example'],
    'path appended' => ['localhost/evil.example'],
    'scheme prefix' => ['http://localhost'],
    'crlf' => ["localhost
X-Injected: 1"],
    'trailing space' => ['localhost '],
    'port out of range' => ['localhost:99999'],
    'non numeric port' => ['localhost:80a'],
    'empty port' => ['localhost:'],
    'unclosed bracket' => ['[::1'],
]);

/**
 * The pattern is linear: its two branches differ in their first character, and
 * neither repetition can match the delimiter that follows it. A pathological
 * host header should cost roughly what a long ordinary one costs.
 */
test('host parsing does not blow up on a pathological header', function () {
    $pattern = '/^(?:([A-Za-z0-9._-]+)|(\[[0-9A-Fa-f:.]+\]))(?::(\d{1,5}))?$/';

    $time = function (string $subject) use ($pattern): float {
        $started = hrtime(true);
        for ($i = 0; $i < 50; $i++) {
            preg_match($pattern, $subject);
        }

        return (hrtime(true) - $started) / 50;
    };

    $short = $time(str_repeat('a', 2000) . ':');
    $long = $time(str_repeat('a', 8000) . ':');

    // Four times the input for well under sixteen times the work: linear, not
    // quadratic and nowhere near exponential.
    expect($long)->toBeLessThan(max($short, 1000) * 16)
        ->and(preg_last_error())->toBe(PREG_NO_ERROR);
});
