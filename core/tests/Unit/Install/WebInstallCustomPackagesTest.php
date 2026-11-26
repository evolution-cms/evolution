<?php

/*
|--------------------------------------------------------------------------
| The web installer restores the packages of core/custom/composer.json
|--------------------------------------------------------------------------
|
| An update through /install/ (also where the manager's update.php leads)
| runs after the files of another version were laid over the site, with
| their own vendor directory, so the merged packages were no longer loaded.
| It runs from a web request: when it cannot run Composer, it says how to.
|
*/

$root = dirname(__DIR__, 4);

/** What restoreCustomComposerPackages() answers for a core directory and Composer stand-in. */
function webRestoreRun(string $root, string $core, ?string $composer): array
{
    $probe = tempnam(sys_get_temp_dir(), 'evo_web_restore_');
    file_put_contents($probe, '<?php'
        . ' define("EVO_CORE_PATH", ' . var_export($core, true) . ');'
        . ' require ' . var_export($root . '/core/vendor/autoload.php', true) . ';'
        . ' require ' . var_export($root . '/install/src/functions.php', true) . ';'
        . ($composer === null ? ' putenv("COMPOSER_BINARY");' : ' putenv("COMPOSER_BINARY=" . ' . var_export($composer, true) . ');')
        . ' echo json_encode(restoreCustomComposerPackages());');
    $out = trim(evoRunPhp($probe));
    unlink($probe);

    return json_decode($out, true, flags: JSON_THROW_ON_ERROR);
}

function webRestoreCore(array $require): string
{
    $core = sys_get_temp_dir() . '/evo-web-restore-' . getmypid() . '-' . uniqid() . '/';
    mkdir($core . 'custom', 0777, true);
    file_put_contents($core . 'custom/composer.json', json_encode(['require' => $require]));

    return $core;
}

test('a site without custom packages has nothing to restore', function () use ($root) {
    $core = webRestoreCore(['php' => '^8.3']);

    try {
        expect(webRestoreRun($root, $core, null))
            ->toBe(['packages' => [], 'restored' => true, 'command' => '', 'output' => '', 'consent' => false]);
    } finally {
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($core);
    }
});

test('a Composer that fails leaves the command to run and its output', function () use ($root) {
    $core = webRestoreCore(['league/csv' => '^9.0']);
    $composer = $core . (PHP_OS_FAMILY === 'Windows' ? 'composer.bat' : 'composer.sh');
    file_put_contents($composer, PHP_OS_FAMILY === 'Windows'
        ? "@echo Could not reach packagist.org\r\n@exit /b 2\r\n"
        : "#!/bin/sh\necho 'Could not reach packagist.org'\nexit 2\n");
    chmod($composer, 0755);

    try {
        $result = webRestoreRun($root, $core, $composer);

        expect($result['packages'])->toBe(['league/csv'])
            ->and($result['restored'])->toBeFalse()
            ->and($result['command'])->toBe('cd core && composer update league/csv --with-dependencies --no-dev')
            ->and($result['output'])->toContain('Could not reach packagist.org');
    } finally {
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($core);
    }
});

test('the command left to run names a path package without the reference it is compared by', function () use ($root) {
    $core = webRestoreCore(['acme/local' => '*']);
    file_put_contents($core . 'custom/composer.installed.json', json_encode(['packages' => ['acme/local' => ['version' => 'dev-main 18f1a03c9d2e', 'dependencies' => []]]]));
    $composer = $core . (PHP_OS_FAMILY === 'Windows' ? 'composer.bat' : 'composer.sh');
    file_put_contents($composer, PHP_OS_FAMILY === 'Windows' ? "@exit /b 2\r\n" : "#!/bin/sh\nexit 2\n");
    chmod($composer, 0755);

    try {
        expect(webRestoreRun($root, $core, $composer)['command'])->toBe('cd core && composer update acme/local:dev-main --with-dependencies --no-dev');
    } finally {
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($core);
    }
});

test('the web update restores the packages last and shows the outcome', function () use ($root) {
    $controller = (string) file_get_contents($root . '/install/src/controllers/install.php');
    $view = (string) file_get_contents($root . '/install/src/template/actions/install.php');
    $lang = (string) file_get_contents($root . '/install/src/lang/en.inc.php');
    $update = substr($controller, strpos($controller, "seed('update');"));

    expect($update)->toContain('$customPackages = restoreCustomComposerPackages();')
        // After the core migrations, like cli-install.php: a slow or failed Composer leaves the database updated.
        ->and(strpos($update, "Console::call('migrate', ['--force' => true]);"))->toBeLessThan(strpos($update, 'restoreCustomComposerPackages()'))
        ->and($view)->toContain("\$_lang['custom_packages_not_restored']")
        ->and($view)->toContain("htmlspecialchars(\$customPackages['command'], ENT_QUOTES, 'UTF-8')")
        ->and($view)->toContain("htmlspecialchars(\$customPackages['output'], ENT_QUOTES, 'UTF-8')")
        ->and($lang)->toContain("'custom_packages_restored' =>")
        ->and($lang)->toContain("'custom_packages_run_command' =>");
});

