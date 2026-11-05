<?php

// #2553: the password is already saved by then, so a refused landing page reports a failure that did not happen
test('a saved password lands on a page every role that may change it can open', function () {
    $core = dirname(__DIR__, 3);
    $controller = (string) file_get_contents($core . '/src/Controllers/Users/ChangePassword.php');
    $theme = (string) file_get_contents($core . '/src/ManagerTheme.php');
    $dashboard = (string) file_get_contents(dirname($core) . '/manager/views/page/2.blade.php');

    expect($controller)
        ->toContain('header("Location: index.php?a=2");')
        ->not->toContain('Location: index.php?a=99')
        ->and($theme)->toMatch('/^\s*2 => null,/m')
        ->and($dashboard)->not->toContain('error_no_privileges');
});

test('the user list stays closed to roles without edit_user', function () {
    $list = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/actions/web_user_management.static.php');

    expect($list)->toContain("hasPermission('edit_user')");
});
