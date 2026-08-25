<?php

/*
|--------------------------------------------------------------------------
| Parser eval() hardening
|--------------------------------------------------------------------------
|
| Three parser entry points used to build a string and hand it to eval():
|
|   - mergeConditionalTagsContent()  <@IF:...> conditional tags
|   - _getSGVar()                     [[$_GET(x)]] superglobal reads
|   - atBindFileContent()             @FILE: template includes
|
| All three are reachable from content that the parser re-scans across passes, so a snippet echoing
| request data can carry a payload into them without any editing privilege. These tests drive the
| real methods on a Core instance and assert that a payload cannot execute or read outside the tree,
| while the legitimate syntax each method exists to serve keeps working.
|
*/

use EvolutionCMS\Core;

beforeAll(function () {
    if (!defined('IN_INSTALL_MODE')) {
        define('IN_INSTALL_MODE', false);
    }
    if (!defined('EVO_API_MODE')) {
        define('EVO_API_MODE', true);
    }
    if (!defined('IN_MANAGER_MODE')) {
        define('IN_MANAGER_MODE', false);
    }
    $root = str_replace('\\', '/', dirname(__DIR__, 3)) . '/';
    if (!defined('EVO_BASE_PATH')) {
        define('EVO_BASE_PATH', $root);
    }
    if (!defined('EVO_CORE_PATH')) {
        define('EVO_CORE_PATH', $root . 'core/');
    }
    if (!defined('EVO_MANAGER_PATH')) {
        define('EVO_MANAGER_PATH', $root . 'manager/');
    }
    $autoload = EVO_CORE_PATH . 'vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }
});

class ParserHardeningCore extends Core
{
    public $cfg = ['enable_filter' => 1, 'rb_base_url' => 'assets/'];

    public function getConfig($name = '', $default = null)
    {
        return $this->cfg[$name] ?? $default;
    }

    public function setConfig($name, $value = null): void
    {
        $this->cfg[$name] = $value;
    }
}

/**
 * A Core with the two config keys the tested methods read, built without the heavy constructor so
 * no bootstrap (storage paths, container, DB) is required.
 */
function parserHardeningCore(): Core
{
    $core = (new ReflectionClass(ParserHardeningCore::class))->newInstanceWithoutConstructor();

    $_SERVER['REQUEST_TIME'] = $_SERVER['REQUEST_TIME'] ?? time();

    return $core;
}

describe('conditional tags (<@IF:>)', function () {

    test('a quote/backslash breakout neither executes nor fatals', function () {
        $core = parserHardeningCore();
        $marker = str_replace('\\', '/', sys_get_temp_dir()) . '/evo_ctag_' . bin2hex(random_bytes(6));

        // A backslash immediately before the quote defeated the str_replace("'", "\'") escaping:
        // the doubled backslash was consumed, the quote closed the generated string literal early,
        // and the tail ran as PHP.
        $bs = chr(92);
        $sq = chr(39);
        $cmd = '1' . $bs . $sq . '.file_put_contents("' . $marker . '","x").' . $bs . $sq . '1';

        $out = $core->mergeConditionalTagsContent('<@IF:' . $cmd . '>body<@ENDIF>');

        expect(file_exists($marker))->toBeFalse()
            ->and($out)->toBeString();
    });

    test('legitimate numeric conditionals still resolve', function () {
        $core = parserHardeningCore();

        expect($core->mergeConditionalTagsContent('<@IF:5>A<@ELSE>B<@ENDIF>'))->toBe('A')
            ->and($core->mergeConditionalTagsContent('<@IF:0>A<@ELSE>B<@ENDIF>'))->toBe('B')
            ->and($core->mergeConditionalTagsContent('<@IF:5>A<@ELSEIF:1>X<@ELSE>B<@ENDIF>'))->toBe('A')
            ->and($core->mergeConditionalTagsContent('<@IF:0>A<@ELSEIF:1>X<@ELSE>B<@ENDIF>'))->toBe('X')
            ->and($core->mergeConditionalTagsContent('<@IF: !0 >neg<@ENDIF>'))->toBe('neg');
    });

    test('nested conditionals resolve without index corruption', function () {
        $core = parserHardeningCore();

        // The inner block trims the shared command list; the outer indices must survive that.
        $tpl = '<@IF:1>outer <@IF:1>inner<@ELSE>x<@ENDIF> end<@ELSE>no<@ENDIF>';

        expect($core->mergeConditionalTagsContent($tpl))->toBe('outer inner end');
    });

    test('content with no conditional tag is returned untouched', function () {
        $core = parserHardeningCore();
        $plain = 'plain [+ph+] content with no tags';

        expect($core->mergeConditionalTagsContent($plain))->toBe($plain);
    });
});

