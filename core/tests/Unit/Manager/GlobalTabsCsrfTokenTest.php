<?php

test('manager global tabs delegate token-free storage and audited restoration to the shared helper', function () {
    $frame = file_get_contents(dirname(__DIR__, 4) . '/manager/views/frame/1.blade.php');

    expect($frame)
        ->toMatch('~<meta\s+name="csrf-token"\s+content="\{\{ csrf_token\(\) \}\}"\s*/?>~')
        ->toContain('tab_restore_user:')
        ->not->toContain('tab_restore_modules');

    $script = file_get_contents(dirname(__DIR__, 4) . '/manager/media/style/default/js/evo.js');

    expect($script)
        ->not->toContain('__EVO_CSRF_TOKEN__')
        ->toContain('tabsUrlForStorage: function (url)')
        ->toContain('tabsUrlForRestore: function (url)')
        ->toContain('w.evoManagerTabState.storage(url, evo.tabsRestoreOptions())')
        ->toContain('w.evoManagerTabState.restore(url, evo.tabsRestoreOptions())')
        ->toContain('var entry = w.evoManagerTabState.remember(getTabUrl(tab), evo.tabsRestoreOptions())')
        ->toContain('active: w.evoManagerTabState.remember(activeUrl, evo.tabsRestoreOptions())')
        ->toContain('var plan = w.evoManagerTabState.resume(tab, evo.tabsRestoreOptions(), d)')
        ->toContain('var activePlan = w.evoManagerTabState.resume(active, evo.tabsRestoreOptions(), d)')
        ->toContain('startupPlan = w.evoManagerTabState.resume(w.location.href, evo.tabsRestoreOptions(), d)')
        ->toContain('token: evo.tabsCsrfToken()')
        ->toContain("'EVO_Tabs:' + encodeURIComponent(evo.EVO_MANAGER_URL) + ':' + evo.config.tab_restore_user")
        ->toContain("localStorage.setItem('page_url', evo.tabsUrlForStorage(b))");

    // Runtime URL validation and stale-token cases live in manager-tab-state.test.js.
    $helper = file_get_contents(dirname(__DIR__, 4) . '/manager/media/script/manager-tab-state.js');
    expect($helper)
        ->toContain("target.searchParams.delete('_token')")
        ->toContain("target.searchParams.set('_token', options.token)")
        ->toContain('var target = read(url, options)')
        ->toContain("if (!target || !options.token) return ''");
});
