<?php

it('uses Ukrainian text for config-file-defined setting notices', function () {
    $_lang = [];
    include dirname(__DIR__, 3) . '/lang/uk/global.php';

    expect($_lang['setting_from_file'])
        ->toContain('Значення параметра задано')
        ->not->toContain('Значение параметра задано');
});
