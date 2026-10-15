<?php

/*
 * Renders manager TV form elements with renderFormElement() from one of its two copies against a
 * stub evo() and prints the HTML as JSON. Both copies are guarded by function_exists() and composer
 * autoloads functions/actions/mutate_content.php, so only a fresh process that loads the chosen
 * copy before the autoloader can exercise core/functions/tv.php.
 *
 * Usage: php tv-render-harness.php <functions/tv.php|functions/actions/mutate_content.php>
 */

// keep diagnostics off stdout, which carries the JSON
ini_set('display_errors', 'stderr');

define('IN_MANAGER_MODE', true);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', true);

$core = dirname(__DIR__, 2) . '/';
define('EVO_BASE_PATH', dirname($core) . '/');
define('EVO_CORE_PATH', $core);

final class TvRenderHarnessCore
{
    public array $config = ['enable_bindings' => 1, 'site_url' => 'https://example.test/', 'use_editor' => 0];
    public $documentIdentifier = 1;
    public $documentObject = [];
    private $phpCompat;

    public function getConfig($name = '', $default = null)
    {
        return $this->config[$name] ?? $default;
    }

    public function getPhpCompat()
    {
        return $this->phpCompat ??= new \EvolutionCMS\Legacy\PhpCompat();
    }

    public function getManagerApi()
    {
        return (object) ['action' => 27];
    }

    public function getChunk($name)
    {
        return '';
    }

    public function parseDocumentSource($content)
    {
        return $content;
    }

    public function mergePlaceholderContent($content)
    {
        return $content;
    }

    public function getTagsFromContent($content, $left = '[+', $right = '+]')
    {
        return [];
    }

    public function logEvent($evtid, $type, $msg, $source = 'Parser')
    {
    }

    public function getDatabase()
    {
        // option lists here are plain text, never a query result
        return new class {
            public function isResult($value): bool
            {
                return false;
            }
        };
    }
}

final class ManagerTheme
{
    public static function getLexicon($key = null, $default = '')
    {
        return 'Insert';
    }

    public static function getStyle($key = null)
    {
        return 'icon';
    }
}

function evo()
{
    return $GLOBALS['tvRenderHarness'];
}

function evolutionCMS()
{
    return evo();
}

$phpErrors = [];
set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline) use (&$phpErrors) {
    $phpErrors[] = $errstr . ' in ' . basename($errfile) . ':' . $errline;

    return true;
});

$copy = $argv[1] ?? 'functions/tv.php';
require $core . $copy;
require $core . 'vendor/autoload.php';

$GLOBALS['tvRenderHarness'] = new TvRenderHarnessCore();
$GLOBALS['content'] = ['richtext' => 0];

// what an editor can save as a TV value, and what a template editor can type as options
$attr = 'x" autofocus onfocus="alert(1)';
$markup = '<img src=x onerror=alert(2)>';
$css = "a.jpg'); background:url(javascript:alert(3)); x:('";
$options = $markup . '==1||Plain==2';

$results = [
    'image' => renderFormElement('image', 1, '', '', $attr),
    'imageCss' => renderFormElement('image', 2, '', '', $css),
    'imageClean' => renderFormElement('image', 3, '', '', 'assets/images/logo.png'),
    'file' => renderFormElement('file', 4, '', '', $attr),
    'date' => renderFormElement('date', 5, '', '', $attr),
    'dateClean' => renderFormElement('date', 6, '', '', '15-10-2026 12:00:00'),
    'dateEmpty' => renderFormElement('date', 7, '', '', ''),
    'option' => renderFormElement('option', 8, '', $options, '1'),
    'checkbox' => renderFormElement('checkbox', 9, '', $options, '1'),
];

echo json_encode(['results' => $results, 'phpErrors' => $phpErrors]);
