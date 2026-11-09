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
| targets still open a plain window. Whether a target is same-origin is decided on the parsed URL by
| manager/media/script/preview-target-helper.js, whose cases (relative and absolute same-origin
| links, a subdirectory installation, external links, rejected schemes) live in
| manager/media/script/tests/preview-target-helper.test.js. These pin the button to the helper.
*/

it('routes the editor preview through the origin helper, not a text prefix', function () {
    $source = file_get_contents(dirname(__DIR__, 4) . '/manager/actions/mutate_content.dynamic.php');

    expect($source)->toContain("media/script/preview-target-helper.js')")
        ->and($source)->toContain('var preview = evoPreviewTargetHelper.resolve(target, site, window.location.href);')
        ->and($source)->toContain("if (preview.mode === 'sandbox') {\n            openSandboxedPreview(preview.url);")
        ->and($source)->toContain("window.open(preview.url, 'previeWin', 'noopener,noreferrer');")
        ->and($source)->not->toContain('lastIndexOf(site')
        ->and($source)->not->toContain('window.open(previewUrl');
});

it('opens only http(s) targets and sandboxes every same-origin one', function () {
    $node = (new Symfony\Component\Process\ExecutableFinder())->find('node');
    if ($node === null) {
        $this->markTestSkipped('Node.js is not installed.');
    }

    $test = dirname(__DIR__, 4) . '/manager/media/script/tests/preview-target-helper.test.js';
    $output = evoRunCommand(escapeshellarg($node) . ' --test ' . escapeshellarg($test) . ' 2>&1', $exitCode);

    expect($exitCode)->toBe(0, $output);
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
