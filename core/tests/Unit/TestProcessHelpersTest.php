<?php

/*
|--------------------------------------------------------------------------
| Process helpers from tests/Pest.php
|--------------------------------------------------------------------------
|
| Tests that need a fresh process go through evoRunCommand() / evoRunPhp(), which use
| ExecWithFallback instead of calling shell_exec() or exec() directly.
|
*/

function processHelperScript(string $code): string
{
    $script = tempnam(sys_get_temp_dir(), 'evo-proc-');
    file_put_contents($script, '<?php ' . $code);

    return $script;
}

/**
 * Calls to the global process functions in $source: comments, strings and method calls such
 * as PDO's ->exec() do not count, as the tokenizer tells them apart.
 *
 * @return string[] "name(" per call, with its line
 */
function processHelperDirectCalls(string $source): array
{
    $tokens = array_values(array_filter(
        token_get_all($source),
        static fn ($token) => !is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)
    ));
    $calls = [];
    foreach ($tokens as $i => $token) {
        if (!is_array($token) || !in_array($token[0], [T_STRING, T_NAME_FULLY_QUALIFIED], true)
            || !in_array(strtolower(ltrim($token[1], '\\')), ['shell_exec', 'exec', 'passthru', 'system', 'popen'], true)
            || ($tokens[$i + 1] ?? null) !== '(') {
            continue;
        }
        $previous = $tokens[$i - 1] ?? null;
        if (is_array($previous) && in_array($previous[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW], true)) {
            continue;
        }
        $calls[] = $token[1] . '( on line ' . $token[2];
    }

    return $calls;
}

it('tells direct process calls from methods, comments and strings', function () {
    $source = '<?php
        // shell_exec("in a comment")
        $pdo->exec("SQL");
        Foo::exec("static");
        new Filesystem();
        $s = "exec(inside a string)";
        function system_status() {}
        $a = shell_exec("ls");
        exec("ls", $out);
        \\passthru("ls");';

    expect(processHelperDirectCalls($source))->toBe([
        'shell_exec( on line 8',
        'exec( on line 9',
        '\\passthru( on line 10',
    ]);
});

it('returns the output and the exit code of a command', function () {
    $script = processHelperScript('echo "first\nsecond\n"; exit(3);');
    try {
        $output = evoRunCommand(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script), $exitCode);
    } finally {
        unlink($script);
    }

    expect($output)->toBe("first\nsecond")
        ->and($exitCode)->toBe(3);
});

it('passes each argument to the PHP script as one argument, however it looks', function () {
    $script = processHelperScript('echo json_encode(array_slice($argv, 1));');
    $args = ['1', 'two words', 'it\'s', '$HOME & more', 'a|b<c>;d'];
    if (PHP_OS_FAMILY !== 'Windows') {
        // escapeshellarg() on Windows turns " % ! into spaces, so they cannot travel there
        $args[] = 'say "hi" 100% !';
    }
    try {
        $output = evoRunPhp($script, $args, $exitCode);
    } finally {
        unlink($script);
    }

    expect(json_decode($output, true))->toBe($args)
        ->and($exitCode)->toBe(0);
});

it('includes stderr in the output unless told not to', function () {
    $script = processHelperScript('fwrite(STDERR, "to-stderr"); echo "to-stdout";');
    try {
        $withStderr = evoRunPhp($script);
        $withoutStderr = evoRunPhp($script, [], $exitCode, false);
    } finally {
        unlink($script);
    }

    expect($withStderr)->toContain('to-stdout')->toContain('to-stderr')
        ->and($withoutStderr)->toBe('to-stdout');
});

it('leaves no direct shell_exec or exec calls in the tests', function () {
    $offenders = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__), FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php' || $file->getPathname() === __FILE__) {
            continue;
        }
        foreach (processHelperDirectCalls(file_get_contents($file->getPathname())) as $call) {
            $offenders[] = str_replace('\\', '/', substr($file->getPathname(), strlen(dirname(__DIR__)) + 1)) . ': ' . $call;
        }
    }

    // proc_open stays where a test needs processes that run alongside it (locks, parallel workers)
    expect($offenders)->toBe([]);
});
