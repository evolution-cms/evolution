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
*/

use EvolutionCMS\Core;

beforeAll(function () {
    if (!defined('EVO_SANITIZE_SEED')) {
        define('EVO_SANITIZE_SEED', 'iis-furl-test-seed');
    }
    if (!defined('EVO_SITE_URL')) {
        define('EVO_SITE_URL', 'http://example.test/');
    }
    if (!defined('EVO_BASE_URL')) {
        define('EVO_BASE_URL', '/');
    }
});

class IisFurlFixCore extends Core
{
    public function getConfig($name = '', $default = null)
    {
        return $name === 'friendly_urls' ? 1 : $default;
    }
}

function iisFurlFixRun(string $queryString): void
{
    $core = (new ReflectionClass(IisFurlFixCore::class))->newInstanceWithoutConstructor();

    $_SERVER['SERVER_SOFTWARE'] = 'Microsoft-IIS/10.0';
    $_SERVER['QUERY_STRING'] = $queryString;
    $_GET = ['404;' . EVO_SITE_URL . 'page' => ''];
    $_REQUEST = $_GET;

    $core->_IIS_furl_fix();
}

afterEach(function () {
    $_GET = $_REQUEST = [];
    unset($_SERVER['SERVER_SOFTWARE'], $_SERVER['QUERY_STRING'], $_SERVER['PHP_SELF']);
});

it('sanitizes the query values it rebuilds', function () {
    iisFurlFixRun('404;' . EVO_SITE_URL . 'page?flat=[[Snippet]]&nested[a]=[!Snippet!]');

    foreach ([$_GET, $_REQUEST] as $request) {
        expect($request['flat'])->not->toContain('[[')
            ->and($request['nested']['a'])->not->toContain('[!')
            ->and(removeSanitizeSeed($request['flat']))->toBe('[[Snippet]]');
    }
});

it('sanitizes the rebuilt document path', function () {
    iisFurlFixRun('404;' . EVO_SITE_URL . 'page-[[Snippet]]');

    // the same value is stored as the document identifier ($core->q)
    expect($_SERVER['PHP_SELF'])->not->toContain('[[');
});

it('still rebuilds an ordinary request', function () {
    iisFurlFixRun('404;' . EVO_SITE_URL . 'news/item?page=2');

    expect($_GET)->toBe(['page' => '2'])
        ->and($_SERVER['PHP_SELF'])->toBe(EVO_BASE_URL . 'news/item')
        ->and($_SERVER['QUERY_STRING'])->toBe('page=2');
});

it('copes with a URL that has no query', function () {
    iisFurlFixRun('404;' . EVO_SITE_URL . 'about');

    expect($_GET)->toBe([])
        ->and($_SERVER['PHP_SELF'])->toBe(EVO_BASE_URL . 'about');
});
