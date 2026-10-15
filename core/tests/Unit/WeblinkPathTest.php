<?php

use EvolutionCMS\Core;

/*
| A weblink to "assets/files/doc.pdf" (what the file browser inserts) is redirected with a
| Location header, which the browser resolves against the weblink's own URL, not <base href>.
*/

function weblinkResolver(): Core
{
    return (new ReflectionClass(Core::class))->newInstanceWithoutConstructor();
}

test('site-relative weblink paths are anchored to the base url', function (string $url, string $base, string $expected) {
    expect(weblinkResolver()->resolveWeblinkPath($url, $base))->toBe($expected);
})->with([
    'file browser path' => ['assets/files/doc.pdf', '/', '/assets/files/doc.pdf'],
    'site in a subfolder' => ['assets/files/doc.pdf', '/site/', '/site/assets/files/doc.pdf'],
    'base url without a trailing slash' => ['news/', '/site', '/site/news/'],
    'path with a query' => ['index.php?id=12', '/', '/index.php?id=12'],
]);

test('weblink targets that already say where they go are left alone', function (string $url) {
    expect(weblinkResolver()->resolveWeblinkPath($url, '/site/'))->toBe($url);
})->with([
    'absolute url' => ['https://evo.im/docs/'],
    'other scheme' => ['mailto:info@example.com'],
    'root-relative path' => ['/assets/files/doc.pdf'],
    'protocol-relative url' => ['//cdn.example.com/a.js'],
    'query only' => ['?page=2'],
    'fragment only' => ['#top'],
    'empty' => [''],
]);
