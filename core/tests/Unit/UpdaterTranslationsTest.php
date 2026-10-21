<?php

test('updater locales preserve English keys and formatting contracts', function (string $locale) {
    $directory = dirname(__DIR__, 3) . '/assets/plugins/updater/lang/';
    $load = static function (string $file): array {
        $_lang = [];
        require $file;

        return $_lang;
    };
    $english = $load($directory . 'en.php');
    $translated = $load($directory . $locale . '.php');

    expect(array_diff_key($english, $translated))->toBe([]);
    expect(array_diff_key($translated, $english))->toBe([]);

    $source = file_get_contents($directory . $locale . '.php');
    preg_match_all('/\$_lang\[[\x27\x22]([^\x27\x22]+)[\x27\x22]\]\s*=/', $source, $assignments);
    expect(count($assignments[1]))->toBe(count(array_unique($assignments[1])));

    foreach ($english as $key => $value) {
        expect($translated[$key], "$locale/$key")->toBeString()->not->toBe('');
        expect(preg_match('//u', $translated[$key]), "$locale/$key UTF-8")->toBe(1);

        foreach (['/%(?:\d+\$)?[-+0-9.]*[bcdeEfFgGosuxX]/', '/<[^>]+>/', '~https?://[^\s\x22<>]+~', '/php artisan make:site update/'] as $pattern) {
            preg_match_all($pattern, $value, $expected);
            preg_match_all($pattern, $translated[$key], $actual);
            expect($actual[0], "$locale/$key formatting")->toBe($expected[0]);
        }
    }
})->with(explode(' ', 'uk en az be bg cs da de es fa fi fr he it ja nl nn pl pt sk sv zh'));
