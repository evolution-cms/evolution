<?php

/*
|--------------------------------------------------------------------------
| Manager actions that used to check less than the page that leads to them
|--------------------------------------------------------------------------
|
| The move form (a=51) enforces the per-document ACL, but the move action (a=52) it submits to
| only checked the global save_document right, so the form was the only thing standing between a
| user and a document outside their groups. Likewise phpinfo and the system info page expose the
| server environment and the database layout, which is settings-level detail, but they were open
| to anyone holding the much more common logs right.
|
*/

function controllerSource(string $class): string
{
    return (string)file_get_contents(dirname(__DIR__, 3) . '/src/Controllers/' . $class . '.php');
}

it('checks the source document ACL in the move action, not only in the move form', function () {
    $source = controllerSource('MoveDocument');

    $handle = strpos($source, 'protected function handle()');
    $display = strpos($source, 'protected function processDisplay()');
    $check = strpos($source, "\$this->checkDocumentPermission(\$document->getKey(), 'access_permission_denied');", $handle);

    expect($handle)->not->toBeFalse()
        ->and($check)->not->toBeFalse()
        ->and($check)->toBeLessThan($display)
        ->and($check)->toBeLessThan(strpos($source, '$document->save();', $handle));

    // the form keeps its own check
    expect(strpos($source, "\$this->checkDocumentPermission(\$document->getKey(), 'access_permission_denied');", $display))
        ->not->toBeFalse();
});

it('gates the phpinfo and system info pages on the settings right', function (string $class) {
    $source = controllerSource($class);

    expect($source)
        ->toContain("hasPermission('settings')")
        ->and($source)->not->toContain("hasPermission('logs')");
})->with(['Phpinfo', 'SystemInfo']);
