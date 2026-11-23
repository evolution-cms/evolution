<?php

/**
 * CI takes its PHP versions from .github/scripts/php-versions.sh, by the CMS version of the tree:
 * 8.3 stays on the 3.5 line and goes from 3.6 on. Stable 8.6 belongs to the required
 * matrix on both lines, whichever branch the workflows were merged to.
 */
function ciPhpVersionsScript(): string
{
    $script = dirname(__DIR__, 3) . '/.github/scripts/php-versions.sh';
    if (!is_file($script)) {
        test()->markTestSkipped('No .github in this tree.');
    }
    evoRunCommand('sh -c true', $exitCode);
    if ($exitCode !== 0) {
        test()->markTestSkipped('No POSIX sh.');
    }

    return $script;
}

function ciPhpVersionsFor(string $versionFile, ?int &$exitCode = null): string
{
    $tree = sys_get_temp_dir() . '/evo-ci-php-' . bin2hex(random_bytes(4));
    mkdir($tree . '/core/factory', 0777, true);
    file_put_contents($tree . '/core/factory/version.php', $versionFile);
    // Inside GitHub Actions the script would write to the job's own output file, not stdout.
    $githubOutput = getenv('GITHUB_OUTPUT');
    putenv('GITHUB_OUTPUT');
    try {
        return evoRunCommand(
            'sh ' . escapeshellarg(ciPhpVersionsScript()) . ' ' . escapeshellarg($tree) . ' 2>&1',
            $exitCode
        );
    } finally {
        if ($githubOutput !== false) {
            putenv('GITHUB_OUTPUT=' . $githubOutput);
        }
        unlink($tree . '/core/factory/version.php');
        rmdir($tree . '/core/factory');
        rmdir($tree . '/core');
        rmdir($tree);
    }
}

test('the CMS version keeps its minimum PHP and requires stable 8.6', function (string $version, string $versions, string $lowest, string $runtimes) {
    $output = ciPhpVersionsFor("<?php return [\n    'version' => '$version',\n    'release_date' => 'Oct 21, 2026',\n];\n", $exitCode);

    expect($exitCode)->toBe(0)
        ->and($output)->toContain("versions=$versions\nlowest=$lowest\nruntimes=$runtimes\n");
})->with([
    '3.5 line' => ['3.5.9', '["8.3","8.4","8.5","8.6"]', '8.3', '["8.3","8.4","8.5"]'],
    'two-digit 3.5 patch' => ['3.5.10', '["8.3","8.4","8.5","8.6"]', '8.3', '["8.3","8.4","8.5"]'],
    '3.6' => ['3.6.0', '["8.4","8.5","8.6"]', '8.4', '["8.4","8.5"]'],
    'two-digit minor' => ['3.10.1', '["8.4","8.5","8.6"]', '8.4', '["8.4","8.5"]'],
    'next major' => ['4.0.0', '["8.4","8.5","8.6"]', '8.4', '["8.4","8.5"]'],
]);

test('PHP 8.6 uses the required jobs instead of an optional RC job', function () {
    ciPhpVersionsScript();
    $ci = (string) file_get_contents(dirname(__DIR__, 3) . '/.github/workflows/ci.yml');
    $build = (string) file_get_contents(dirname(__DIR__, 3) . '/.github/workflows/build.yml');

    expect($ci)->toContain('php: ${{ fromJSON(needs.php.outputs.versions) }}')
        ->not->toContain('php86-rc:')
        ->not->toContain('PHP 8.6 RC compatibility')
        ->not->toContain('--ignore-platform-req')
        ->and($build)->toContain('php: ${{ fromJSON(needs.php.outputs.runtimes) }}');
});

test('a tree without a readable version fails instead of guessing', function () {
    $output = ciPhpVersionsFor("<?php return [];\n", $exitCode);

    expect($exitCode)->not->toBe(0)
        ->and($output)->not->toContain('versions=');
});

test('no workflow lists PHP versions of its own', function () {
    ciPhpVersionsScript();
    $workflows = glob(dirname(__DIR__, 3) . '/.github/workflows/*.yml');

    expect($workflows)->not->toBeEmpty();
    foreach ($workflows as $workflow) {
        $yaml = (string) file_get_contents($workflow);
        expect($yaml)->not->toMatch('/^\s*php:\s*\[/m', basename($workflow))
            ->and($yaml)->not->toMatch("/php-version:\\s*'?8\\./", basename($workflow));
    }
});
