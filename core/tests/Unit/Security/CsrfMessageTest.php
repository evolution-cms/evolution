<?php

use EvolutionCMS\Support\CsrfMessage;

/*
|--------------------------------------------------------------------------
| The refusal text of the manager's CSRF checks
|--------------------------------------------------------------------------
|
| A failed token or referer check used to answer in hard-coded English, and the referer check
| answered the AJAX login form with a whole alert page, which the form then alerted as raw HTML.
| Every check now takes its text from CsrfMessage: one lexicon entry per refusal, in the manager
| language, plain text.
|
*/

function csrfMessageLexicon(string $language): array
{
    $file = dirname(__DIR__, 3) . '/lang/' . $language . '/global.php';
    if (!is_file($file)) {
        return [];
    }

    return (static function (string $file): array {
        $_lang = [];
        include $file;

        return $_lang;
    })($file);
}

afterEach(function () {
    unset($GLOBALS['evo']);
});

it('reads both refusals from the English lexicon', function () {
    $en = csrfMessageLexicon('en');

    expect(CsrfMessage::text(CsrfMessage::TOKEN, 'en'))->toBe($en['csrf_token_mismatch'])
        ->and(CsrfMessage::text(CsrfMessage::REFERER, 'en'))->toBe($en['csrf_referer_mismatch']);
});

it('answers in every shipped manager language, falling back to English per entry', function (string $language) {
    $en = csrfMessageLexicon('en');
    $own = csrfMessageLexicon($language);

    foreach ([CsrfMessage::TOKEN, CsrfMessage::REFERER] as $key) {
        $expected = isset($own[$key]) && trim((string)$own[$key]) !== '' ? $own[$key] : $en[$key];
        $text = CsrfMessage::text($key, $language);

        expect($text)->toBe($expected)
            ->and($text)->not->toMatch('/<[a-z!\/]/i')
            // a renamed manager folder keeps it from bots: a refusal never says where it is
            ->and($text)->not->toMatch('~https?://|/manager|%s|\[\+|MGR_DIR~i');
    }
})->with(fn () => array_map('basename', glob(dirname(__DIR__, 3) . '/lang/*', GLOB_ONLYDIR)));

it('never includes a file named by an unexpected language value', function (string $language) {
    expect(CsrfMessage::text(CsrfMessage::TOKEN, $language))->toBe(csrfMessageLexicon('en')['csrf_token_mismatch']);
})->with(['../../../etc/passwd', 'en/../de', 'english', '', 'zz']);

it('takes the language of the configuration evo() already holds', function () {
    $GLOBALS['evo'] = new class {
        public function getConfig($key, $default = null)
        {
            return $key === 'manager_language' ? 'de' : $default;
        }
    };

    expect(CsrfMessage::text())->toBe(CsrfMessage::text(CsrfMessage::TOKEN, 'de'));
});

it('falls back to English without a built evo()', function () {
    expect(CsrfMessage::text())->toBe(CsrfMessage::text(CsrfMessage::TOKEN, 'en'));
});

it('keeps the machine-readable fields of the JSON refusal and adds the message', function () {
    expect(CsrfMessage::payload())->toBe([
        'error' => 'CSRF token mismatch',
        'code' => 'csrf_token_mismatch',
        'message' => CsrfMessage::text(),
    ])->and(CsrfMessage::payload(CsrfMessage::REFERER, 'en'))->toBe([
        'error' => 'CSRF referer mismatch',
        'code' => 'csrf_referer_mismatch',
        'message' => CsrfMessage::text(CsrfMessage::REFERER, 'en'),
    ]);
});

it('treats an unknown key as the token refusal', function () {
    expect(CsrfMessage::text('nope', 'en'))->toBe(CsrfMessage::text(CsrfMessage::TOKEN, 'en'))
        ->and(CsrfMessage::payload('nope')['code'])->toBe('csrf_token_mismatch');
});

it('recognises the callers that show the response body themselves', function (array $server, array $post, bool $script) {
    expect(CsrfMessage::wantsScriptResponse($server, $post))->toBe($script);
})->with([
    'XHR' => [['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'], [], true],
    'JSON client' => [['HTTP_ACCEPT' => 'application/json'], [], true],
    'AJAX login form' => [[], ['ajax' => '1'], true],
    'page load' => [['HTTP_ACCEPT' => 'text/html,*/*'], [], false],
    'ajax array' => [[], ['ajax' => ['1']], false],
    'ajax=0' => [[], ['ajax' => '0'], false],
]);

it('is used by every CSRF check instead of a hard-coded message', function (string $file, string $removed) {
    $source = file_get_contents(dirname(__DIR__, 4) . '/' . $file);

    expect($source)->toContain('CsrfMessage')
        ->and($source)->not->toContain($removed);
})->with([
    ['core/src/Middleware/VerifyCsrfToken.php', 'The session token has changed or is missing'],
    ['core/functions/preload.php', 'A possible CSRF attempt was detected'],
    ['manager/actions/files.dynamic.php', '<b>Invalid token</b>'],
    ['manager/media/browser/mcpuk/browse.php', 'Invalid CSRF token.'],
]);

it('answers a script caller of the referer check with JSON, not the alert page', function () {
    $source = file_get_contents(dirname(__DIR__, 3) . '/functions/preload.php');
    $function = substr($source, strpos($source, 'function rejectManagerReferer'));

    expect(strpos($function, 'wantsScriptResponse'))->toBeLessThan(strpos($function, 'webAlertAndQuit'))
        ->and($function)->toContain('http_response_code(403)')
        // shutdown callbacks call evo() again after the exit; the refusal must not be printed twice
        ->and(strpos($function, 'static $refused'))->toBeLessThan(strpos($function, 'wantsScriptResponse'))
        // the language comes from the instance under construction: evo() would run the check again
        ->and($function)->toContain("\$evo->getConfig('manager_language'");
});

it('lets the login form alert only the text of a refusal', function (string $template) {
    $source = file_get_contents(dirname(__DIR__, 4) . '/manager/media/style/' . $template . '/login.tpl');

    expect($source)->not->toContain('alert(this.response)')
        ->and($source)->toContain('alert(loginResponseMessage(this))')
        ->and($source)->toContain('body.message')
        ->and($source)->toContain("DOMParser().parseFromString(text, 'text/html')");
})->with(['default', 'common']);
