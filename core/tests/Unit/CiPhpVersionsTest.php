<?php

/**
 * CI takes its PHP versions from .github/scripts/php-versions.sh, by the CMS version of the tree:
 * 8.3 stays on the 3.5 line and goes from 3.6 on, the 8.6 RC check runs from 3.6 on only,
 * whichever branch the workflows were merged to.
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

test('the CMS version decides whether CI runs PHP 8.3 or the 8.6 RC', function (string $version, string $versions, string $lowest, string $rc) {
    $output = ciPhpVersionsFor("<?php return [\n    'version' => '$version',\n    'release_date' => 'Oct 21, 2026',\n];\n", $exitCode);

    expect($exitCode)->toBe(0)
        ->and($output)->toContain("versions=$versions\nlowest=$lowest\nrc=$rc\n");
})->with([
    '3.5 line' => ['3.5.9', '["8.3","8.4","8.5"]', '8.3', ''],
    'two-digit 3.5 patch' => ['3.5.10', '["8.3","8.4","8.5"]', '8.3', ''],
    '3.6' => ['3.6.0', '["8.4","8.5"]', '8.4', '8.6'],
    'two-digit minor' => ['3.10.1', '["8.4","8.5"]', '8.4', '8.6'],
    'next major' => ['4.0.0', '["8.4","8.5"]', '8.4', '8.6'],
]);

test('the 8.6 RC job runs only where the script asks for it', function () {
    ciPhpVersionsScript();
    $ci = (string) file_get_contents(dirname(__DIR__, 3) . '/.github/workflows/ci.yml');

    expect($ci)->toContain("    name: PHP 8.6 RC compatibility (Docker)\n    needs: php\n    if: needs.php.outputs.rc == '8.6'\n");
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
