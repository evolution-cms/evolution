<?php

it('keeps scheduled and actual publish dates separate in the resource editor', function () {
    $template = file_get_contents(dirname(__DIR__, 4) . '/manager/actions/mutate_content.dynamic.php');

    expect($template)
        ->toContain("ManagerTheme::getLexicon('page_data_scheduled_publishdate')")
        ->toContain("ManagerTheme::getLexicon('page_data_scheduled_publishdate_help')")
        ->toContain("ManagerTheme::getLexicon('page_data_actual_publishdate')")
        ->toContain("ManagerTheme::getLexicon('page_data_actual_publishdate_help')")
        ->toContain("get_by_key(\$content, 'publishedon', 0, 'is_scalar')")
        ->toContain('id="publishedon"')
        ->toContain('readonly="readonly"')
        ->toContain('name="pub_date"')
        ->not->toMatch('/page_data_publishdate.*?name="pub_date"/s');
});

it('defines scheduled and actual publish date labels for every manager language', function () {
    foreach (glob(dirname(__DIR__, 3) . '/lang/*/global.php') as $file) {
        $_lang = [];
        include $file;

        expect($_lang)
            ->toHaveKey('page_data_scheduled_publishdate')
            ->toHaveKey('page_data_scheduled_publishdate_help')
            ->toHaveKey('page_data_actual_publishdate')
            ->toHaveKey('page_data_actual_publishdate_help')
            ->and($_lang['page_data_scheduled_publishdate'])
            ->not->toBe($_lang['page_data_actual_publishdate']);
    }
});

it('keeps the English scheduled date label distinct from the actual publication timestamp', function () {
    $_lang = [];
    include dirname(__DIR__, 3) . '/lang/en/global.php';

    expect($_lang['page_data_scheduled_publishdate'])
        ->toBe('Scheduled publish date')
        ->and($_lang['page_data_actual_publishdate'])
        ->toBe('Actual publish date')
        ->and($_lang['page_data_actual_publishdate_help'])
        ->toContain('publishedon');
});
