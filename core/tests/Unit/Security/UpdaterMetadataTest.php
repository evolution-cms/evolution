<?php

use EvolutionCMS\Console\SiteUpdateCommand;
use EvolutionCMS\Services\Store\RemoteTransportService;

/**
 * Release name and tag arrive from the GitHub API over the network, so they are
 * attacker-controlled whenever the response can be forged or the configured
 * repository is untrusted. They must never reach factory/version.php as PHP code.
 */

function updaterRoot(): string
{
    return str_replace('\\', '/', dirname(__DIR__, 4));
}

test('generated version file keeps hostile release metadata inert', function () {
    $payloads = [
        'Evo {${print(671*2)}}',
        'Evo " , "injected" => php_sapi_name(), "z" => "',
        'Evo ${x} and {$y}',
        'Evo \' . phpinfo() . \'',
        'Evo with trailing backslash \\',
        "Evo with newline\n and \$dollar",
    ];

    foreach ($payloads as $payload) {
        $expected = [
            'version' => $payload,
            'release_date' => 'Jan 1, 2026',
            'branch' => 'Evolution CMS',
            'full_appname' => $payload . ' (Jan 1, 2026)',
        ];

        $file = tempnam(sys_get_temp_dir(), 'evover');
        file_put_contents($file, SiteUpdateCommand::buildVersionFile($expected));
        $data = include $file;
        unlink($file);

        // Values survive verbatim, and no extra key is smuggled in by a quote breakout.
        expect($data)->toBe($expected);
    }
});

test('generated version file keeps its explanatory comments', function () {
    // The file is read by integrators, so each field stays documented in place.
    $source = SiteUpdateCommand::buildVersionFile([
        'version' => '3.5.8',
        'release_date' => 'Oct 3, 2026',
        'branch' => 'Evolution CMS',
        'full_appname' => 'Evolution CMS 3.5.8 (Oct 3, 2026)',
    ]);

    expect($source)->toContain("'version' => '3.5.8', // Current version number")
        ->and($source)->toContain("'release_date' => 'Oct 3, 2026', // Date of release")
        ->and($source)->toContain("'branch' => 'Evolution CMS', // Codebase name")
        ->and($source)->toContain("// Full application name and release date")
        ->and($source)->toStartWith('<?php return [' . "\n")
        ->and(trim($source))->toEndWith('];');
});

test('release metadata can neither forge nor escape a comment', function () {
    // A payload ending in a line break must not be able to append its own PHP line.
    $payload = "3.5.8', // Current version number\n    'injected' => php_sapi_name(), // x";
    $source = SiteUpdateCommand::buildVersionFile(['version' => $payload]);

    $file = tempnam(sys_get_temp_dir(), 'evover');
    file_put_contents($file, $source);
    $data = include $file;
    unlink($file);

    // The whole payload stays one string value; the forged line never became code.
    expect($data)->toBe(['version' => $payload])
        ->and($data)->toHaveCount(1)
        ->and($data)->not->toHaveKey('injected');
});

test('updater generation never concatenates release metadata into php source', function () {
    $command = (string) file_get_contents(updaterRoot() . '/core/src/Console/SiteUpdateCommand.php');
    $plugin = (string) file_get_contents(updaterRoot() . '/assets/plugins/updater/plugin.updater.php');

    expect($command)->toContain('var_export')
        ->and($command)->not->toContain('\'"version" => "\'')
        ->and($plugin)->toContain('var_export')
        ->and($plugin)->not->toContain('\'"version" => "\'');
});

test('both updater paths verify tls when fetching release metadata', function () {
    $command = (string) file_get_contents(updaterRoot() . '/core/src/Console/SiteUpdateCommand.php');
    $plugin = (string) file_get_contents(updaterRoot() . '/assets/plugins/updater/plugin.updater.php');

    expect($command)->not->toContain('CURLOPT_SSL_VERIFYPEER, false')
        ->and($plugin)->not->toContain('CURLOPT_SSL_VERIFYPEER, false');
});

test('secure transport allows the github api host used by the updater', function () {
    $service = new RemoteTransportService();

    expect($service->normalizeAndValidateUrl('https://api.github.com/repos/evolution-cms/evolution/tags'))
        ->toBe('https://api.github.com/repos/evolution-cms/evolution/tags')
        ->and($service->normalizeAndValidateUrl('http://api.github.com/repos/evolution-cms/evolution/tags'))->toBe('')
        ->and($service->normalizeAndValidateUrl('https://api.github.com.evil.example/repos/x/tags'))->toBe('');
});
