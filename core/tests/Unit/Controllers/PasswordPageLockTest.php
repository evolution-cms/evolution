<?php

use EvolutionCMS\Controllers\Password;

// the form changes the viewer's own password only, so another manager on it conflicts with nothing
test('the change password page is never locked by another manager', function () {
    $controller = (new ReflectionClass(Password::class))->newInstanceWithoutConstructor();

    expect($controller->checkLocked())->toBeNull();
});
