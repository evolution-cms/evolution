<?php

// Selected files and legacy alias lookup must execute the same controller once.
test('pinned and automatic files run the controller once', function () {
    $output = evoRunPhp(dirname(__DIR__) . '/Fixtures/template-pinned-controller.php', [], $status);

    expect($status)->toBe(0, $output);
});