describe('superglobal reads ([[$_GET(x)]])', function () {

    test('a backtick payload is not executed', function () {
        $core = parserHardeningCore();
        // A colon-free relative name: a `:` in the tag is the modifier delimiter, unrelated here.
        $marker = 'evo_sg_' . bin2hex(random_bytes(6)) . '.txt';

        // Backticks need no parentheses, so the old `(`/`)` rewrite did not stop them.
        $payload = '$_SERVER . `echo x > ' . $marker . '`';

        $value = $core->_getSGVar($payload);

        expect(file_exists($marker))->toBeFalse()
            ->and($value)->toBe('');
    });

    test('a statement-separator payload is refused', function () {
        $core = parserHardeningCore();

        expect($core->_getSGVar('$_GET[id];phpinfo()'))->toBe('');
    });

    test('the documented accessor forms still read the value', function () {
        $core = parserHardeningCore();
        $_GET['id'] = 'hello';
        $_POST['name'] = 'world';

        // The caller rewrites (key) into ['key'] before _getSGVar sees it; accept both spellings.
        expect($core->_getSGVar("\$_GET['id']"))->toBe('hello')
            ->and($core->_getSGVar('$_GET(id)'))->toBe('hello')
            ->and($core->_getSGVar("\$_POST['name']"))->toBe('world');

        unset($_GET['id'], $_POST['name']);
    });

    test('a missing key yields empty string, not a notice', function () {
        $core = parserHardeningCore();
        unset($_GET['nope']);

        expect($core->_getSGVar("\$_GET['nope']"))->toBe('');
    });

    test('mgrFormValues and token stay hidden from $_SESSION dumps', function () {
        $core = parserHardeningCore();
        $_SESSION = ['visible' => '1', 'mgrFormValues' => 'secret', 'token' => 'csrf'];

        $dump = $core->_getSGVar('$_SESSION');

        expect($dump)->toContain('visible')
            ->and($dump)->not->toContain('mgrFormValues')
            ->and($dump)->not->toContain('csrf');

        $_SESSION = [];
    });

    test('a variable outside the allow list is refused', function () {
        $core = parserHardeningCore();

        expect($core->_getSGVar('$GLOBALS'))->toBe('')
            ->and($core->_getSGVar('$this'))->toBe('');
    });
});

describe('@FILE binding', function () {

    test('directory traversal outside the base path is refused', function () {
        $core = parserHardeningCore();

        // A real file that certainly exists outside EVO_BASE_PATH.
        $traversalDepth = str_repeat('../', 20);

        expect($core->atBindFileContent('@FILE:' . $traversalDepth . 'Windows/win.ini'))
            ->toContain('Could not retrieve')
            ->and($core->atBindFileContent('@FILE:' . $traversalDepth . 'etc/passwd'))
            ->toContain('Could not retrieve');
    });

    test('a php file inside the tree is still refused, including alternate extensions', function () {
        $core = parserHardeningCore();

        expect($core->atBindFileContent('@FILE:index.php'))->toBe('Could not retrieve PHP file.')
            ->and($core->atBindFileContent('@FILE:index.phtml'))->toBe('Could not retrieve PHP file.')
            ->and($core->atBindFileContent('@FILE:x.inc'))->toBe('Could not retrieve PHP file.');
    });

    test('a permitted file inside the tree is read', function () {
        $core = parserHardeningCore();

        $relative = 'assets/evo_atfile_' . bin2hex(random_bytes(6)) . '.txt';
        $absolute = EVO_BASE_PATH . $relative;
        file_put_contents($absolute, 'included-body');

        try {
            expect($core->atBindFileContent('@FILE:' . $relative))->toBe('included-body');
        } finally {
            @unlink($absolute);
        }
    });

    test('a traversal that resolves back inside the tree is still allowed', function () {
        $core = parserHardeningCore();

        $relative = 'assets/evo_atfile_' . bin2hex(random_bytes(6)) . '.txt';
        $absolute = EVO_BASE_PATH . $relative;
        file_put_contents($absolute, 'roundtrip');

        try {
            // assets/../assets/<file> normalises to a path under the base, so it must resolve.
            expect($core->atBindFileContent('@FILE:assets/../' . $relative))->toBe('roundtrip');
        } finally {
            @unlink($absolute);
        }
    });
});
