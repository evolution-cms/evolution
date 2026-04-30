<?php

test('tree sort button uses contextual JS handler instead of hardcoded root id', function () {
    $template = file_get_contents(dirname(__DIR__, 3) . '/manager/views/frame/tree.blade.php');

    expect($template)->toContain("onclick=\"evo.tree.openSortMenuIndex();\"")
        ->and($template)->not->toContain('?a=56&id=0');
});

test('default manager theme resolves sort menu index target from current tree context', function () {
    $defaultThemeJs = file_get_contents(dirname(__DIR__, 3) . '/manager/media/style/default/js/evo.js');

    expect($defaultThemeJs)->toContain('getSortMenuIndexTarget: function ()')
        ->and($defaultThemeJs)->toContain("d.querySelector('#tree .current')")
        ->and($defaultThemeJs)->toContain("d.querySelector('.treeRoot .node')")
        ->and($defaultThemeJs)->toContain("openSortMenuIndex: function ()")
        ->and($defaultThemeJs)->not->toContain("?a=56&id=0");
});
