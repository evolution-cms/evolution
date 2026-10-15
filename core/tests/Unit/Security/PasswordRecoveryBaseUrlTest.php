<?php

use EvolutionCMS\Services\PasswordRecoveryService;

test('a pinned host list keeps the configured manager url', function () {
    $url = PasswordRecoveryService::resolveRecoveryBaseUrl(
        'https://example.com/manager/',
        'example.com,www.example.com',
        ['SERVER_NAME' => 'other.test', 'HTTP_HOST' => 'example.com']
    );

    expect($url)->toBe('https://example.com/manager/');
});

test('without a host list the server name replaces a forged host header', function () {
    // The Host header was "evil.example", so EVO_MANAGER_URL was derived from it.
    $url = PasswordRecoveryService::resolveRecoveryBaseUrl(
        'http://evil.example/manager/',
        '',
        ['SERVER_NAME' => 'site.test', 'HTTP_HOST' => 'evil.example']
    );

    expect($url)->toBe('http://site.test/manager/');
});

test('scheme, port and path survive the host swap', function () {
    expect(PasswordRecoveryService::resolveRecoveryBaseUrl(
        'https://evil.example:8443/sub/manager/',
        ' ',
        ['SERVER_NAME' => 'site.test', 'SERVER_PORT' => '8443', 'HTTP_HOST' => 'EVIL.example:8443']
    ))->toBe('https://site.test:8443/sub/manager/');
});

test('the port comes from the server, never from the host header', function (array $server, string $expected) {
    // EVO_MANAGER_URL carries whatever port the Host header named
    $managerUrl = 'http://' . $server['HTTP_HOST'] . '/manager/';

    expect(PasswordRecoveryService::resolveRecoveryBaseUrl($managerUrl, '', $server + ['SERVER_NAME' => 'site.test']))
        ->toBe($expected);
})->with([
    // Apache picks the vhost by name only, so the trusted name with a forged port gets here
    'trusted name, forged port' => [['HTTP_HOST' => 'site.test:9080', 'SERVER_PORT' => '80'], 'http://site.test/manager/'],
    'forged name and port' => [['HTTP_HOST' => 'evil.example:9080', 'SERVER_PORT' => '80'], 'http://site.test/manager/'],
    // UseCanonicalName On with "ServerName site.test:8101", or nginx listening on 8101
    'server port' => [['HTTP_HOST' => 'site.test:9080', 'SERVER_PORT' => '8101'], 'http://site.test:8101/manager/'],
    'tls terminated by a proxy' => [['HTTP_HOST' => 'site.test', 'SERVER_PORT' => '443'], 'http://site.test/manager/'],
    'no server port' => [['HTTP_HOST' => 'site.test:9080'], 'http://site.test/manager/'],
    'garbage server port' => [['HTTP_HOST' => 'site.test:9080', 'SERVER_PORT' => '99999'], 'http://site.test/manager/'],
]);

test('an unusable server name leaves the url unchanged', function () {
    $names = [null, '', '_', 'a@evil.example', "x.test/\n", '*.example.com', '.example.com'];

    foreach ($names as $name) {
        $server = ['HTTP_HOST' => 'localhost'];
        if ($name !== null) {
            $server['SERVER_NAME'] = $name;
        }

        expect(PasswordRecoveryService::resolveRecoveryBaseUrl('http://localhost/manager/', '', $server))
            ->toBe('http://localhost/manager/');
    }
});

test('a manager url the operator pinned is not replaced', function () {
    // EVO_SITE_URL was defined in custom/define.php, so its host is not the Host header's.
    expect(PasswordRecoveryService::resolveRecoveryBaseUrl(
        'https://cms.example.com/manager/',
        '',
        ['SERVER_NAME' => 'localhost', 'HTTP_HOST' => 'evil.example']
    ))->toBe('https://cms.example.com/manager/')
        ->and(PasswordRecoveryService::resolveRecoveryBaseUrl(
            'https://cms.example.com/manager/',
            '',
            ['SERVER_NAME' => 'localhost']
        ))->toBe('https://cms.example.com/manager/');
});

test('the recovery mail link is built from the resolved url', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Services/PasswordRecoveryService.php');

    expect($source)->toContain("\$link = \$this->recoveryBaseUrl() . '?a=0&hash='")
        ->and($source)->not->toContain("\$link = EVO_MANAGER_URL");
});
