<?php

test('manager frame exposes legacy tree aliases for TinyMCE4 integrations', function () {
    $frame = file_get_contents(dirname(__DIR__, 4) . '/manager/views/frame/1.blade.php');

    expect($frame)
        ->toContain('tree: {')
        ->toContain("itemToChange: ''")
        ->toContain('selectedObjectName: null')
        ->toContain('window.modx = evo;')
        ->toContain('window.tree = evo.tree;');
});
