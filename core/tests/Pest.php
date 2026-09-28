<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Runs $command and returns its output, lines joined with "\n"; $exitCode receives its exit
 * code. ExecWithFallback tries exec(), passthru(), proc_open() and popen() in turn, so a test
 * still runs where some of them are disabled; it throws when none is available.
 */
function evoRunCommand(string $command, ?int &$exitCode = null): string
{
    $output = [];
    \ExecWithFallback\ExecWithFallback::exec($command, $output, $exitCode);

    return implode("\n", $output);
}

/**
 * Runs the PHP script $script in a fresh PHP process, for what must not leak between tests
 * (constants, globals, loaded classes). stderr joins the output unless $captureStderr is false.
 *
 * @param string[] $args passed to the script, each shell-escaped (on Windows escapeshellarg()
 *                       turns " % ! into spaces)
 */
function evoRunPhp(string $script, array $args = [], ?int &$exitCode = null, bool $captureStderr = true): string
{
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script);
    foreach ($args as $arg) {
        $command .= ' ' . escapeshellarg((string) $arg);
    }

    return evoRunCommand($command . ($captureStderr ? ' 2>&1' : ''), $exitCode);
}
