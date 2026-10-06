<?php

use EvolutionCMS\Core;

/*
|--------------------------------------------------------------------------
| Open redirect guard in sendRedirect()
|--------------------------------------------------------------------------
|
| The guard used to run only when parse_url() reported a scheme, so every target that names a
| host without one walked past it: "//evil.tld" is protocol-relative, and browsers rewrite the
| backslash variants into the same thing before following the Location header. Extras that hand
| sendRedirect() a "returnUrl" taken from the request are exactly what the guard is there for,
| so it has to read the target the way the browser will.
|
*/

/**
 * The check touches no state, so it is exercised on an instance built without the constructor -
 * booting the whole parser would say nothing more about it.
 */
function redirectGuard(): Core
{
    return (new ReflectionClass(Core::class))->newInstanceWithoutConstructor();
}

test('same-site and relative targets are allowed', function (string $url) {
    expect(redirectGuard()->isLocalRedirectTarget($url, 'https://example.com/'))->toBeTrue();
})->with([
    'relative path' => ['index.php?id=12'],
    'root-relative path' => ['/news/article/'],
    'root-relative path with a query' => ['/index.php?id=12&err=1'],
    'absolute url on this host' => ['https://example.com/manager/'],
    'absolute url on this host over http' => ['http://example.com/manager/'],
    'host casing differs' => ['https://EXAMPLE.com/manager/'],
]);

test('cross-site targets are refused', function (string $url) {
    expect(redirectGuard()->isLocalRedirectTarget($url, 'https://example.com/'))->toBeFalse();
})->with([
    'absolute url on another host' => ['https://evil.tld/'],
    // The regression: no scheme, so the old check never ran.
    'protocol-relative' => ['//evil.tld/'],
    'protocol-relative without a trailing slash' => ['//evil.tld'],
    'backslash after the slash' => ['/\\evil.tld/'],
    'backslash before the slash' => ['\\/evil.tld/'],
    'both backslashes' => ['\\\\evil.tld/'],
    'userinfo pointing at another host' => ['https://example.com@evil.tld/'],
    'subdomain of a lookalike' => ['https://example.com.evil.tld/'],
    'non-http scheme' => ['javascript:alert(1)'],
    'data url' => ['data:text/html,<script>alert(1)</script>'],
    'empty authority' => ['///'],
    'leading space before an authority' => [' //evil.tld/'],
    'leading tab before an authority' => ["\t//evil.tld/"],
    'leading tab before an absolute url' => ["\thttps://evil.tld/"],
]);

test('a site url without a host never matches', function () {
    expect(redirectGuard()->isLocalRedirectTarget('https://example.com/', ''))->toBeFalse();
});
test('resource links accept local paths and explicit external HTTP URLs', function (string $url) {
    expect(redirectGuard()->isWeblinkRedirectTarget($url, 'https://example.com/'))->toBeTrue();
})->with([
    ['/news/'], ['index.php?id=12'], ['https://example.com/news/'],
    ['https://ir.maup.com.ua/'],
    ['https://maup.com.ua/ua/navchannya-u-maup/akademichna-dobrochesnist.html'],
    ['http://other.example/path?q=a%20b'],
]);

test('resource links reject unsafe or incomplete targets', function (string $url) {
    expect(redirectGuard()->isWeblinkRedirectTarget($url, 'https://example.com/'))->toBeFalse();
})->with([
    [''], ['http://'], ['https:///path'], ['//other.example/'],
    ['javascript:alert(1)'], ['data:text/html,test'], ['ftp://other.example/'],
    ['https://user:pass@other.example/'], ['https://user@example.com/'],
    ["https://other.example/\r\nX-Test:1"], ["\thttps://other.example/"],
    ['https://other.example/%0d%0aX-Test:1'], ['https://other.example/%00'],
    ['https://other.example\\@example.com/'], ['/\\other.example/'],
    ['https://bad_host.example/'], ['https://other.example:99999/'],
]);

test('external redirects remain opt in', function () {
    $parameter = (new ReflectionMethod(Core::class, 'sendRedirect'))->getParameters()[4];
    expect($parameter->getDefaultValue())->toBeFalse();
    expect(redirectGuard()->isLocalRedirectTarget('https://ir.maup.com.ua/', 'https://example.com/'))->toBeFalse();
});

test('stored resource links explicitly opt in to validated external redirects', function () {
    $core = new class extends Core {
        public function __construct() {}
        public function sendRedirect(string $url, int $count_attempts = 0, string $type = '', string|int $responseCode = '', bool $allowExternal = false): ?bool
        {
            expect($url)->toBe('https://ir.maup.com.ua/');
            expect($allowExternal)->toBeTrue();
            expect($responseCode)->toBe(302);
            throw new RuntimeException('redirect intercepted');
        }
    };
    try {
        $core->_sendRedirectForRefPage('https://ir.maup.com.ua/');
    } catch (RuntimeException $error) {
        expect($error->getMessage())->toBe('redirect intercepted');
        return;
    }
    throw new RuntimeException('Resource link did not redirect');
});