test('a web update whose packages were not restored ends incomplete and keeps the installer', function () use ($root) {
    $controller = (string) file_get_contents($root . '/install/src/controllers/install.php');
    $view = (string) file_get_contents($root . '/install/src/template/actions/install.php');
    $lang = (string) file_get_contents($root . '/install/src/lang/en.inc.php');
    $done = substr($view, strpos($view, '<?php if ($installLevel >= 7): ?>'));

    expect($controller)->toContain("if (!\$customPackages['restored']) {\n                \$errors += 1;")
        // An error takes away the "remove the install folder" option.
        ->and($done)->toContain('<?php if ($errors === 0) : ?>')
        ->and($done)->toContain("\$_lang['custom_packages_update_incomplete']")
        ->and(strpos($done, "custom_packages_update_incomplete"))->toBeLessThan(strpos($done, "\$_lang['installation_successful']"))
        ->and($lang)->toContain("'custom_packages_update_incomplete' =>");
});

test('a COMPOSER_BINARY that leads to the copy in core/vendor is not taken for a standalone Composer', function () use ($root) {
    $core = webRestoreCore(['league/csv' => '^9.0']);
    mkdir($core . 'vendor/bin', 0777, true);
    file_put_contents($core . 'vendor/bin/composer', "<?php\n");
    $standalone = $core . 'standalone-composer';
    file_put_contents($standalone, "#!/bin/sh\n");
    $probe = tempnam(sys_get_temp_dir(), 'evo_web_composer_');
    file_put_contents($probe, '<?php'
        . ' define("EVO_CORE_PATH", ' . var_export($core, true) . ');'
        . ' require ' . var_export($root . '/core/vendor/autoload.php', true) . ';'
        . ' require ' . var_export($root . '/install/src/functions.php', true) . ';'
        . ' $found = [];'
        . ' foreach (' . var_export([$core . 'vendor/bin/composer', $standalone], true) . ' as $binary) {'
        . '   putenv("COMPOSER_BINARY=" . $binary); $found[] = installerComposerCommand()["standalone"];'
        . ' }'
        . ' echo json_encode($found);');

    try {
        expect(json_decode(trim(evoRunPhp($probe)), true, flags: JSON_THROW_ON_ERROR))->toBe([false, true]);
    } finally {
        unlink($probe);
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($core);
    }
});

test('the web installer never runs Composer through the FPM binary', function () use ($root) {
    $functions = (string) file_get_contents($root . '/install/src/functions.php');
    $finder = substr($functions, strpos($functions, 'function installerComposerCommand()'), 1500);

    expect($finder)->toContain("PHP_SAPI === 'cli' ? PHP_BINARY : ''")
        ->and($finder)->toContain('PHP_BINDIR');
});

test('a Composer that refuses a vulnerable PHP tells the operator to update PHP, in the installer language', function () use ($root) {
    $core = webRestoreCore(['league/csv' => '^9.0']);
    file_put_contents($core . 'fake-composer.php', '<?php echo implode("\n", ['
        . '"Your requirements could not be resolved to an installable set of packages.", "", "  Problem 1",'
        . '"    - php is present at version 8.4.27 and cannot be modified by Composer",'
        . '"    - roave/security-advisories is locked to version dev-master and an update of this package was not requested.",'
        . '"    - roave/security-advisories dev-master conflicts with php <8.2.36|>=8.3,<8.3.37|>=8.4,<8.4.28|>=8.5,<8.5.13.",'
        . ']), "\n"; exit(2);');
    $composer = $core . (PHP_OS_FAMILY === 'Windows' ? 'composer.bat' : 'composer.sh');
    file_put_contents($composer, PHP_OS_FAMILY === 'Windows'
        ? '@"' . PHP_BINARY . '" "%~dp0fake-composer.php" %*' . "\r\n@exit /b %ERRORLEVEL%\r\n"
        : "#!/bin/sh\nexec " . escapeshellarg(PHP_BINARY) . ' "$(dirname "$0")/fake-composer.php" "$@"' . "\n");
    chmod($composer, 0755);

    $probe = tempnam(sys_get_temp_dir(), 'evo_web_restore_');
    file_put_contents($probe, '<?php'
        . ' define("EVO_CORE_PATH", ' . var_export($core, true) . ');'
        . ' require ' . var_export($root . '/core/vendor/autoload.php', true) . ';'
        . ' require ' . var_export($root . '/install/src/functions.php', true) . ';'
        . ' $_lang = ["php_vulnerable_composer" => "Оновіть PHP :version до :fixed."];'
        . ' putenv("COMPOSER_BINARY=" . ' . var_export($composer, true) . ');'
        . ' echo json_encode(restoreCustomComposerPackages());');

    try {
        $result = json_decode(trim(evoRunPhp($probe)), true, flags: JSON_THROW_ON_ERROR);

        expect($result['restored'])->toBeFalse()
            ->and($result['output'])->toStartWith("Оновіть PHP 8.4.27 до 8.4.28.\n")
            ->and($result['output'])->toContain('roave/security-advisories dev-master conflicts with php')
            ->and($result['output'])->not->toContain('together with core/custom/composer.json');
    } finally {
        unlink($probe);
        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($core);
    }
});

test('the installer lexicon carries the PHP vulnerability notice', function () use ($root) {
    $lang = (string) file_get_contents($root . '/install/src/lang/en.inc.php');

    expect($lang)->toContain("'php_vulnerable_composer' =>")
        ->and($lang)->toContain("'php_vulnerable_composer_no_fix' =>");
});
