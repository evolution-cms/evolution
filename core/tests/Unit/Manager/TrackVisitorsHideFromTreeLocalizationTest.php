<?php

it('uses dedicated hide-from-tree lexicon keys in the resource editor', function () {
    $template = file_get_contents(dirname(__DIR__, 4) . '/manager/actions/mutate_content.dynamic.php');

    expect($template)
        ->toContain("ManagerTheme::getLexicon('hide_from_tree_title')")
        ->toContain("ManagerTheme::getLexicon('hide_from_tree_help')")
        ->not->toMatch("/ManagerTheme::getLexicon\\('track_visitors_title'\\).*?name=\"hide_from_treecheck\"/s");
});

it('keeps visitor tracking and hide-from-tree translations separate', function () {
    foreach (glob(dirname(__DIR__, 3) . '/lang/*/global.php') as $file) {
        $_lang = [];
        include $file;

        expect($_lang)
            ->toHaveKey('track_visitors_title')
            ->toHaveKey('track_visitors_message')
            ->toHaveKey('hide_from_tree_title')
            ->toHaveKey('hide_from_tree_help')
            ->and($_lang['track_visitors_title'])
            ->not->toBe($_lang['hide_from_tree_title'])
            ->and($_lang['track_visitors_message'])
            ->not->toBe($_lang['hide_from_tree_help']);
    }
});

it('restores the English visitor tracking setting text', function () {
    $_lang = [];
    include dirname(__DIR__, 3) . '/lang/en/global.php';

    expect($_lang['track_visitors_title'])
        ->toBe('Log visits (stats)')
        ->and($_lang['track_visitors_message'])
        ->toContain('visitor tracking or statistics add-on')
        ->not->toContain('child resources')
        ->and($_lang['hide_from_tree_title'])
        ->toBe('Show child resources')
        ->and($_lang['hide_from_tree_help'])
        ->toContain('child resources in the document tree');
});
