<?php

test('tree sort button uses contextual JS handler instead of hardcoded root id', function () {
    $template = file_get_contents(dirname(__DIR__, 3) . '/manager/views/frame/tree.blade.php');

    expect($template)->toContain("onclick=\"evo.tree.openSortMenuIndex();\"")
        ->and($template)->not->toContain('?a=56&id=0');
});

test('both manager themes resolve sort menu index target from current tree context', function () {
    $defaultThemeJs = file_get_contents(dirname(__DIR__, 3) . '/manager/media/style/default/js/evo.js');
    $liquidThemeJs = file_get_contents(dirname(__DIR__, 3) . '/manager/media/style/liquid/js/evo.js');

    foreach ([$defaultThemeJs, $liquidThemeJs] as $themeJs) {
        expect($themeJs)->toContain('getSortMenuIndexTarget: function ()')
            ->and($themeJs)->toContain("d.querySelector('#tree .current')")
            ->and($themeJs)->toContain("d.querySelector('.treeRoot .node')")
            ->and($themeJs)->toContain("openSortMenuIndex: function ()")
            ->and($themeJs)->not->toContain("?a=56&id=0");
    }
});
