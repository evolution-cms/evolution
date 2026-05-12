<?php

test('manage elements view options panel has scoped spacing rules', function () {
    $basePath = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR;
    $helper = file_get_contents($basePath . 'manager/views/page/resources/helper/switchButtons.blade.php');
    $mainCss = file_get_contents($basePath . 'manager/media/style/default/css/main.css');

    expect($helper)
        ->toContain('class="form-group form-inline switchForm"')
        ->toContain('name="cb_icons"')
        ->toContain('name="fontsize"')
        ->and($mainCss)
        ->toContain('.switchForm .form-row { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 1rem;')
        ->toContain('.switchForm label { display: inline-flex; align-items: center; gap: 0.35rem;')
        ->toContain('.switchForm input[type="checkbox"], .switchForm input[type="radio"] { margin: 0;')
        ->toContain('.switchForm .columns, .switchForm .fontsize { width: 5rem;');
});
