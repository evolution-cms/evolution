<?php

test('manager global tabs delegate token-free storage and audited restoration to the shared helper', function () {
    $frame = file_get_contents(dirname(__DIR__, 4) . '/manager/views/frame/1.blade.php');

    expect($frame)
        ->toMatch('~<meta\s+name="csrf-token"\s+content="\{\{ csrf_token\(\) \}\}"\s*/?>~')
        ->toContain("config('cms.manager_tab_restore.modules'")
        ->toContain('tab_restore_user:');

    $script = file_get_contents(dirname(__DIR__, 4) . '/manager/media/style/default/js/evo.js');

    expect($script)
        ->not->toContain('__EVO_CSRF_TOKEN__')
        ->toContain('tabUrlForStorage: function (url)')
        ->toContain('tabUrlForRestore: function (url)')
        ->toContain('w.evoManagerTabState.storage(url, evo.tabsRestoreOptions())')
        ->toContain('w.evoManagerTabState.restore(url, evo.tabsRestoreOptions())')
        ->toContain('url: evo.tabUrlForStorage(url)')
        ->toContain('active: evo.tabUrlForStorage(activeUrl)')
        ->toContain('var tabUrl = evo.tabUrlForRestore(tab.url)')
        ->toContain('var activeUrl = evo.tabUrlForRestore(data.active)')
        ->toContain('startupUrl = evo.tabUrlForRestore(w.location.href)')
        ->toContain('token: evo.tabsCsrfToken()')
        ->toContain('modules: evo.config.tab_restore_modules')
        ->toContain("'EVO_Tabs:' + encodeURIComponent(evo.EVO_MANAGER_URL) + ':' + evo.config.tab_restore_user")
        ->toContain("localStorage.setItem('page_url', evo.tabUrlForStorage(b))");

    // Runtime URL validation and stale-token cases live in manager-tab-state.test.js.
    $helper = file_get_contents(dirname(__DIR__, 4) . '/manager/media/script/manager-tab-state.js');
    expect($helper)
        ->toContain("target.searchParams.delete('_token')")
        ->toContain("target.searchParams.set('_token', options.token)")
        ->toContain('var target = read(url, options)')
        ->toContain("if (!target || !options.token) return ''");
});
