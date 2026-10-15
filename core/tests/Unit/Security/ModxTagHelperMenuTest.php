<?php

/*
| The code editor's tag helper (manager theme ajax.php, a=modxTagHelper) answers with menu items whose
| URL and header evo.js turns into an onclick string that evo.tree.showPopup() evals. Element names
| were entity-escaped without single quotes and pasted between quotes, so a chunk named
| x'-alert(1)-' ran in the administrator's manager. evo.js now passes both as JSON literals (see
| manager/media/script/tests/modx-tag-helper-menu.test.js); the server URL-encodes the name it puts
| in a query string.
*/

it('url-encodes the element name in the links it offers', function () {
    $ajax = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/style/default/ajax.php');

    expect(substr_count($ajax, 'itemname=" . rawurlencode($name)'))->toBe(5)
        ->and($ajax)->not->toContain("itemname=\" . entities(\$name");
});

it('builds the menu click handler from JSON literals', function () {
    $script = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/style/default/js/evo.js');

    expect($script)->toContain("'if(event.shiftKey){evo.openWindow(' + JSON.stringify({ url: String(r[k].url) }) + ')}else{evo.popup(' + JSON.stringify({")
        ->and($script)->not->toContain("title:\\'' + r.header.innerHTML + '\\'");
});
