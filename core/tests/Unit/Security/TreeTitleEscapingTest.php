<?php

/*
|--------------------------------------------------------------------------
| Resource titles taken from the document tree
|--------------------------------------------------------------------------
|
| The tree prints a title once escaped, in data-title-esc and as an argument of an inline handler.
| Both reach scripts decoded, so a page title "<img src=x onerror=...>" became markup again when a
| click on the node opened its tab (tab title innerHTML), in the context menu header and in the
| parent pickers of the resource editor and the move page: an editor's title ran script in the
| administrator's manager. The runtime cases live in manager/media/script/tests/tree-title-escaping.test.js.
|
*/

function treeTitleSource(string $relative): string
{
    return (string) file_get_contents(dirname(__DIR__, 4) . '/' . $relative);
}

it('escapes tree titles in evo.js before they become HTML', function () {
    $script = treeTitleSource('manager/media/style/default/js/evo.js');

    expect($script)->toContain('escapeHtml: function (t) {')
        ->and($script)->toContain("title: evo.escapeHtml(title) + '<small>(' + id + ')</small>'")
        ->and($script)->toContain('f.textContent = this.selectedObjectName;')
        ->and($script)->not->toContain("title: this.selectedObjectName + '<small>")
        ->and($script)->not->toContain("title: title + '<small>")
        ->and($script)->not->toContain("title: target.title + '<small>");
});

it('writes the chosen parent title as text in the resource editor', function () {
    $source = treeTitleSource('manager/actions/mutate_content.dynamic.php');

    expect($source)->toContain('elm.textContent = pId + " (" + pName + ")";')
        ->and($source)->not->toContain('elm.innerHTML = (pId + " (" + pName + ")");');
});

it('writes the chosen parent title as text on the move page', function () {
    $view = treeTitleSource('manager/views/page/move_document.blade.php');

    expect($view)->toContain("label.appendChild(document.createTextNode(' (' + pName + ')'));")
        ->and($view)->toContain("parentName.textContent = @js(ManagerTheme::getLexicon('new_parent')) + ': ';")
        ->and($view)->not->toContain("(' + pName + ')</span>'");
});
