<?php

/**
 * The scripts in tests/standalone declare stub classes and global functions (app(), evo()...)
 * that would collide with the suite and with each other, so phpunit.xml keeps them out of the
 * test discovery and each one runs here in a fresh PHP process.
 */

$standaloneScripts = glob(dirname(__DIR__) . '/standalone/*Test.php') ?: [];

test('the standalone scripts are found', function () use ($standaloneScripts) {
    expect($standaloneScripts)->not->toBeEmpty();
});

test('standalone script passes cleanly', function (string $script) {
    $output = evoRunPhp($script, [], $exitCode);

    expect($exitCode)->toBe(0, $output)
        ->and($output)->toContain('PASS:')
        ->and($output)->not->toMatch('/\b(Fatal error|Warning|Notice|Deprecated):/');
})->with(array_combine(array_map('basename', $standaloneScripts), array_map(fn ($script) => [$script], $standaloneScripts)));
