<?php

test('lightness theme keeps active menu items readable', function () {
    $stylesheet = file_get_contents(dirname(__DIR__, 4) . '/manager/media/style/default/css/mainmenu.css');

    expect($stylesheet)->toContain('.lightness #mainMenu .nav > li.active > a {')
        ->and($stylesheet)->toContain('.lightness #mainMenu.show .nav > li.dropdown.hover > a {')
        ->and($stylesheet)->toContain('color: #464646;')
        ->and($stylesheet)->not->toContain(".lightness #mainMenu .nav > li.active > a {\n    color: #fff;")
        ->and($stylesheet)->not->toContain(".lightness #mainMenu.show .nav > li.dropdown.hover > a {\n    color: #fff;");
});
