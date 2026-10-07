<?php

test('package discovery invalidates bootstrap configuration after generating providers and aliases', function () {
    $fixture = dirname(__DIR__, 2) . '/Fixtures/discover-bootstrap-cache.php';
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($fixture) . ' 2>&1', $output, $status);

    expect($status)->toBe(0, implode("\n", $output));
});
