<?php

/*
| A resource keeps its template id after that template is deleted. The resource overview (a=3)
| left $templatename as the empty array of the failed lookup and passed it to entities(), which
| threw "htmlentities(): Argument #1 must be of type string, array given". It now says which
| template is missing instead.
*/

it('reports a missing template on the resource overview instead of failing', function () {
    $view = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/views/page/3.blade.php');

    expect($view)
        ->toContain("->whereKey(\$content['template'])->value('templatename')")
        ->toContain('@elseif($templatename === null)')
        ->toContain("sprintf(ManagerTheme::getLexicon('template_not_found'), (int) \$content['template'])")
        ->not->toContain("where('id', '=', \$content['template'])->get()->toArray()")
        ->not->toContain('entities($templatename');
});

it('names the missing template id in the English message', function () {
    $_lang = [];
    include dirname(__DIR__, 3) . '/lang/en/global.php';

    expect($_lang)->toHaveKey('template_not_found')
        ->and(sprintf($_lang['template_not_found'], 3))->toStartWith('Template #3 not found');
});
