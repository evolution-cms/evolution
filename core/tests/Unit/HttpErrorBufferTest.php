<?php

use Symfony\Component\Process\Process;

test('HTTP error cleanup stops at buffers PHP cannot remove', function (string $mode, int $level, string $content) {
    // Non-removable buffers must live in a child: neither Pest nor PHP can close them afterwards.
    $process = new Process([PHP_BINARY, dirname(__DIR__) . '/Mocks/http_error_buffers.php', $mode]);
    $process->setTimeout(5);
    $process->run();

    expect($process->getExitCode())->toBe(0, $process->getErrorOutput())
        ->and(json_decode($process->getErrorOutput(), true))->toBe([
            'level' => $level, 'content' => $content, 'warnings' => 0,
        ]);
})->with([
    'nested removable buffers' => ['removable', 1, ''],
    'a cleanable non-removable buffer' => ['locked-cleanable', 2, ''],
    'a non-cleanable non-removable buffer' => ['locked', 2, 'before abort'],
    'a non-cleanable original buffer' => ['baseline-locked', 1, 'before abort'],
    'no original buffer' => ['no-baseline', 0, ''],
]);
