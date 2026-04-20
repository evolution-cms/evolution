<?php

test('front controller maps legacy modx api mode to evo api mode', function () {
    $index = file_get_contents(dirname(__DIR__, 3) . '/index.php');

    expect($index)
        ->toContain("define('EVO_API_MODE', defined('MODX_API_MODE') ? (bool)MODX_API_MODE : false);")
        ->toContain("define('MODX_API_MODE', EVO_API_MODE);");
});
