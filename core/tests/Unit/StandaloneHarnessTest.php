<?php

// These scripts supply their own globals and autoloader, so execute them in a
// fresh process rather than including them during PHPUnit test discovery.
test('standalone harness completes in its own PHP process', function (string $script) {
    $output = evoRunPhp($script, [], $exitCode);

    expect($exitCode)->toBe(0, $output);
})->with(function () {
    foreach (glob(dirname(__DIR__) . '/standalone/*Test.php') as $file) {
        yield basename($file) => [$file];
    }
});
