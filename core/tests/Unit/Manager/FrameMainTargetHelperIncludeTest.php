<?php

test('manager frame loads main target link helper before modx js', function () {
    $template = file_get_contents(dirname(__DIR__, 4) . '/manager/views/frame/1.blade.php');

    $treeHelper = 'media/script/tree-drop-guard-helper.js?v={{evo()->getVersionData(\'version\')}}';
    $mainTargetHelper = 'media/script/main-target-link-helper.js?v={{evo()->getVersionData(\'version\')}}';
    $modxScript = '{{ManagerTheme::getThemeUrl()}}js/evo.js?v={{evo()->getVersionData(\'version\')}}';

    expect(str_contains($template, $treeHelper))->toBeTrue();
    expect(str_contains($template, $mainTargetHelper))->toBeTrue();
    expect(strpos($template, $mainTargetHelper))->toBeLessThan(strpos($template, $modxScript));
});
