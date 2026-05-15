<?php

test('manage elements view options panel has scoped spacing rules', function () {
    $basePath = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR;
    $helper = file_get_contents($basePath . 'manager/views/page/resources/helper/switchButtons.blade.php');
    $mainCss = file_get_contents($basePath . 'manager/media/style/default/css/main.css');

    expect($helper)
        ->toContain('class="form-group switchForm"')
        ->not->toContain('form-inline switchForm')
        ->toContain('name="cb_icons"')
        ->toContain('name="fontsize"')
        ->and($mainCss)
        ->toContain('.switchForm { display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem 1rem;')
        ->toContain('.switchForm .form-row { display: flex; flex: 0 1 auto; flex-wrap: wrap; align-items: center; gap: 0.5rem 0.75rem;')
        ->toContain('.switchForm label { display: inline-flex; align-items: center; gap: 0.35rem; margin: 0; min-height: 2rem;')
        ->toContain('.switchForm input[type="checkbox"], .switchForm input[type="radio"] { flex: 0 0 auto; margin: 0;')
        ->toContain('.switchForm .columns, .switchForm .fontsize { flex: 0 0 5rem; width: 5rem; min-width: 5rem;')
        ->toContain('.switchForm .optionsReset { display: flex; align-items: center; margin: 0;')
        ->toContain('@media (max-width: 480px)');
});
