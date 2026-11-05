<?php

// the user is already deleted by then, so a refused landing page reports a failure that did not happen
test('a deleted user lands on the user list only for a role that may open it', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Controllers/Users/DeleteUser.php');

    expect($source)
        ->toContain("\$landing = \$this->managerTheme->getCore()->hasPermission('edit_user') ? 99 : 2;")
        ->toContain('header("Location: index.php?a={$landing}");')
        ->not->toContain('header("Location: index.php?a=99");');
});
