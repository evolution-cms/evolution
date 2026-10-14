<?php

/*
 * Runs Core::_IIS_furl_fix() for one IIS 404 query string and prints the rebuilt request as JSON.
 *
 * The method strips EVO_SITE_URL out of the URL, and inside a full test run that constant is
 * whatever the CLI bootstrap made of the environment ("/" on Windows, which strips every slash),
 * so the constants are pinned here, in a process of their own.
 *
 * Usage: php iis-furl-fix.php <base64 of the path and query after EVO_SITE_URL>
 */

// keep diagnostics off stdout, which carries the JSON
ini_set('display_errors', 'stderr');

define('IN_MANAGER_MODE', false);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', true);
define('EVO_SANITIZE_SEED', 'iis-furl-test-seed');
define('EVO_SITE_URL', 'http://example.test/sub/');
define('EVO_BASE_URL', '/sub/');

$core = dirname(__DIR__, 2) . '/';
require $core . 'vendor/autoload.php';
require_once $core . 'functions/preload.php';

final class IisFurlFixCore extends EvolutionCMS\Core
{
    public function getConfig($name = '', $default = null)
    {
        return $name === 'friendly_urls' ? 1 : $default;
    }
}

$tail = base64_decode((string) ($argv[1] ?? ''), true);
$core = (new ReflectionClass(IisFurlFixCore::class))->newInstanceWithoutConstructor();

$_SERVER['SERVER_SOFTWARE'] = 'Microsoft-IIS/10.0';
$_SERVER['QUERY_STRING'] = '404;' . EVO_SITE_URL . $tail;
$_GET = ['404;' . EVO_SITE_URL . 'page' => ''];
$_REQUEST = $_GET;

$core->_IIS_furl_fix();

echo json_encode([
    'get' => $_GET,
    'request' => $_REQUEST,
    'php_self' => $_SERVER['PHP_SELF'] ?? null,
    'query_string' => $_SERVER['QUERY_STRING'],
    'seed' => EVO_SANITIZE_SEED,
    'base_url' => EVO_BASE_URL,
]);
