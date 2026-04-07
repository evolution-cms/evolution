<?php

test('default theme toggle does not stay marked as active menu item', function () {
    $script = file_get_contents(dirname(__DIR__, 4) . '/manager/media/style/default/js/modx.js');

    expect($script)->toContain("a.id === 'treeMenu_theme_dark' || a.closest('#theme')")
        ->and($script)->toContain("themeItem.classList.remove('active');")
        ->and($script)->toContain('return;');
});
