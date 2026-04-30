<?php

test('remove locks confirm dialog normalizes escaped newlines in the default manager theme', function () {
    $defaultThemeJs = file_get_contents(dirname(__DIR__, 3) . '/manager/media/style/default/js/evo.js');

    expect($defaultThemeJs)->toContain("confirm(modx.lang.confirm_remove_locks.replace(/\\\\n/g, '\\n'))");
});

test('uk manager lexicon keeps remove locks confirmation readable', function () {
    $_lang = [];
    include dirname(__DIR__, 3) . '/core/lang/uk/global.php';

    expect($_lang['confirm_remove_locks'])
        ->toContain('\\n\\nПродовжити?')
        ->not->toContain('Прожовжити');
});
