<?php

test('manager global tabs refresh persisted csrf tokens before restoring urls', function () {
    $frame = file_get_contents(dirname(__DIR__, 4) . '/manager/views/frame/1.blade.php');

    expect($frame)
        ->toContain('csrf_token: @js(csrf_token())');

    foreach (['default', 'liquid'] as $theme) {
        $script = file_get_contents(dirname(__DIR__, 4) . "/manager/media/style/{$theme}/js/evo.js");

        expect($script)
            ->toContain("tabsCsrfPlaceholder: '__EVO_CSRF_TOKEN__'")
            ->toContain('tabUrlForStorage: function (url)')
            ->toContain('tabUrlForRestore: function (url)')
            ->toContain('url: evo.tabUrlForStorage(url)')
            ->toContain('active: evo.tabUrlForStorage(activeUrl)')
            ->toContain('url: evo.tabUrlForRestore(tab.url)')
            ->toContain('url: evo.tabUrlForRestore(data.active)')
            ->toContain('evo.tabUrlForRestore(href)')
            ->toContain("localStorage.setItem('page_url', evo.tabUrlForStorage(b))");
    }
});
