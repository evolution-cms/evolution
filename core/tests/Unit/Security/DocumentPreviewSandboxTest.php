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
