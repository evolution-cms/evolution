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
