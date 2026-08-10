<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

/*
|--------------------------------------------------------------------------
| Manager frame title
|--------------------------------------------------------------------------
|
| The frame title is built from the site_name setting, which an operator can edit. It is printed
| in two places - the <title> element and the JavaScript configuration object - and each place
| needs its own encoding. These tests exercise both echoes against a payload instead of looking
| for a particular spelling in the template.
|
*/

function renderFrameTitleFragment(string $template, string $siteName): string
{
    static $compiler = null;

    if ($compiler === null) {
        $cache = sys_get_temp_dir() . '/evo-frame-title-tests';
        if (!is_dir($cache)) {
            mkdir($cache, 0777, true);
        }
        $compiler = new BladeCompiler(new Filesystem(), $cache);
    }

    $managerTitle = $siteName . ' - (Evolution CMS Manager)';
    $compiled = $compiler->compileString($template);

    ob_start();
    try {
        eval('?>' . $compiled);
    } catch (\Throwable $exception) {
        ob_end_clean();
        throw $exception;
    }

    return ob_get_clean();
}

test('the site name cannot close the title element', function () {
    $output = renderFrameTitleFragment(
        '<title>{{ $managerTitle }}</title>',
        '</title><script>alert(1)</script>'
    );

    expect($output)->not->toContain('<script>')
        ->and(substr_count($output, '</title>'))->toBe(1);
});

test('an ordinary site name is shown as typed', function () {
    $output = renderFrameTitleFragment('<title>{{ $managerTitle }}</title>', "Bob's Bikes");

    expect(html_entity_decode(strip_tags($output), ENT_QUOTES, 'UTF-8'))
        ->toBe("Bob's Bikes - (Evolution CMS Manager)");
});

test('the JavaScript copy of the title survives quotes and cannot break out of the script', function () {
    $output = renderFrameTitleFragment(
        '<script>var t = @js($managerTitle);</script>',
        'A "quoted" & \'apostrophed\' </script> name'
    );

    // One script element in, one script element out.
    expect(substr_count($output, '<script>'))->toBe(1)
        ->and(substr_count($output, '</script>'))->toBe(1);

    // And no HTML entity leaks into the JavaScript string value.
    expect($output)->not->toContain('&quot;')
        ->and($output)->not->toContain('&#039;')
        ->and($output)->not->toContain('&amp;');
});
