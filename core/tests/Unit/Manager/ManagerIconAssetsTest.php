<?php

test('every manager CSS icon reference resolves to a generated Tabler SVG mask', function () {
    $root = dirname(__DIR__, 4);
    $directory = $root . '/manager/media/style/common/icons';
    $css = file_get_contents($directory . '/manager-icons.css');
    $icons = json_decode(file_get_contents($directory . '/icons.json'), true, 512, JSON_THROW_ON_ERROR);

    expect($css)->not->toContain('@font-face', '.fa-', 'webfonts', 'Font Awesome');
    expect(is_file($root . '/manager/media/style/common/font-awesome/css/font-awesome.min.css'))->toBeFalse();
    foreach ($icons as $icon) {
        expect($css)->toContain('--evo-icon-' . $icon . ':url("data:image/svg+xml,')
            ->and($css)->toContain('.evo-icon-' . $icon . '{--evo-icon:var(--evo-icon-' . $icon . ')}');
    }

    $files = [];
    foreach (['manager/media', 'assets/modules/store'] as $path) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $path)) as $file) {
            $files[] = $file;
        }
    }
    foreach ($files as $file) {
        if ($file->getExtension() !== 'css' || $file->getFilename() === 'styles.min.css') {
            continue;
        }
        $source = file_get_contents($file->getPathname());
        // Font Awesome is gone, so its font name or a private-use glyph renders nothing (3.5.x merges bring them back)
        expect(preg_match('/Font\s*Awesome/i', $source))->toBe(0, $file->getPathname() . ' still uses the Font Awesome font')
            ->and(preg_match('/content\s*:\s*([\'"])\\\\f[0-9a-f]{3}\1/i', $source))->toBe(0, $file->getPathname() . ' still uses a Font Awesome glyph');
        preg_match_all('/var\(--evo-icon-([a-z0-9-]+)\)/', $source, $matches);
        foreach (array_unique($matches[1]) as $icon) {
            expect($icons)->toContain($icon);
        }
    }
});

test('bundled plugins render icons from the generated SVG set, not Font Awesome', function () {
    $root = dirname(__DIR__, 4);
    $icons = json_decode(file_get_contents($root . '/manager/media/style/common/icons/icons.json'), true, 512, JSON_THROW_ON_ERROR);
    $utilities = ['fw', 'spin', 'spin-hover', 'pulse', 'lg', 'sm', 'xs'];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/assets/plugins', FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $source = file_get_contents($file->getPathname());
        // markup classes and widget / popup 'icon' values
        expect(preg_match('/class=\\\\?["\'][^"\']*\bfa\s+fa-|[\'"]icon[\'"]\s*=>\s*[\'"]fa-/', $source))
            ->toBe(0, $file->getPathname() . ' still uses a Font Awesome icon');
        preg_match_all('/\bevo-icon-([a-z0-9-]+)/', $source, $matches);
        foreach (array_diff(array_unique($matches[1]), $utilities) as $icon) {
            expect($icons)->toContain($icon);
        }
    }
});

test('full and empty trash and open and closed folders retain distinct SVG shapes', function () {
    $css = file_get_contents(dirname(__DIR__, 4) . '/manager/media/style/common/icons/manager-icons.css');
    $shapes = [];
    foreach (['trash', 'trash-filled', 'folder', 'folder-open', 'lock'] as $icon) {
        preg_match('/--evo-icon-' . $icon . ':url\("data:image\/svg\+xml,([^"\r\n]+)"\)/', $css, $match);
        $svg = rawurldecode($match[1] ?? '');
        $document = new DOMDocument();
        expect($document->loadXML($svg))->toBeTrue();
        expect($document->documentElement->getAttribute('viewBox'))->toBe('0 0 24 24');
        $shapes[$icon] = $svg;
    }
    expect($shapes['trash'])->not->toBe($shapes['trash-filled'])
        ->and($shapes['folder'])->not->toBe($shapes['folder-open']);
});
