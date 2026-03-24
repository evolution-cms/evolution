<?php

use EvolutionCMS\Controllers\UserRoles\UserRole;

test('it keeps disabled manager permissions when no checkbox payload is submitted', function () {
    $result = UserRole::normalizePermissionsPayload([], ['frames', 'home', 'logout']);

    expect($result)->toBe([
        'frames' => 1,
        'home' => 1,
        'logout' => 1,
    ]);
});

test('it merges required permissions into the submitted role payload', function () {
    $result = UserRole::normalizePermissionsPayload([
        'new_document' => 1,
        'edit_document' => 1,
    ], [
        'frames',
        'home',
        'logout',
    ]);

    expect($result)->toBe([
        'new_document' => 1,
        'edit_document' => 1,
        'frames' => 1,
        'home' => 1,
        'logout' => 1,
    ]);
});
