<?php

/*
|--------------------------------------------------------------------------
| Names printed by the legacy manager pages
|--------------------------------------------------------------------------
|
| A user with save_template saves TV captions, TV names and template names exactly as posted; the
| TV sort page (a=117) printed them raw, so its next visitor - typically an administrator - ran the
| markup. Group names (manage_groups) were printed raw in the access lists of the resource, user,
| module and TV forms.
|
*/

function legacyManagerSource(string $relative): string
{
    return (string) file_get_contents(dirname(__DIR__, 4) . '/' . $relative);
}

it('escapes TV captions, TV names and the template name on the TV sort page', function () {
    $source = legacyManagerSource('manager/actions/mutate_template_tv_rank.dynamic.php');

    expect($source)->toContain("e((string)\$caption)")
        ->and($source)->toContain("[*' . e((string)\$row['name']) . '*]")
        ->and($source)->toContain("e((string)\$templatename) . '<small>(' . \$id . ')</small>'")
        ->and($source)->not->toContain("' . \$caption . '")
        ->and($source)->not->toContain("\$templatename . '<small>");
});

it('escapes group names in the access lists', function (string $file, string $expected) {
    expect(legacyManagerSource($file))->toContain($expected);
})->with([
    'resource' => ['manager/actions/mutate_content.dynamic.php', "'\">' . e((string)\$row['name']) . '</label></li>'"],
    'user' => ['manager/actions/mutate_web_user.dynamic.php', "' />' . e((string)\$row['name']) . '</label><br />'"],
    'module' => ['manager/actions/mutate_module.dynamic.php', "/> ' . e((string)\$row['name']) . \"</label><br />"],
    'tv' => ['manager/views/page/tmplvar.blade.php', "/> \" . e((string)\$row['name']) . \"</label></li>\""],
]);
