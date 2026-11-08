<?php

/*
| The resource preview iframe loads the front-end's rendering of whatever content a manager
| (possibly a limited editor, not the one previewing) saved on the resource. Without a sandbox,
| same-origin script there can reach window.top, read the manager's CSRF meta tag, and ride the
| previewing manager's session into a same-origin request. This pins the mitigation in place.
*/

it('sandboxes the resource preview iframe without allow-same-origin', function () {
    $source = file_get_contents(dirname(__DIR__, 4) . '/manager/views/page/3.blade.php');

    expect($source)->toContain('id="previewIframe"')
        ->and($source)->toMatch('/<iframe[^>]*id="previewIframe"[^>]*sandbox="([^"]*)"/');

    preg_match('/<iframe[^>]*id="previewIframe"[^>]*sandbox="([^"]*)"/', $source, $matches);
    $tokens = explode(' ', trim($matches[1]));

    expect($tokens)->not->toContain('allow-same-origin')
        ->and($tokens)->toContain('allow-scripts');
});

/*
| The editor's "Preview" button used to window.open() the raw same-origin front-end URL in a new
| window, where an editor's inline content ran with the previewing manager's session. It now opens
| same-origin content in the same opaque-origin sandbox the info page uses; only external reference
| targets still open a plain window. These pin the button to the sandbox.
*/

it('routes a same-origin editor preview through the sandbox, not a raw window', function () {
    $source = file_get_contents(dirname(__DIR__, 4) . '/manager/actions/mutate_content.dynamic.php');

    expect($source)->toContain('openSandboxedPreview(previewUrl)')
        ->and($source)->toContain("previewUrl.lastIndexOf(site, 0) === 0");
});

it('sandboxes the editor preview iframe without allow-same-origin or allow-top-navigation', function () {
    $source = file_get_contents(dirname(__DIR__, 4) . '/manager/actions/mutate_content.dynamic.php');

    expect($source)->toMatch('/<iframe[^>]*id="evoPreviewFrame"[^>]*sandbox="([^"]*)"/');

    preg_match('/<iframe[^>]*id="evoPreviewFrame"[^>]*sandbox="([^"]*)"/', $source, $matches);
    $tokens = explode(' ', trim($matches[1]));

    expect($tokens)->toContain('allow-scripts')
        ->and($tokens)->not->toContain('allow-same-origin')
        ->and($tokens)->not->toContain('allow-top-navigation')
        ->and($tokens)->not->toContain('allow-top-navigation-by-user-activation')
        ->and($tokens)->not->toContain('allow-popups-to-escape-sandbox');
});
