<?php

test('the icon-only button beside a menu item (Clear cache) centres its SVG icon', function () {
    $css = file_get_contents(dirname(__DIR__, 4) . '/manager/media/style/default/css/mainmenu.css');

    // menu icons are inline SVGs: a rule for icon-font elements alone left their label gap on the right
    expect($css)->toMatch('/#mainMenu \.nav li\.item-group > \*:not\(:first-child\) svg \{\s*margin-right: 0;/')
        ->and($css)->toMatch('/#mainMenu \.nav li\.item-group > \*:not\(:first-child\) \{\s*vertical-align: middle;/');
});
