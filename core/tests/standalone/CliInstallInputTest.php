<?php

/**
 * Exercise actual readline input in bounded child processes, without booting a site.
 * Output goes to files so a regressed busy loop cannot block on a full output pipe.
 */
$root = dirname(__DIR__, 3);
$fixture = $root . '/core/tests/Fixtures/cli-install-input.php';
$temp = sys_get_temp_dir() . '/evo-cli-input-' . bin2hex(random_bytes(6));
mkdir($temp . '/database', 0700, true);

/**
 * Run a PHP child with an immediately closed stdin pipe or the platform null device.
 * A deadline turns the original endless retry into a failing regression test.
 *
 * @param list<string> $arguments PHP script path followed by its arguments.
 * @return array{int, string} Exit status and combined output.
 */
$run = static function (array $arguments, string $input = '', bool $nullInput = false) use ($temp, $root): array {
    $outputFile = $temp . '/output';
    $descriptors = [
        0 => $nullInput ? ['file', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null', 'r'] : ['pipe', 'r'],
        1 => ['file', $outputFile, 'w'],
        2 => ['file', $outputFile, 'a'],
    ];
    $process = proc_open([PHP_BINARY, ...$arguments], $descriptors, $pipes, $root . '/install');
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start PHP input probe');
    }
    if (!$nullInput) {
        if ($input !== '') {
            fwrite($pipes[0], $input);
        }
        fclose($pipes[0]);
    }
    $deadline = microtime(true) + 5;
    do {
        $status = proc_get_status($process);
        if (!$status['running']) {
            $exitCode = $status['exitcode'];
            proc_close($process);
            return [$exitCode, (string) file_get_contents($outputFile)];
        }
        usleep(10000);
    } while (microtime(true) < $deadline);

    proc_terminate($process, 9);
    proc_close($process);
    throw new RuntimeException('CLI input probe exceeded five seconds: ' . implode(' ', $arguments));
};

$check = static function (array $result, int $exitCode, string $message): void {
    if ($result[0] !== $exitCode || !str_contains($result[1], $message)) {
        throw new RuntimeException('Unexpected CLI result: ' . var_export($result, true));
    }
    if (preg_match('/\b(Fatal error|Warning|Notice|Deprecated):/', $result[1])) {
        throw new RuntimeException('PHP diagnostic in CLI output: ' . $result[1]);
    }
};

try {
    foreach ([false, true] as $nullInput) {
        // Exercise the real entrypoint and its RuntimeException -> exit 1 handling.
        $check($run([$root . '/install/cli-install.php'], '', $nullInput), 1,
            'End of input while waiting for: Please choose your variant of install:');
        $check($run([$root . '/install/cli-install.php', '--typeInstall=1', '--databaseType=sqlite'], '', $nullInput), 1,
            'End of input while waiting for: Please enter database:');
        // EOF must not silently accept either kind of default.
        $check($run([$fixture, 'ask'], '', $nullInput), 1, 'End of input while waiting for: Database name?');
        $check($run([$fixture, 'choice'], '', $nullInput), 1, 'End of input while waiting for: Install or update?');
    }

    $check($run([$fixture, 'ask'], "\n"), 0, 'ANSWER:fallback');
    $check($run([$fixture, 'ask'], "  custom  \n"), 0, 'ANSWER:custom');
    $check($run([$fixture, 'choice'], "\n"), 0, 'ANSWER:Install');
    $check($run([$fixture, 'choice'], "invalid\n2\n"), 0, 'ANSWER:Update');
    $check($run([$fixture, 'choice'], "invalid\n"), 1, 'End of input while waiting for: Install or update?');

    // Real SQLite prefix collision with all CLI arguments supplied. The failure
    // must leave the existing data and installer intact, even with removeInstall=y.
    $database = new PDO('sqlite:' . $temp . '/database/existing.sqlite');
    $database->exec('CREATE TABLE evo_site_content (id INTEGER)');
    $database->exec('INSERT INTO evo_site_content VALUES (42)');
    foreach ([false, true] as $nullInput) {
        $result = $run([$fixture, 'prefix-cli', $temp], '', $nullInput);
        $check($result, 1, 'Table prefix already exists');
        $check($result, 1, 'End of input while waiting for: Please enter table_prefix:');
        if ((int) $database->query('SELECT id FROM evo_site_content')->fetchColumn() !== 42
            || !is_file($root . '/install/cli-install.php')
            || is_file($temp . '/config/database/connections/default.php')) {
            throw new RuntimeException('Prefix conflict modified data, wrote configuration or removed the installer');
        }
    }
    $check($run([$fixture, 'prefix-recovery'], "other_\n"), 0, 'ANSWER:other_');
    $check($run([$fixture, 'prefix-recovery'], "\n"), 1, 'End of input while waiting for: Please enter table_prefix:');
    echo "PASS: CLI EOF exits 1, defaults require a line, piped answers work, prefix conflict preserves data.\n";
} finally {
    $database = null;
    foreach (glob($temp . '/database/*') ?: [] as $file) {
        unlink($file);
    }
    @unlink($temp . '/output');
    rmdir($temp . '/database');
    rmdir($temp);
}
