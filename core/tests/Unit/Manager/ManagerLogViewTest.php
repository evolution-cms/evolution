<?php

use EvolutionCMS\Support\Paginate;

function managerLogViewSource(string $relative): string
{
    return (string)file_get_contents(dirname(__DIR__, 4) . '/' . $relative);
}

it('points the last page link at the first row of the last page', function () {
    $paging = (new Paginate(828, 0, 100))->getPagingArray();

    expect($paging['last_link'])->toContain('int_cur_position=800"')
        ->and((new Paginate(800, 0, 100))->getPagingArray()['last_link'])->toContain('int_cur_position=700"');
});

it('reports the current page as an integer', function () {
    expect((new Paginate(200000, 150000, 100))->getCurrentPage())->toBe(1500)
        ->and((new Paginate(828, 800, 100))->getCurrentPage())->toBe(8)
        ->and((new Paginate(828, 0, 0))->getCurrentPage())->toBe(0);
});

it('keeps the page list to a window around the current page', function (int $position, array $window) {
    expect((new Paginate(828, $position, 100))->getPageWindow())->toBe($window);
})->with([
    'first page' => [0, [0, 1, 2, 3, 4]],
    'middle' => [400, [2, 3, 4, 5, 6]],
    'last page' => [800, [4, 5, 6, 7, 8]],
]);

it('lists every page when there are fewer than the window', function () {
    expect((new Paginate(250, 100, 100))->getPageWindow())->toBe([0, 1, 2])
        ->and((new Paginate(0, 0, 100))->getPageWindow())->toBe([]);
});

it('adds the CSRF token to the empty log link of the manager log', function () {
    $source = managerLogViewSource('manager/actions/logging.static.php');

    expect($source)
        ->toContain('$emptyLogLink = \'~<a href=(["\\\'])index\\.php\\?a=55\\1>(.*?)</a>~s\';')
        ->toContain('\'<a href="index.php?a=55&amp;_token=\' . e(csrf_token()) . \'">$2</a>\'')
        ->not->toContain('echo \'<p>\' . $_lang["mgrlog_sortinst"] . \'</p>\';');
});

it('writes the empty log link the same way in every locale', function () {
    // The manager log adds the token by matching this exact link.
    $unmatched = [];
    foreach (glob(dirname(__DIR__, 3) . '/lang/*/global.php') as $file) {
        $_lang = [];
        include $file;
        $text = (string)($_lang['mgrlog_sortinst'] ?? '');
        if (str_contains($text, 'a=55') && !preg_match('~<a href=(["\'])index\.php\?a=55\1>(.*?)</a>~s', $text)) {
            $unmatched[] = basename(dirname($file));
        }
    }

    expect($unmatched)->toBe([]);
});

it('renders the item name column as markup with the stored name escaped', function () {
    $source = managerLogViewSource('manager/actions/logging.static.php');

    expect($source)
        ->toContain("\$itemname = entities((string)\$logentry['itemname'], EvolutionCMS()->getConfig('modx_charset'));")
        ->toContain("'<a href=\"index.php?a=3&amp;id=' . (int)\$logentry['itemid'] . '\">' . \$itemname . '</a>'")
        ->not->toContain('entities($item,');
});

it('filters the manager log by message on the message column', function () {
    $source = managerLogViewSource('manager/actions/logging.static.php');

    expect($source)
        ->toContain("\$logs->where('message', 'LIKE', '%' . \$message . '%')")
        ->not->toContain("\$logs->where('itemname', 'LIKE'");
});

it('looks up the event log context menu element instead of quoting its accessor', function () {
    $source = managerLogViewSource('manager/views/page/eventlog.blade.php');

    expect($source)
        ->toContain('return document.getElementById(@js($cm->id));')
        ->not->toContain("var contextm = '{{\$cm->getClientScriptObject()}}';");
});
