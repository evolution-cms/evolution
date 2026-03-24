<?php

test('frame menu site checks the home permission before adding dashboard', function () {
    $controllerPath = dirname(__DIR__, 3) . '/src/Controllers/Frame.php';
    $controller = file_get_contents($controllerPath);

    expect(str_contains($controller, "hasPermission('home')"))->toBeTrue();
    expect(str_contains($controller, "'index.php?a=2'"))->toBeTrue();
});
