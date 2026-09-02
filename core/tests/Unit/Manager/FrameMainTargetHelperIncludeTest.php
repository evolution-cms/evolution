<?php

test('manager frame loads navigation and restoration helpers before versioned evo js', function () {
    $template = file_get_contents(dirname(__DIR__, 4) . '/manager/views/frame/1.blade.php');

    $treeHelper = 'media/script/tree-drop-guard-helper.js?v={{evo()->getVersionData(\'version\')}}';
    $mainTargetHelper = 'media/script/main-target-link-helper.js?v={{evo()->getVersionData(\'version\')}}';
    $tabHelper = "@revision(MGR_DIR . '/media/script/manager-tab-state.js')";
    $evoScript = "@revision(MGR_DIR . '/' . ManagerTheme::getThemeDir(false) . 'js/evo.js')";

    expect(str_contains($template, $treeHelper))->toBeTrue();
    expect(str_contains($template, $mainTargetHelper))->toBeTrue();
    expect($template)->toContain($tabHelper)->toContain($evoScript);
    expect(strpos($template, $mainTargetHelper))->toBeLessThan(strpos($template, $tabHelper));
    expect(strpos($template, $tabHelper))->toBeLessThan(strpos($template, $evoScript));
});
