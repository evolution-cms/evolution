<?php

use EvolutionCMS\Support\AdminAccountGuard;

test('only an administrator may manage an administrator account', function () {
    expect(AdminAccountGuard::mayManageAccount(1, 1))->toBeTrue()
        ->and(AdminAccountGuard::mayManageAccount(2, 1))->toBeFalse()
        ->and(AdminAccountGuard::mayManageAccount(0, 1))->toBeFalse();
});

test('any manager may manage ordinary or not yet existing accounts', function () {
    expect(AdminAccountGuard::mayManageAccount(2, 3))->toBeTrue()
        ->and(AdminAccountGuard::mayManageAccount(2, null))->toBeTrue()
        ->and(AdminAccountGuard::mayManageAccount(1, null))->toBeTrue();
});

test('only an administrator may hand out the administrator role', function () {
    expect(AdminAccountGuard::mayAssignRole(2, 1))->toBeFalse()
        ->and(AdminAccountGuard::mayAssignRole(1, 1))->toBeTrue()
        ->and(AdminAccountGuard::mayAssignRole(2, 3))->toBeTrue();
});

test('the user save and delete controllers apply the guard', function () {
    $root = dirname(__DIR__, 3) . '/src/Controllers/Users/';
    $edit = (string) file_get_contents($root . 'EditOrNewUser.php');
    $delete = (string) file_get_contents($root . 'DeleteUser.php');

    expect($edit)->toContain('AdminAccountGuard::mayManageAccount(')
        ->and($edit)->toContain('AdminAccountGuard::mayAssignRole(')
        ->and($delete)->toContain('AdminAccountGuard::mayManageAccount(');
});
