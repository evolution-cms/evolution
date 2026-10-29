<?php

test('added locales contain every English translation key', function (string $locale) {
    if (!defined('MGR_DIR')) {
        define('MGR_DIR', 'manager');
    }
    $root = dirname(__DIR__, 3);
    $paths = [
        ['core/lang/%s/global.php', '_lang'],
        ['core/lang/%s/pagination.php', null],
        ['core/lang/%s/validation.php', null],
        ['assets/modules/store/installer/lang/%s.inc.php', '_lang'],
        ['assets/modules/store/lang/%s.php', '_Lang'],
        ['assets/plugins/updater/lang/%s.php', '_lang'],
        ['install/src/lang/%s.inc.php', '_lang'],
        ['manager/includes/lang/errormsg/%s.inc.php', '_lang'],
        ['manager/includes/lang/country/%s_country.inc.php', '_country_lang'],
        ['manager/media/browser/mcpuk/lang/%s.php', 'lang'],
    ];
    $load = static function (string $file, ?string $variable): array {
        $_lang = $_Lang = $_country_lang = $lang = [];
        $result = require $file;

        return $variable === null ? $result : $$variable;
    };
    $check = static function (array $english, array $translated, string $context) use (&$check): void {
        expect(array_diff_key($english, $translated), $context)->toBe([]);
        expect(array_diff_key($translated, $english), $context)->toBe([]);
        foreach ($english as $key => $value) {
            if (is_array($value)) {
                expect($translated[$key], "$context/$key")->toBeArray();
                $check($value, $translated[$key], "$context/$key");
            } elseif (is_string($value) && $value !== '') {
                expect($translated[$key], "$context/$key")->toBeString()->not->toBe('');
                expect(preg_match('//u', $translated[$key]), "$context/$key UTF-8")->toBe(1);
                if (str_starts_with((string) $key, '_')) {
                    continue;
                }
                foreach (['/%(?:\d+\$)?[-+0-9.]*[bcdeEfFgGosuxX]/', '/(?<![\w:]):[a-zA-Z_][a-zA-Z_0-9]*/', '/\[\+[^\]]+\+\]/', '/<\/?[a-zA-Z][a-zA-Z0-9]*(?=[\s>\/])/'] as $pattern) {
                    preg_match_all($pattern, $value, $expected);
                    preg_match_all($pattern, $translated[$key], $actual);
                    sort($expected[0]);
                    sort($actual[0]);
                    expect($actual[0], "$context/$key placeholders")->toBe($expected[0]);
                }
            }
        }
    };
    foreach ($paths as [$pattern, $variable]) {
        $file = $root . '/' . sprintf($pattern, $locale);
        expect(is_file($file), $file)->toBeTrue();
        $english = $load($root . '/' . sprintf($pattern, 'en'), $variable);
        $translated = $load($file, $variable);
        $check($english, $translated, $file);
    }
})->with(explode(' ', 'kk ky tg tk ka et hy'));

test('added locales declare their language and UTF-8 encoding', function (string $locale, string $territory) {
    if (!defined('MGR_DIR')) {
        define('MGR_DIR', 'manager');
    }
    $root = dirname(__DIR__, 3);
    $_lang = $lang = [];
    $modx_lang_attribute = $evo_lang_attribute = null;
    $modx_manager_charset = $evo_manager_charset = null;
    require "$root/core/lang/$locale/global.php";
    expect($modx_lang_attribute)->toBe($locale);
    expect($evo_lang_attribute)->toBe($locale);
    expect(strtolower($modx_manager_charset))->toBe('utf-8');
    expect(strtolower($evo_manager_charset))->toBe('utf-8');

    require "$root/assets/modules/store/installer/lang/$locale.inc.php";
    expect($_lang['language_code'])->toBe($locale);
    expect(strtolower($_lang['encoding']))->toBe('utf-8');
    $_lang = [];
    require "$root/install/src/lang/$locale.inc.php";
    expect($_lang['language_code'])->toBe($locale);

    require "$root/manager/media/browser/mcpuk/lang/$locale.php";
    expect($lang['_locale'])->toBe($locale . '_' . $territory . '.UTF-8');
    expect(strtolower($lang['_charset']))->toBe('utf-8');
})->with([
    ['kk', 'KZ'], ['ky', 'KG'], ['tg', 'TJ'], ['tk', 'TM'], ['ka', 'GE'],
    ['et', 'EE'], ['hy', 'AM'],
]);
