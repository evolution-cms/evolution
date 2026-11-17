<?php

/*
| ht.access and ng.inx refuse every path segment that starts with a dot, so the media browser's
| fallback icons "..png" and ".image.png" answered 403/404 for every file without its own icon.
*/

$root = dirname(__DIR__, 4);
$browser = $root . '/manager/media/browser/mcpuk';

test('no file icon of a media browser theme is a dotfile the web server refuses', function () use ($browser) {
    $icons = glob($browser . '/themes/*/img/files/{big,small}/{,.}*.png', GLOB_BRACE);

    expect($icons)->not->toBeEmpty()
        ->and(array_values(array_filter($icons, fn ($icon) => basename($icon)[0] === '.')))->toBe([]);
});

test('the fallback icons the media browser asks for exist in every theme', function () use ($browser) {
    $php = (string) file_get_contents($browser . '/core/browser.php');
    $files = (string) file_get_contents($browser . '/js/browser/files.js');
    $clipboard = (string) file_get_contents($browser . '/js/browser/clipboard.js');

    expect($php)->toContain('/img/files/big/generic-file.png"')
        ->and($files)->toContain("icon = 'generic-image';")
        ->and($files)->toContain("icon = 'generic-file';")
        ->and($files)->toContain("var icon = file.bigIcon ? _.getFileExtension(file.name) : 'generic-file';")
        ->and($clipboard)->toContain("icon = 'generic-image';")
        ->and($clipboard)->toContain("icon = 'generic-file';")
        ->and($files . $clipboard)->not->toMatch("/icon = '\\.(image)?';/");

    foreach (glob($browser . '/themes/*', GLOB_ONLYDIR) as $theme) {
        foreach (['big', 'small'] as $size) {
            foreach (['generic-file', 'generic-image'] as $name) {
                expect(is_file("$theme/img/files/$size/$name.png"))->toBeTrue(basename($theme) . " $size $name");
            }
        }
    }
});

test('the web server rules that refuse dotfiles stay in place', function () use ($root) {
    expect((string) file_get_contents($root . '/ht.access'))->toContain('RewriteCond %{REQUEST_URI} (^|/)\. [NC]')
        ->and((string) file_get_contents($root . '/ng.inx'))->toContain('location ~ (^|/)\. { access_log off; return 404; }');
});
