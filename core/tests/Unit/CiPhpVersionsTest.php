<?php

/**
 * CI takes its PHP versions from .github/scripts/php-versions.sh, by the CMS version of the tree:
 * 8.3 stays on the 3.5 line and goes from 3.6 on, 8.6 comes with 3.6, whichever branch the
 * workflows were merged to. The Docker images are built only on versions salo2 has runtimes for.
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

test('the CMS version decides whether CI runs PHP 8.3 or 8.6', function (string $version, string $versions, string $lowest, string $runtimes) {
    $output = ciPhpVersionsFor("<?php return [\n    'version' => '$version',\n    'release_date' => 'Oct 21, 2026',\n];\n", $exitCode);

    expect($exitCode)->toBe(0)
        ->and($output)->toContain("versions=$versions\nlowest=$lowest\nruntimes=$runtimes\n");
})->with([
    '3.5 line' => ['3.5.9', '["8.3","8.4","8.5"]', '8.3', '["8.3","8.4","8.5"]'],
    'two-digit 3.5 patch' => ['3.5.10', '["8.3","8.4","8.5"]', '8.3', '["8.3","8.4","8.5"]'],
    '3.6' => ['3.6.0', '["8.4","8.5","8.6"]', '8.4', '["8.4","8.5"]'],
    'two-digit minor' => ['3.10.1', '["8.4","8.5","8.6"]', '8.4', '["8.4","8.5"]'],
    'next major' => ['4.0.0', '["8.4","8.5","8.6"]', '8.4', '["8.4","8.5"]'],
]);

test('CI tests every version and builds images only on Salo runtimes', function () {
    ciPhpVersionsScript();
    $root = dirname(__DIR__, 3);
    $ci = (string) file_get_contents($root . '/.github/workflows/ci.yml');
    $build = (string) file_get_contents($root . '/.github/workflows/build.yml');

    expect(substr_count($ci, 'php: ${{ fromJSON(needs.php.outputs.versions) }}'))->toBe(2)
        ->and($ci)->not->toContain('outputs.rc')
        ->and($build)->toContain('runtimes: ${{ steps.php.outputs.runtimes }}')
        ->and($build)->toContain('php: ${{ fromJSON(needs.php.outputs.runtimes) }}')
        ->and($build)->not->toContain('fromJSON(needs.php.outputs.versions)');
});

test('the checked out release metadata and CI agree on the supported PHP line', function () {
    $root = dirname(__DIR__, 3);
    $release = require $root . '/core/factory/version.php';
    $core = json_decode(file_get_contents($root . '/core/composer.json'), true, 512, JSON_THROW_ON_ERROR);
    $project = json_decode(file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
    $output = ciPhpVersionsFor(file_get_contents($root . '/core/factory/version.php'), $exitCode);

    expect($release['version'])->toBe($core['version'])
        ->and($project['require']['php'])->toBe($core['require']['php'])
        ->and($exitCode)->toBe(0);

    preg_match('/^lowest=(\d+\.\d+)$/m', $output, $matches);
    expect($matches)->toHaveCount(2);
    $minimum = ltrim($core['require']['php'], '^');
    expect(version_compare($matches[1] . '.999', $minimum, '>='))->toBeTrue();
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
