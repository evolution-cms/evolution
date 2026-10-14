<?php

/*
|--------------------------------------------------------------------------
| IIS friendly URL fix keeps request sanitizing
|--------------------------------------------------------------------------
|
| On IIS the 404 handler hands the original URL over as `404;http://host/path?a=b`.
| Core::_IIS_furl_fix() rebuilt $_GET and $_REQUEST from it after
| protect.inc.php had already run, so the rebuilt values kept their parser tags.
|
| It runs in a fresh process (tests/Fixtures/iis-furl-fix.php): the method reads EVO_SITE_URL and
| EVO_BASE_URL, and in a full run other tests have already defined them from the CLI environment.
|
*/

/**
 * @return array{get: array, request: array, php_self: ?string, query_string: string, seed: string, base_url: string}
 */
function iisFurlFixRun(string $tail): array
{
    // base64: on Windows escapeshellarg() turns "!" (as in [!Snippet!]) into a space
    $output = evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/iis-furl-fix.php', [base64_encode($tail)], $exitCode, false);
    $result = json_decode($output, true);
    if ($exitCode !== 0 || !is_array($result)) {
        throw new RuntimeException('iis-furl-fix fixture failed: ' . $output);
    }

    return $result;
}

it('sanitizes the query values it rebuilds', function () {
    $result = iisFurlFixRun('page?flat=[[Snippet]]&nested[a]=[!Snippet!]');

    foreach ([$result['get'], $result['request']] as $request) {
        expect($request['flat'])->not->toContain('[[')
            ->and($request['nested']['a'])->not->toContain('[!')
            ->and(str_replace($result['seed'], '', $request['flat']))->toBe('[[Snippet]]')
            ->and(str_replace($result['seed'], '', $request['nested']['a']))->toBe('[!Snippet!]');
    }
});

it('sanitizes the rebuilt document path', function () {
    $result = iisFurlFixRun('page-[[Snippet]]');

    // the same value is stored as the document identifier ($core->q)
    expect($result['php_self'])->not->toContain('[[');
});

it('still rebuilds an ordinary request', function () {
    $result = iisFurlFixRun('news/item?page=2');

    expect($result['get'])->toBe(['page' => '2'])
        ->and($result['php_self'])->toBe($result['base_url'] . 'news/item')
        ->and($result['query_string'])->toBe('page=2');
});

it('copes with a URL that has no query', function () {
    $result = iisFurlFixRun('about');

    expect($result['get'])->toBe([])
        ->and($result['php_self'])->toBe($result['base_url'] . 'about');
});
