<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

use EvolutionCMS\Core;
use PHPUnit\Framework\TestCase;

/*
|--------------------------------------------------------------------------
| Test Setup & Bootstrap
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    // Reset global evo/modx to prevent contamination from other tests
    // (e.g. ClearCacheFullCommandTest sets $modx to a Mockery mock which
    //  leaks into the global $evo, causing app('path.storage') to return
    //  the mock object instead of a string path).
    global $evo, $modx;
    $evo = null;
    $modx = null;

    $rootDir = dirname(__DIR__, 3); // tests/Unit/ → project root

    // === Constants (no function calls allowed yet!) ===
    if (!defined('IN_INSTALL_MODE'))
        define('IN_INSTALL_MODE', false);
    if (!defined('EVO_API_MODE'))
        define('EVO_API_MODE', true);
    if (!defined('IN_MANAGER_MODE'))
        define('IN_MANAGER_MODE', false);
    if (!defined('EVO_BASE_PATH'))
        define('EVO_BASE_PATH', rtrim($rootDir, '/\\') . '/');
    if (!defined('EVO_CORE_PATH'))
        define('EVO_CORE_PATH', EVO_BASE_PATH . 'core' . '/');
    if (!defined('EVO_STORAGE_PATH'))
        define('EVO_STORAGE_PATH', EVO_CORE_PATH . 'storage' . '/');
    if (!defined('EVO_MANAGER_PATH'))
        define('EVO_MANAGER_PATH', EVO_BASE_PATH . 'manager' . '/');
    if (!defined('EVO_SITE_URL'))
        define('EVO_SITE_URL', 'http://127.0.0.1/');

    if (!defined('EVO_CLASS')) {
        define('EVO_CLASS', 'Tests\Mocks\MockDocumentParser');
    }

    $autoload = EVO_BASE_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
    if (!file_exists($autoload)) {
        throw new \RuntimeException("Run: composer install");
    }
    require_once $autoload;

    require_once EVO_CORE_PATH . 'includes' . DIRECTORY_SEPARATOR . 'define.inc.php';

    $this->parser = \EvolutionCMS\Core::getInstance();

    // Set a default config for tests
    $this->parser->config = $this->parser->config ?? [];
    $this->parser->config['enable_filter'] = 0;
});

afterEach(function () {
    // Restore handlers to avoid "risky" tests due to ExceptionHandler setting global handlers
    if ($this->parser->hasService('ExceptionHandler')) {
        $this->parser->getService('ExceptionHandler')->restoreHandlers();
    }
    Mockery::close();
});

describe('getTagsFromContent', function () {

    test('it extracts basic placeholders correctly', function () {
        $content = 'Hello [+name+], welcome to [+site+].';
        $result = $this->parser->getTagsFromContent($content);

        expect($result)->toBeArray()
            ->and($result[1])->toBe(['name', 'site'])
            ->and($result[0])->toBe(['[+name+]', '[+site+]']);
    });

    test('it handles multibyte characters in content and tags', function () {
        $content = 'Привіт [+плейсхолдер+], [+こんにちは+]!';
        $result = $this->parser->getTagsFromContent($content);

        expect($result[1])->toBe(['плейсхолдер', 'こんにちは']);
    });
    /* covers expected logic not current optimal one
    test('it ignores tags inside CDATA sections', function () {
        $content = '[+a+] <![CDATA[ [+ignored1+] ]]> [+b+] <![CDATA[ [+ignored2+] ]]> [+c+]';
        $result = $this->parser->getTagsFromContent($content);

        expect($result[1])->toBe(['a', 'b', 'c'])
            ->and($result[1])->not->toContain('ignored1', 'ignored2');
    });

    test('it handles snippet edge cases', function () {
        $content = '[[snippet]]]';
        $result = $this->parser->getTagsFromContent($content, '[[', ']]');
        expect($result[1])->toBe(['snippet']);

        $content = '[[snippet]]]]';
        $result = $this->parser->getTagsFromContent($content, '[[', ']]');
        expect($result[1])->toBe(['snippet']);
    });
*/
    test('it ignores empty chunks {{}}', function () {
        $content = 'Start {{}} {{valid}} End';
        $result = $this->parser->getTagsFromContent($content, '{{', '}}');

        expect($result[1])->toBe(['valid'])
            ->and($result[1])->not->toContain('');
    });
/* covers undocumented logic which may be still used
    test('it ignores chunks ending with semicolon', function () {
        $content = 'Valid {{chunk1}} Ignored {{chunk2;}} Valid {{chunk3}}';
        $result = $this->parser->getTagsFromContent($content, '{{', '}}');

        expect($result[1])->toBe(['chunk1', 'chunk3'])
            ->and($result[1])->not->toContain('chunk2;');
    });
*/
    test('it handles nested tags when filters enabled', function () {
        $this->parser->config['enable_filter'] = 1;

        $content = '[+outer [+inner+]+]';
        $result = $this->parser->getTagsFromContent($content);

        expect($result[1])->toBe(['inner', 'outer [+inner+]']);
    });

    test('it ignores nested tags when filters disabled', function () {
        $this->parser->config['enable_filter'] = 0;

        $content = '[+outer [+inner+]+]';
        $result = $this->parser->getTagsFromContent($content);

        expect($result[1])->toBe(['inner', 'outer [+inner+]']);
    });

    test('it deduplicates tags efficiently', function () {
        $content = '[+tag+] [+tag+] [+tag+]';
        $result = $this->parser->getTagsFromContent($content);

        expect($result[1])->toBe(['tag'])
            ->and(count($result[1]))->toBe(1);
    });

    test('it handles unclosed tags gracefully', function () {
        $content = '[+valid+] [+unclosed';
        $result = $this->parser->getTagsFromContent($content);

        expect($result[1])->toBe(['valid']);
    });

    test('it processes complex nesting scenarios', function () {
        $this->parser->config['enable_filter'] = 1;

        $content = '[+1 [+2 [+3+]+]+]';
        $result = $this->parser->getTagsFromContent($content);

        expect($result[1])->toBe(['3', '2 [+3+]', '1 [+2 [+3+]+]']);
    });
});