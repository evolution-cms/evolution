<?php

/*
|--------------------------------------------------------------------------
| File and folder names echoed into the classic file manager
|--------------------------------------------------------------------------
|
| A user with file_manager may create a folder whose name holds < > " ' (the newfolder handler
| only strips slashes, unlike rename, which also rejects those characters). The current folder
| path was then written into a <script> block with addslashes(), which does not neutralise
| </script>, so navigating into such a folder ran script in the manager. The image preview link
| built its onclick argument with addslashes($rel_web) inside a double-quoted attribute - a name
| containing " escaped the attribute - and viewfile() dropped the value into innerHTML.
|
| These assertions read the shipped sources: constraining each value where it is produced covers
| every sink it feeds.
|
*/

function fileManagerSource(string $relative): string
{
    return (string) file_get_contents(dirname(__DIR__, 4) . '/' . $relative);
}

it('emits the current path as a JSON literal, not through addslashes', function () {
    $source = fileManagerSource('manager/actions/files.dynamic.php');

    expect($source)
        ->toContain('var current_path = <?= js_json((string) $relative_path) ?>;')
        ->and($source)->not->toContain("var current_path = '<?= addslashes(\$relative_path) ?>';");
});

it('builds the image preview without innerHTML string concatenation', function () {
    $source = fileManagerSource('manager/actions/files.dynamic.php');

    expect($source)
        ->toContain('var img = document.createElement(\'img\');')
        ->and($source)->toContain('img.src = url;')
        ->and($source)->not->toContain("el.innerHTML = '<img src=\"' + url + '\" />';");
});

it('url-encodes the preview path instead of addslashes in a double-quoted attribute', function () {
    $source = fileManagerSource('core/functions/actions/files.php');

    expect($source)
        ->toContain("onclick=\"viewfile(\\'../' . implode('/', array_map('rawurlencode', explode('/', \$rel_web)))")
        ->and($source)->not->toContain("onclick=\"viewfile(\\'../' . addslashes(\$rel_web)");
});

it('still escapes displayed file and folder names as HTML', function () {
    // the fix must not regress the existing htmlspecialchars on the name shown in each row
    $source = fileManagerSource('core/functions/actions/files.php');

    expect(substr_count($source, 'htmlspecialchars($file'))->toBeGreaterThanOrEqual(3);
});
