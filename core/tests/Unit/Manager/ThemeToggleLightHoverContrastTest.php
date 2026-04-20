<?php

test('lightness theme keeps the theme toggle darker on hover', function () {
    $css = file_get_contents(dirname(__DIR__, 4) . '/manager/media/style/default/css/mainmenu.css');

    expect($css)->toContain('.lightness #mainMenu #settings > #theme > a:hover')
        ->and($css)->toContain('.lightness #mainMenu #settings > #theme > a:focus')
        ->and($css)->toContain('.lightness #mainMenu #settings > #theme > a:active')
        ->and($css)->toContain('color: #2f2f2f;');
});
