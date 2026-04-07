<?php

test('theme toggle uses the shared settings icon wrapper', function () {
    $view = file_get_contents(dirname(__DIR__, 4) . '/manager/views/frame/1.blade.php');

    expect($view)->toContain('<li id="theme">')
        ->and($view)->toContain('<a id="treeMenu_theme_dark" onclick="evo.tree.toggleTheme(event)" title="{{ManagerTheme::getLexicon(\'manager_theme_mode_title\')}}">')
        ->and($view)->toContain('<span class="icon">{!! $_style[\'icon_theme\'] !!}</span>');
});
