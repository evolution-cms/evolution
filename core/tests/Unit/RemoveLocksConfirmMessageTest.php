<?php

test('remove locks confirm dialog normalizes escaped newlines in both manager themes', function () {
    $defaultThemeJs = file_get_contents(dirname(__DIR__, 3) . '/manager/media/style/default/js/modx.js');
    $liquidThemeJs = file_get_contents(dirname(__DIR__, 3) . '/manager/media/style/liquid/js/modx.js');

    foreach ([$defaultThemeJs, $liquidThemeJs] as $themeJs) {
        expect($themeJs)->toContain("confirm(modx.lang.confirm_remove_locks.replace(/\\\\n/g, '\\n'))");
    }
});

test('uk manager lexicon keeps remove locks confirmation readable', function () {
    $_lang = [];
    include dirname(__DIR__, 3) . '/core/lang/uk/global.php';

    expect($_lang['confirm_remove_locks'])
        ->toContain('\\n\\nПродовжити?')
        ->not->toContain('Прожовжити');
});
