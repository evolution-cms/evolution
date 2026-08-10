<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

/*
|--------------------------------------------------------------------------
| Theme toggle icon wrapper
|--------------------------------------------------------------------------
|
| The frame prints theme icons inside a `<span class="icon">` wrapper. The icons are SVG markup
| owned by the theme, so they go through icon_html(), which marks them as Htmlable. These tests
| check that the wrapper renders the icon unchanged - and that a class-list icon is still escaped
| into its attribute rather than injected as markup.
|
*/

function renderIconWrapper($icon): string
{
    static $compiler = null;

    if ($compiler === null) {
        $cache = sys_get_temp_dir() . '/evo-theme-icon-tests';
        if (!is_dir($cache)) {
            mkdir($cache, 0777, true);
        }
        $compiler = new BladeCompiler(new Filesystem(), $cache);
    }

    $compiled = $compiler->compileString('<span class="icon">{{ icon_html($icon) }}</span>');

    ob_start();
    try {
        eval('?>' . $compiled);
    } catch (\Throwable $exception) {
        ob_end_clean();
        throw $exception;
    }

    return ob_get_clean();
}

test('an SVG theme icon reaches the page unchanged', function () {
    $svg = '<svg class="icon-brightness" viewBox="0 0 24 24"><path d="M12 3v18"/></svg>';

    expect(renderIconWrapper($svg))->toBe('<span class="icon">' . $svg . '</span>');
});

test('a class list theme icon becomes an escaped class attribute', function () {
    expect(renderIconWrapper('fa fa-brightness'))
        ->toBe('<span class="icon"><i class="fa fa-brightness"></i></span>');

    expect(renderIconWrapper('fa" onmouseover="alert(1)'))
        ->toBe('<span class="icon"><i class="fa&quot; onmouseover=&quot;alert(1)"></i></span>');
});

test('a missing theme icon renders an empty wrapper instead of the word Array', function () {
    expect(renderIconWrapper(''))->toBe('<span class="icon"></span>')
        ->and(renderIconWrapper(null))->toBe('<span class="icon"></span>')
        ->and(renderIconWrapper(['fa']))->toBe('<span class="icon"></span>');
});
