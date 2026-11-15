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

/*
| "Save and continue" saves over XHR and keeps the page, so a URL rendered into it goes stale once
| the alias changes. 3.5.9 rendered makeUrl($id) there and previewed the old alias; the id-based
| URL is resolved by the front-end at click time.
*/

it('previews a document by id so an alias saved in place is followed', function () {
    $source = file_get_contents(dirname(__DIR__, 4) . '/manager/actions/mutate_content.dynamic.php');

    expect(preg_match('/^\s*view: function\(\) \{\n(.*?)^\s{8}\}\n/ms', $source, $matches))->toBe(1);

    expect($matches[1])->toContain("var target = site + 'index.php?id=<?= (int) \$id ?>&z=manprev';")
        ->and($matches[1])->not->toContain('makeUrl')
        ->and($matches[1])->not->toContain('<?= $content[');
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
