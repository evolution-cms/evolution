<?php

/*
|--------------------------------------------------------------------------
| Selecting a run of neighbours on the sort page and in the file manager
|--------------------------------------------------------------------------
|
| Shift+click (or a swipe / a held touch) selects neighbouring items. On the menu index sort page
| (a=56) the run is dragged as one block; in the classic file manager (a=31) Ctrl+A selects the
| whole folder and Del or the one trash icon of the run deletes it. The behaviour itself lives in
| manager/media/script/range-select.js, tested by manager/media/script/tests/range-select.test.js.
|
*/

function rangeSelectionSource(string $relative): string
{
    return (string) file_get_contents(dirname(__DIR__, 4) . '/' . $relative);
}

it('passes the range selection script tests', function () {
    $node = (new Symfony\Component\Process\ExecutableFinder())->find('node');
    if ($node === null) {
        $this->markTestSkipped('Node.js is not installed.');
    }

    $test = dirname(__DIR__, 4) . '/manager/media/script/tests/range-select.test.js';
    $output = evoRunCommand(escapeshellarg($node) . ' --test ' . escapeshellarg($test) . ' 2>&1', $exitCode);

    expect($exitCode)->toBe(0, $output);
});

it('drags the selected run on the sort page and saves every position', function () {
    $page = rangeSelectionSource('manager/actions/mutate_menuindex_sort.dynamic.php');

    expect($page)->toContain('<script src="media/script/range-select.js?v=')
        ->and($page)->toContain("evoRangeSelect.sortable(document.getElementById('sortlist'), {")
        ->and($page)->toContain('renderList();')
        ->and($page)->not->toContain("evo.sortable('.sortableList > li'")
        ->and($page)->toContain("e(\$_lang['sort_select_group_msg'])");
});

it('deletes a file manager selection only by POST, with both tokens, in a folder the manager may write', function () {
    $page = rangeSelectionSource('manager/actions/files.dynamic.php');

    $start = strpos($page, "get_by_key(\$_POST, 'mode') == 'deletemany'");
    expect($start)->not->toBeFalse();
    $branch = substr($page, $start, 700);
    expect($branch)->toContain('if (!$token_check)')
        ->and($branch)->toContain('} elseif (!$currentPathWritable) {')
        ->and($branch)->toContain('fileManagerDeleteSelectionRequest($_POST, $filemanager_path, $startpath, $relative_path, $userGroups, $protected_path)')
        ->and(strpos($branch, '$token_check'))->toBeLessThan(strpos($branch, 'fileManagerDeleteSelectionRequest('))
        ->and($page)->not->toContain("get_by_key(\$_REQUEST, 'mode') == 'deletemany'");

    preg_match('/<form name="fileManagerDeleteMany".*?<\/form>/s', $page, $form);
    expect($form[0] ?? '')->toContain('method="post"')
        ->toContain('<?= csrf_field() ?>')
        ->toContain('name="mode" value="deletemany"')
        ->toContain('name="token" value="<?= $newToken ?>"')
        // the whole selection in one field: PHP drops fields past max_input_vars
        ->toContain('<input type="hidden" name="count" value="">')
        ->toContain('<input type="hidden" name="names" value="">');
});

it('marks every listed entry and its trash icon for the selection script', function () {
    $helpers = rangeSelectionSource('core/functions/actions/files.php');
    $page = rangeSelectionSource('manager/actions/files.dynamic.php');

    expect($helpers)->toContain('<tr class="fm-entry" data-kind="dir" data-name="\' . htmlspecialchars($dirs_array[$i][\'name\'], ENT_QUOTES, \'UTF-8\')')
        ->and($helpers)->toContain('data-kind="file" data-name="\' . htmlspecialchars($files_array[$i][\'name\'], ENT_QUOTES, \'UTF-8\')')
        ->and($helpers)->toContain('<tr class="fm-entry" data-kind="link" data-name="\' . htmlspecialchars($link[\'name\'], ENT_QUOTES, \'UTF-8\')')
        ->and(substr_count($helpers, '<a class="fm-delete" href="javascript:'))->toBe(3)
        ->and($page)->toContain('<script src="media/script/range-select.js?v=')
        ->and($page)->toContain('keyboard: true,')
        ->and($page)->toContain("var own = rows[0].querySelector('a.fm-delete');")
        ->and($page)->toContain('form.elements.names.value = JSON.stringify(rows.map(function (row) {')
        ->and($page)->toContain('form.elements.count.value = String(rows.length);')
        ->and($page)->not->toContain("'names[]'");
});

it('keeps the edit and view row marks of the listing', function () {
    require_once dirname(__DIR__, 3) . '/functions/actions/files.php';

    expect(markRowClass('a/b.txt', 'a/b.txt', 'edit'))->toBe('editRow')
        ->and(markRowClass('a/b.txt', 'a/b.txt', 'view'))->toBe('viewRow')
        ->and(markRowClass('a/b.txt', 'a/b.txt', 'drill'))->toBe('')
        ->and(markRowClass('a/c.txt', 'a/b.txt', 'edit'))->toBe('')
        ->and(markRow('a/b.txt', 'a/b.txt', 'edit'))->toBe(' class="editRow"')
        ->and(markRow('a/c.txt', 'a/b.txt', 'edit'))->toBe('');
});
