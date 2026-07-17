<?php

test('expired lock cleanup removes locks for inactive session ids', function () {
    $source = file_get_contents(dirname(__DIR__, 3) . '/src/Core.php');

    expect($source)->toContain("ActiveUserLock::whereNotIn('sid', \$userSids)->delete();")
        ->and($source)->not->toContain("ActiveUserSession::whereNotIn('sid', \$userSids)->delete();");
});
