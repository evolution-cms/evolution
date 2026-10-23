<?php

use EvolutionCMS\Support\Formatter\CSSMinify;

test('combined manager CSS keeps embedded SVG icons and rebases theme images', function () {
    $root = dirname(__DIR__, 4);
    $target = $root . '/manager/media/style/default/css/styles.min.css';
    $minifier = new CSSMinify([
        $root . '/manager/media/style/common/icons/manager-icons.css',
        $root . '/manager/media/style/default/css/mainmenu.css',
    ]);
    $css = $minifier->minify($target);

    expect($css)->toContain('data:image/svg+xml,')
        ->and($css)->toContain('--evo-icon-trash-filled:')
        ->and($css)->toContain('../images/misc/logo-navbar.png')
        ->and($css)->not->toContain('font-awesome', 'webfonts/', '@font-face');
});

test('CSS URL rebasing preserves external, embedded, root and fragment URLs', function () {
    $directory = sys_get_temp_dir() . '/evo-css-' . bin2hex(random_bytes(6));
    mkdir($directory . '/source', 0777, true);
    mkdir($directory . '/output');
    $file = $directory . '/source/example.css';
    $source = '.a{background:url("images/photo.jpg?v=2#preview")} '
        . '.b{background:url(data:image/svg+xml;base64,PHN2Zz4=)} '
        . '.c{background:url(https://example.test/icon.svg)} '
        . '.d{background:url(/shared/icon.svg)} .e{filter:url(#filter)} '
        . '.f{background:url(//example.test/icon.svg)}';
    file_put_contents($file, $source);

    try {
        $minifier = new CSSMinify([$file]);
        $css = $minifier->minify($directory . '/output/bundle.css');
        expect($css)->toContain('url("../source/images/photo.jpg?v=2#preview")')
            ->and($css)->toContain('url(data:image/svg+xml;base64,PHN2Zz4=)')
            ->and($css)->toContain('url(https://example.test/icon.svg)')
            ->and($css)->toContain('url(/shared/icon.svg)')
            ->and($css)->toContain('url(#filter)')
            ->and($css)->toContain('url(//example.test/icon.svg)');
    } finally {
        unlink($file);
        rmdir($directory . '/source');
        rmdir($directory . '/output');
        rmdir($directory);
    }
});

test('old CSS caches are rebuilt and rebased caches are recognised', function () {
    $file = tempnam(sys_get_temp_dir(), 'evo-css-cache-');
    try {
        expect(CSSMinify::isCacheCurrent($file))->toBeFalse();
        file_put_contents($file, '/* Evolution CMS: rebased CSS URLs v1 */' . "\n" . '@font-face{font-family:old}');
        expect(CSSMinify::isCacheCurrent($file))->toBeFalse();

        $root = dirname(__DIR__, 4);
        $minifier = new CSSMinify([$root . '/manager/media/style/default/css/mainmenu.css']);
        file_put_contents($file, $minifier->minify($file));
        expect(CSSMinify::isCacheCurrent($file))->toBeTrue();
    } finally {
        unlink($file);
    }
    expect(CSSMinify::isCacheCurrent($file))->toBeFalse();
});
