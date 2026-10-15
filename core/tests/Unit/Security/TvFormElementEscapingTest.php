<?php

/*
|--------------------------------------------------------------------------
| TV form elements in the document editor
|--------------------------------------------------------------------------
|
| renderFormElement() printed the saved value of image, file and date TVs into value="..." (and,
| for images, into data-image and a CSS url()) without escaping, and the labels of radio and
| checkbox options as markup. A user who may only edit documents saved `x" autofocus onfocus=...`
| as an image TV and it ran for whoever opened the document next, administrators included. The date
| branch of functions/tv.php also lost its value="..." to an operator precedence slip.
|
| Both copies of the function are rendered, in a fresh process each (see the harness).
|
*/

$renderTvForm = static function (string $copy): array {
    $output = evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/tv-render-harness.php', [$copy], $exitCode, false);
    $decoded = json_decode($output, true);

    expect($exitCode)->toBe(0, $output)
        ->and($decoded)->toBeArray($output)
        ->and($decoded['phpErrors'])->toBe([]);

    return $decoded['results'];
};

$copies = [
    'functions/tv.php' => ['functions/tv.php'],
    'functions/actions/mutate_content.php' => ['functions/actions/mutate_content.php'],
];

it('escapes an editor-saved value in image, file and date inputs', function (string $copy) use ($renderTvForm) {
    $html = $renderTvForm($copy);

    foreach (['image', 'file', 'date'] as $type) {
        expect($html[$type])->toContain('value="x&quot; autofocus onfocus=&quot;alert(1)"')
            ->and($html[$type])->not->toContain('autofocus onfocus="alert(1)');
    }
})->with($copies);

it('keeps ordinary values and empty dates as they were', function (string $copy) use ($renderTvForm) {
    $html = $renderTvForm($copy);

    expect($html['imageClean'])->toContain('value="assets/images/logo.png"')
        ->and($html['dateClean'])->toContain('value="15-10-2026 12:00:00"')
        ->and($html['dateEmpty'])->toContain('class="DatePicker" type="text" value=""');
})->with($copies);

it('escapes option labels of radio and checkbox TVs', function (string $copy) use ($renderTvForm) {
    $html = $renderTvForm($copy);

    foreach (['option', 'checkbox'] as $type) {
        expect($html[$type])->toContain('&lt;img src=x onerror=alert(2)&gt;')
            ->and($html[$type])->not->toContain('<img src=x')
            ->and($html[$type])->toContain('Plain');
    }
})->with($copies);

it('escapes the image preview attributes of the manager copy', function () use ($renderTvForm) {
    $html = $renderTvForm('functions/actions/mutate_content.php');

    expect($html['image'])->toContain('data-image="x&quot; autofocus onfocus=&quot;alert(1)"')
        ->and($html['imageClean'])->toContain("url('https://example.test/assets/images/logo.png')")
        // the value cannot close the CSS string, the url() or the declaration
        ->and($html['imageCss'])->toContain("url('https://example.test/a.jpg\\27 \\29 ; background:url\\28 javascript:alert\\28 3\\29 \\29 ; x:\\28 \\27 ')")
        ->and($html['imageCss'])->not->toContain("url('https://example.test/a.jpg')");
});

it('escapes a value for a quoted CSS string', function (string $input, string $expected) {
    expect(tv_css_string_escape($input))->toBe($expected);
})->with([
    'plain url' => ['https://example.test/a b.png', 'https://example.test/a b.png'],
    'single quote' => ["a'b", 'a\\27 b'],
    'double quote' => ['a"b', 'a\\22 b'],
    'parentheses' => ['a(b)', 'a\\28 b\\29 '],
    'backslash' => ['a\\b', 'a\\5c b'],
    'newline' => ["a\nb", 'a\\a b'],
    'angle brackets' => ['</style>', '\\3c /style\\3e '],
]);

it('returns an empty CSS string for values that are not text', function () {
    expect(tv_css_string_escape(null))->toBe('')
        ->and(tv_css_string_escape(['a']))->toBe('');
});

it('escapes template, category and TV labels in the document and user editors', function (string $file) {
    $source = (string) file_get_contents(dirname(__DIR__, 4) . '/' . $file);

    expect($source)->toContain("\$row['category'] = e(\$row['category_name'] ?? '');")
        ->and($source)->toContain("sanitize_inline_html(\$row['description'])")
        ->and($source)->toContain("sanitize_inline_html(\$row['caption'])")
        ->and($source)->toContain("[*' . e(\$row['name']) . '*]")
        ->and($source)->not->toContain("<span class=\"warning\">' . \$row['caption']");
})->with([
    'document editor' => ['manager/actions/mutate_content.dynamic.php'],
    'user editor' => ['manager/actions/mutate_web_user.dynamic.php'],
]);

it('escapes template and category names in the template select', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/actions/mutate_content.dynamic.php');

    expect($source)->toContain("'>' . e(\$row['templatename']) . \" (\"")
        ->and($source)->toContain('<optgroup label=\"" . e($thisCategory) . "\">');
});
