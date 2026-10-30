<?php

use EvolutionCMS\Support\ManagerReferer;

/*
|--------------------------------------------------------------------------
| The manager's referer check (validate_referer)
|--------------------------------------------------------------------------
|
| A host or proxy that sends `Referrer-Policy: no-referrer` makes the browser drop the Referer
| even on the manager's own POSTs, so the login form was refused as a CSRF attempt. Such a request
| still carries `Sec-Fetch-Site: same-origin`, which no referrer policy removes. Behind a proxy
| that ends TLS, PHP sees http while the browser's Referer says https; the "Server type" setting
| declares the site https.
|
*/

const MANAGER_REFERER_SITE = 'http://example.test/sub/';

function managerRefererAllows(array $server, string $protocol = 'http'): bool
{
    return ManagerReferer::allows($server + ['REQUEST_METHOD' => 'POST'], MANAGER_REFERER_SITE, $protocol);
}

it('accepts a referer from the site itself, in any letter case', function (string $referer) {
    expect(managerRefererAllows(['HTTP_REFERER' => $referer]))->toBeTrue();
})->with(['http://example.test/sub/manager/', 'HTTP://EXAMPLE.TEST/sub/manager/index.php?a=2']);

it('refuses a foreign referer, whatever else the request claims', function (string $referer) {
    expect(managerRefererAllows(['HTTP_REFERER' => $referer, 'HTTP_SEC_FETCH_SITE' => 'same-origin']))->toBeFalse()
        ->and(managerRefererAllows(['HTTP_REFERER' => $referer, 'REQUEST_METHOD' => 'GET']))->toBeFalse();
})->with([
    'other site' => 'http://evil.test/',
    'look-alike host' => 'http://example.test.evil.test/sub/',
    'site as a path' => 'http://evil.test/http://example.test/sub/',
    'outside the base path' => 'http://example.test/other/',
    'other port' => 'http://example.test:8080/sub/',
]);

it('accepts a POST without a referer that the browser marks as same-origin', function () {
    expect(managerRefererAllows(['HTTP_SEC_FETCH_SITE' => 'same-origin']))->toBeTrue()
        ->and(managerRefererAllows(['HTTP_REFERER' => '', 'HTTP_SEC_FETCH_SITE' => 'Same-Origin']))->toBeTrue();
});

it('refuses a POST without a referer from anywhere but the same origin', function (?string $site) {
    $server = $site === null ? [] : ['HTTP_SEC_FETCH_SITE' => $site];

    expect(managerRefererAllows($server))->toBeFalse()
        ->and(managerRefererAllows($server + ['HTTP_REFERER' => '']))->toBeFalse();
})->with([null, 'same-site', 'cross-site', 'none', '']);

it('keeps letting a GET without a referer through, but not one with an empty referer', function () {
    expect(managerRefererAllows(['REQUEST_METHOD' => 'GET']))->toBeTrue()
        ->and(managerRefererAllows(['REQUEST_METHOD' => 'GET', 'HTTP_REFERER' => '']))->toBeFalse();
});

it('accepts the https referer of an http site URL once the site is declared https', function () {
    $referer = ['HTTP_REFERER' => 'https://example.test/sub/manager/'];

    expect(managerRefererAllows($referer, 'http'))->toBeFalse()
        ->and(managerRefererAllows($referer, 'https'))->toBeTrue()
        ->and(ManagerReferer::siteUrls(MANAGER_REFERER_SITE, 'https'))
        ->toBe([MANAGER_REFERER_SITE, 'https://example.test/sub/']);
});

it('never lets http count for an https site', function () {
    expect(ManagerReferer::allows(
        ['REQUEST_METHOD' => 'POST', 'HTTP_REFERER' => 'http://example.test/sub/manager/'],
        'https://example.test/sub/',
        'https'
    ))->toBeFalse();
});

it('is what evo() asks, with the Server type setting', function () {
    $source = file_get_contents(dirname(__DIR__, 3) . '/functions/preload.php');

    expect($source)->toContain('ManagerReferer::allows(')
        ->and($source)->toContain("getConfig('server_protocol'")
        ->and($source)->not->toContain("preg_quote(EVO_SITE_URL");
});

it('asks the browser for a same-origin referrer on every manager page that posts', function (string $file) {
    expect(file_get_contents(dirname(__DIR__, 4) . '/' . $file))
        ->toContain('<meta name="referrer" content="same-origin">');
})->with([
    'manager/media/style/default/login.tpl',
    'manager/media/style/common/login.tpl',
    'manager/media/style/default/manager.lockout.tpl',
    'manager/media/style/common/manager.lockout.tpl',
    'manager/views/frame/1.blade.php',
    'manager/views/partials/header.blade.php',
    'manager/media/browser/mcpuk/tpl/tpl_browser.php',
]);

it('sends the same policy as a header before the referer check runs', function () {
    $index = file_get_contents(dirname(__DIR__, 4) . '/manager/index.php');

    expect($index)->toContain("header('Referrer-Policy: same-origin');")
        ->and(strpos($index, "header('Referrer-Policy: same-origin');"))->toBeLessThan(strpos($index, '= evo();'));
});
