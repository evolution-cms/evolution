<?php

/** Storage and clock regressions run in fresh processes so autoload checks stay meaningful. */
test('file storage preserves upstream behavior without eager Carbon startup', function (string $mode) {
    $output = evoRunPhp(dirname(__DIR__).'/Fixtures/scalar-file-clock.php', [$mode], $status);
    expect($status)->toBe(0, $output)->and($output)->toContain('PASS '.$mode);
})->with(['cache-system', 'cache-frozen', 'session-system', 'session-frozen', 'drivers']);
