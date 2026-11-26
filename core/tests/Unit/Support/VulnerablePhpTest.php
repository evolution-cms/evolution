<?php

use EvolutionCMS\Support\VulnerablePhp;

/** What Composer 2.11 prints on PHP 8.4.27 for any run that resolves the lock. */
function vulnerablePhpComposerOutput(string $present = '8.4.27'): string
{
    return implode("\n", [
        'Loading composer repositories with package information',
        'Updating dependencies',
        'Your requirements could not be resolved to an installable set of packages.',
        '',
        '  Problem 1',
        '    - php is present at version ' . $present . ' and cannot be modified by Composer',
        '    - roave/security-advisories is locked to version dev-master and an update of this package was not requested.',
        '    - roave/security-advisories dev-master conflicts with php <8.2.36|>=8.3,<8.3.37|>=8.4,<8.4.28|>=8.5,<8.5.13.',
        '',
    ]);
}

test('the roave refusal of the running PHP is read from Composer output, with the release that fixes it', function () {
    expect(VulnerablePhp::fromComposerOutput(vulnerablePhpComposerOutput()))
        ->toBe(['version' => '8.4.27', 'fixed' => '8.4.28'])
        ->and(VulnerablePhp::fromComposerOutput(vulnerablePhpComposerOutput('8.3.20')))
        ->toBe(['version' => '8.3.20', 'fixed' => '8.3.37']);
});

test('a PHP line below every bounded range is sent to the first fixed release', function () {
    expect(VulnerablePhp::fromComposerOutput(vulnerablePhpComposerOutput('8.1.31')))
        ->toBe(['version' => '8.1.31', 'fixed' => '8.2.36']);
});

test('the refusal is found inside the output of make:site and of the task worker', function () {
    $output = "Check Composer packages against the new version\n"
        . "The Composer check stopped the update. Nothing was changed.\n"
        . "Composer could not resolve this version of Evolution CMS together with core/custom/composer.json:\n"
        . vulnerablePhpComposerOutput();

    expect(VulnerablePhp::fromComposerOutput($output))->toBe(['version' => '8.4.27', 'fixed' => '8.4.28']);
});

test('a conflict that names the PHP version itself is read too', function () {
    $output = "  Problem 1\n    - roave/security-advisories dev-latest conflicts with php[8.4.27].\n";

    expect(VulnerablePhp::fromComposerOutput($output))->toBe(['version' => '8.4.27', 'fixed' => '']);
});

test('other Composer failures are not taken for it', function (string $output) {
    expect(VulnerablePhp::fromComposerOutput($output))->toBeNull();
})->with([
    'missing extension' => ["  Problem 1\n    - elcreator/aimage 1.0.0 requires ext-imagick * -> it is missing from your system.\n"],
    'roave against a package' => ["  Problem 1\n    - roave/security-advisories dev-master conflicts with symfony/http-kernel[v6.0.0].\n"],
    'php requirement of a package' => ["  Problem 1\n    - php is present at version 8.2.10 and cannot be modified by Composer\n    - seiger/sapi v1.1.2 requires php ^8.3 -> your php version (8.2.10) does not satisfy that requirement.\n"],
    'network' => ["Could not reach packagist.org\n"],
]);

test('the notice names the PHP and the release to update to', function () {
    expect(VulnerablePhp::message(['version' => '8.4.27', 'fixed' => '8.4.28'], []))
        ->toContain('effective PHP version (8.4.27)')
        ->toContain('8.4.28 or newer')
        ->and(VulnerablePhp::message(['version' => '8.4.27', 'fixed' => ''], []))
        ->toContain('effective PHP version (8.4.27)')
        ->toContain('supported PHP release without this conflict')
        ->not->toContain(':fixed');
});

test('the notice takes its text from the lexicon it is given', function () {
    $lang = ['php_vulnerable_composer' => 'Оновіть PHP :version до :fixed або новішої.'];

    expect(VulnerablePhp::message(['version' => '8.4.27', 'fixed' => '8.4.28'], $lang))
        ->toBe('Оновіть PHP 8.4.27 до 8.4.28 або новішої.')
        // a key the lexicon lacks falls back to English
        ->and(VulnerablePhp::message(['version' => '8.4.27', 'fixed' => ''], $lang))
        ->toStartWith('Composer refused to change packages:');
});

test('the notice distinguishes the PHP version Composer reports from the local runtime', function () {
    $reported = PHP_VERSION_ID >= 80500 ? '8.4.27' : '8.5.12';
    $details = VulnerablePhp::fromComposerOutput(vulnerablePhpComposerOutput($reported));

    expect($details['version'] ?? null)->toBe($reported)
        ->and(VulnerablePhp::message($details ?? [], []))
        ->toContain('effective PHP version (' . $reported . ')')
        ->toContain('PHP executable Composer uses')
        ->toContain('config.platform.php')
        ->not->toContain('on this server');
});

test('the English notice is the one in the core lexicon', function () {
    $_lang = [];
    $lang = require dirname(__DIR__, 3) . '/lang/en/global.php';

    expect(VulnerablePhp::message(['version' => '8.4.27', 'fixed' => '8.4.28'], []))
        ->toBe(strtr($lang['php_vulnerable_composer'], [':version' => '8.4.27', ':fixed' => '8.4.28']))
        ->and(VulnerablePhp::message(['version' => '8.4.27'], []))
        ->toBe(strtr($lang['php_vulnerable_composer_no_fix'], [':version' => '8.4.27']));
});

test('make:site and the installers explain a vulnerable PHP instead of blaming the custom packages', function () {
    $plan = ['ok' => false, 'reason' => 'unresolvable', 'changes' => [], 'runtime' => [], 'pinsFit' => false, 'output' => trim(vulnerablePhpComposerOutput())];
    $other = ['reason' => 'unresolvable', 'output' => 'Could not reach packagist.org'] + $plan;

    expect(\EvolutionCMS\Services\CustomComposerPackages::describeRefusal($plan, []))
        ->toStartWith(VulnerablePhp::message(['version' => '8.4.27', 'fixed' => '8.4.28'], []) . "\n")
        ->toContain('roave/security-advisories dev-master conflicts with php')
        ->not->toContain('together with core/custom/composer.json')
        ->and(\EvolutionCMS\Services\CustomComposerPackages::describeRefusal($other))
        ->toStartWith('Composer could not resolve this version of Evolution CMS together with core/custom/composer.json');
});
