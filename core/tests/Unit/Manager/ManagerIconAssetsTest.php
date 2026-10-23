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
        preg_match_all('/var\(--evo-icon-([a-z0-9-]+)\)/', file_get_contents($file->getPathname()), $matches);
        foreach (array_unique($matches[1]) as $icon) {
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
