<?php

test('package discovery invalidates bootstrap configuration after generating providers and aliases', function () {
    $fixture = dirname(__DIR__, 2) . '/Fixtures/discover-bootstrap-cache.php';
    $output = evoRunPhp($fixture, [], $status);

    expect($status)->toBe(0, $output);
});
