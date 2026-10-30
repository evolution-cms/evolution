<?php

test('requested manager locales preserve the English translation contract', function (string $locale, string $territory) {
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
        ['manager/includes/lang/country/%s_country.inc.php', '_country_lang'],
        ['manager/includes/lang/errormsg/%s.inc.php', '_lang'],
        ['manager/media/browser/mcpuk/lang/%s.php', 'lang'],
    ];
    $load = static function (string $file, ?string $variable): array {
        $_lang = $_Lang = $_country_lang = $lang = [];
        $result = require $file;

        return $variable === null ? $result : $$variable;
    };
    $check = static function (array $english, array $translated, string $context, bool $allowAdditionalKeys = false) use (&$check): void {
        expect(array_diff_key($english, $translated), $context)->toBe([]);
        if (!$allowAdditionalKeys) {
            expect(array_diff_key($translated, $english), $context)->toBe([]);
        }
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
        $translated = $load($file, $variable);
        // KCFinder's English file defines metadata; other locales may also translate its English message keys.
        $check($load($root . '/' . sprintf($pattern, 'en'), $variable), $translated, $file, $variable === 'lang');
        if ($variable === 'lang') {
            foreach ($translated as $key => $value) {
                expect($value, "$file/$key")->toBeString()->not->toBe('');
                expect(preg_match('//u', $value), "$file/$key UTF-8")->toBe(1);
            }
        }
    }

    require "$root/core/lang/$locale/global.php";
    expect($modx_lang_attribute)->toBe($locale);
    expect($evo_lang_attribute)->toBe($locale);
    expect(strtolower($modx_manager_charset))->toBe('utf-8');
    expect(strtolower($evo_manager_charset))->toBe('utf-8');
    $_lang = [];
    require "$root/assets/modules/store/installer/lang/$locale.inc.php";
    expect($_lang['language_code'])->toBe($locale);
    expect(strtolower($_lang['encoding']))->toBe('utf-8');
    $lang = [];
    require "$root/manager/media/browser/mcpuk/lang/$locale.php";
    expect($lang['_locale'])->toBe($locale . '_' . $territory . '.UTF-8');
    expect(strtolower($lang['_charset']))->toBe('utf-8');
})->with([
    ['ro', 'RO'], ['uz', 'UZ'], ['lt', 'LT'], ['lv', 'LV'],
]);

test('publiccode lists every available manager locale exactly once', function () {
    $root = dirname(__DIR__, 3);
    $metadata = file_get_contents($root . '/publiccode.yml');
    expect(preg_match('/^  availableLanguages:\R((?:    - [a-z]{2}\R)+)/m', $metadata, $block))->toBe(1);
    preg_match_all('/^    - ([a-z]{2})$/m', $block[1], $matches);
    $listed = $matches[1];
    $available = array_map(static fn (string $file): string => basename(dirname($file)), glob($root . '/core/lang/*/global.php'));
    expect(count(array_unique($listed)))->toBe(count($listed));
    sort($listed);
    sort($available);
    expect($listed)->toBe($available);
});
