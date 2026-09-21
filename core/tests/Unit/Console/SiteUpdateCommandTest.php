<?php use EvolutionCMS\Console\SiteUpdateCommand;

beforeEach(function () {
    $this->command = new SiteUpdateCommand();
});

function invokeSiteUpdateMethod(SiteUpdateCommand $command, string $method, array $args = [])
{
    $reflection = new ReflectionClass($command);
    $instanceMethod = $reflection->getMethod($method);
    $instanceMethod->setAccessible(true);

    return $instanceMethod->invokeArgs($command, $args);
}

test('buildArchiveUrl uses tag archive path for semantic versions', function () {
    $url = invokeSiteUpdateMethod($this->command, 'buildArchiveUrl', ['evolution-cms/evolution', '3.5.5']);

    expect($url)->toBe('https://codeload.github.com/evolution-cms/evolution/zip/refs/tags/3.5.5');
});

test('buildArchiveUrl uses branch archive path for branch refs', function () {
    $url = invokeSiteUpdateMethod($this->command, 'buildArchiveUrl', ['evolution-cms/evolution', '3.5.x']);

    expect($url)->toBe('https://codeload.github.com/evolution-cms/evolution/zip/refs/heads/3.5.x');
});

test('buildArchiveUrl preserves nested branch paths', function () {
    $url = invokeSiteUpdateMethod($this->command, 'buildArchiveUrl', ['vendor/repo', 'feature/test-branch']);

    expect($url)->toBe('https://codeload.github.com/vendor/repo/zip/refs/heads/feature/test-branch');
});

test('buildArchiveUrl uses commit archive path for commit hashes', function () {
    $url = invokeSiteUpdateMethod($this->command, 'buildArchiveUrl', ['evolution-cms/evolution', '922ece66071acecaea9afb8486791738acc6de5e']);

    expect($url)->toBe('https://codeload.github.com/evolution-cms/evolution/zip/922ece66071acecaea9afb8486791738acc6de5e');
});

test('normalizeRequestedVersion keeps explicit refs and normalizes empty input', function () {
    expect(invokeSiteUpdateMethod($this->command, 'normalizeRequestedVersion', ['3.5.x']))->toBe('3.5.x');
    expect(invokeSiteUpdateMethod($this->command, 'normalizeRequestedVersion', ['']))->toBe('null');
    expect(invokeSiteUpdateMethod($this->command, 'normalizeRequestedVersion', [null]))->toBe('null');
});

test('normalizeUpdateRepository trims custom repository slugs', function () {
    expect(invokeSiteUpdateMethod($this->command, 'normalizeUpdateRepository', [' /middleDuckAi/evolution/ ']))
        ->toBe('middleDuckAi/evolution');
});

test('site updater runs core migrations during updates', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/SiteUpdateCommand.php');

    expect($source)->toContain('$this->runCoreMigrations();');
    expect($source)->toContain("runCoreShellCommand('php artisan migrate --force')");
    expect($source)->toContain('$this->updateBundledExtrasModule();');
    expect($source)->not->toContain('cli-install.php --typeInstall=2');
    expect(strpos($source, '$this->runCoreMigrations();'))->toBeLessThan(strpos($source, '$this->updateBundledExtrasModule();'));
    expect(strpos($source, '$this->updateBundledExtrasModule();'))->toBeLessThan(strpos($source, "self::rmdirs(EVO_BASE_PATH . 'install')"));
});

test('site updater removes update placeholder files before composer repair', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/SiteUpdateCommand.php');
    $pairs = invokeSiteUpdateMethod($this->command, 'updatePlaceholderFilePairs');
    $normalizedPairs = array_map(
        fn (array $pair) => array_map(fn (string $path) => str_replace('\\', '/', $path), $pair),
        $pairs
    );

    expect($source)->toContain('$this->cleanupUpdatePlaceholderFiles();');
    expect($source)->toContain("[EVO_BASE_PATH . 'composer.json', EVO_BASE_PATH . 'config.php.example']");
    expect($source)->toContain('$this->synchronizeComposerVersion();');
    expect(strpos($source, '$this->cleanupUpdatePlaceholderFiles();'))->toBeLessThan(strpos($source, '$this->installComposerDependencies('));
    expect(strpos($source, '$this->cleanupUpdatePlaceholderFiles();'))->toBeLessThan(strpos($source, '$this->synchronizeComposerVersion();'));
    expect(strpos($source, '$this->synchronizeComposerVersion();'))->toBeLessThan(strpos($source, '$this->installComposerDependencies('));
    expect($normalizedPairs)->toHaveCount(6);
    expect($normalizedPairs[0][0])->toEndWith('/ht.access');
    expect($normalizedPairs[0][1])->toEndWith('/.htaccess');
    expect($normalizedPairs[1][0])->toEndWith('/sample-robots.txt');
    expect($normalizedPairs[1][1])->toEndWith('/robots.txt');
    expect($normalizedPairs[2][0])->toEndWith('/core/custom/.env.docker.example');
    expect($normalizedPairs[2][1])->toEndWith('/core/custom/.env.docker');
    expect($normalizedPairs[3][0])->toEndWith('/core/custom/composer.json.example');
    expect($normalizedPairs[3][1])->toEndWith('/core/custom/composer.json');
    expect($normalizedPairs[4][0])->toEndWith('/core/custom/routes.php.example');
    expect($normalizedPairs[4][1])->toEndWith('/core/custom/routes.php');
    expect($normalizedPairs[5][0])->toEndWith('/core/custom/config/cms/settings/ControllerNamespace.php.example');
    expect($normalizedPairs[5][1])->toEndWith('/core/custom/config/cms/settings/ControllerNamespace.php');
});

test('site updater repairs composer vendor state before artisan commands', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/SiteUpdateCommand.php');
    $composerBinary = getenv('COMPOSER_BINARY');
    $composerBin = getenv('COMPOSER_BIN');

    putenv('COMPOSER_BINARY');
    putenv('COMPOSER_BIN');

    expect($source)
        ->toContain('$this->composerInstallCommand()')
        ->toContain('buildCustomComposerUpdateCommand')
        ->toContain('$this->composerDumpAutoloadCommand()')
        ->toContain("runCoreShellCommand('php artisan package:discover')")
        ->toContain('--no-scripts')
        ->toContain('return self::FAILURE')
        ->toContain('catch (\Throwable $exception)')
        ->not->toContain('new Application()')
        ->not->toContain("runCoreShellCommand('composer update')");

    expect($source)->toContain('$this->installComposerDependencies($customPackageConstraints, $lockedCustomPackageVersions);');
    expect(strpos($source, '$this->installComposerDependencies($customPackageConstraints, $lockedCustomPackageVersions);'))->toBeLessThan(strpos($source, '$this->runCoreMigrations();'));
    expect(strpos($source, 'buildCustomComposerUpdateCommand'))->toBeLessThan(strpos($source, '$this->composerInstallCommand()'));
    expect(strpos($source, '$this->composerInstallCommand()'))->toBeLessThan(strpos($source, '$this->composerDumpAutoloadCommand()'));
    expect(strpos($source, '$this->composerDumpAutoloadCommand()'))->toBeLessThan(strpos($source, "runCoreShellCommand('php artisan package:discover')"));
    expect(invokeSiteUpdateMethod($this->command, 'composerInstallCommand'))
        ->toBe('composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --classmap-authoritative --no-scripts');
    expect(invokeSiteUpdateMethod($this->command, 'composerDumpAutoloadCommand'))
        ->toBe('composer dump-autoload -o --no-dev --classmap-authoritative --no-scripts');

    $composerBinary === false ? putenv('COMPOSER_BINARY') : putenv('COMPOSER_BINARY=' . $composerBinary);
    $composerBin === false ? putenv('COMPOSER_BIN') : putenv('COMPOSER_BIN=' . $composerBin);
});

test('ordinary composer lifecycle does not synchronize root version metadata', function () {
    $composer = json_decode(
        (string) file_get_contents(dirname(__DIR__, 3) . '/composer.json'),
        true
    );
    $scripts = $composer['scripts'] ?? [];

    expect($scripts)->toHaveKey('sync-replace');
    expect($scripts)->not->toHaveKey('pre-install-cmd');
    expect($scripts)->not->toHaveKey('pre-update-cmd');
});

test('site updater accepts only real composer package names for scoped updates', function () {
    expect(invokeSiteUpdateMethod($this->command, 'isComposerPackageName', ['seiger/scommerce']))->toBeTrue();
    expect(invokeSiteUpdateMethod($this->command, 'isComposerPackageName', ['evolution-cms/eai']))->toBeTrue();
    expect(invokeSiteUpdateMethod($this->command, 'isComposerPackageName', ['php']))->toBeFalse();
    expect(invokeSiteUpdateMethod($this->command, 'isComposerPackageName', ['ext-json']))->toBeFalse();
    expect(invokeSiteUpdateMethod($this->command, 'isComposerPackageName', ['bad package']))->toBeFalse();
});

test('site updater builds scoped composer update for custom packages', function () {
    $composerBinary = getenv('COMPOSER_BINARY');
    $composerBin = getenv('COMPOSER_BIN');

    putenv('COMPOSER_BINARY');
    putenv('COMPOSER_BIN');

    $command = invokeSiteUpdateMethod($this->command, 'buildCustomComposerUpdateCommand', [
        [
            'evolution-cms/eai' => '^1.0',
            'php' => '^8.3',
            'ext-json' => '*',
            'Seiger/sTask' => '*',
            'bad package' => '*',
            'seiger/scommerce' => '*',
        ],
        [
            'evolution-cms/eai' => '1.2.3',
            'seiger/stask' => '1.0.10',
        ],
    ]);

    // The builder quotes each package with escapeshellarg(), whose quoting style
    // is platform specific, so the expectation is quoted the same way.
    $quoted = implode(' ', array_map('escapeshellarg', [
        'evolution-cms/eai:1.2.3',
        'seiger/stask:1.0.10',
        'seiger/scommerce',
    ]));

    expect($command)
        ->toBe("composer update {$quoted} --with-all-dependencies --no-dev --no-interaction --prefer-dist --optimize-autoloader --classmap-authoritative --no-scripts");

    $composerBinary === false ? putenv('COMPOSER_BINARY') : putenv('COMPOSER_BINARY=' . $composerBinary);
    $composerBin === false ? putenv('COMPOSER_BIN') : putenv('COMPOSER_BIN=' . $composerBin);
});

test('site updater accepts configured composer binary', function () {
    $composerBinary = getenv('COMPOSER_BINARY');

    putenv('COMPOSER_BINARY=/home/evo/.composer/composer');

    $binary = escapeshellarg('/home/evo/.composer/composer');

    expect(invokeSiteUpdateMethod($this->command, 'composerInstallCommand'))
        ->toBe("{$binary} install --no-dev --no-interaction --prefer-dist --optimize-autoloader --classmap-authoritative --no-scripts");
    expect(invokeSiteUpdateMethod($this->command, 'composerDumpAutoloadCommand'))
        ->toBe("{$binary} dump-autoload -o --no-dev --classmap-authoritative --no-scripts");

    $composerBinary === false ? putenv('COMPOSER_BINARY') : putenv('COMPOSER_BINARY=' . $composerBinary);
});

test('site updater checks user local composer path as a fallback', function () {
    $home = getenv('HOME');

    putenv('HOME=/home/evo');

    expect(invokeSiteUpdateMethod($this->command, 'composerBinaryCandidates'))
        ->toContain('/home/evo/.composer/composer');

    $home === false ? putenv('HOME') : putenv('HOME=' . $home);
});

test('site updater can read bundled extras installer metadata', function () {
    $moduleFile = dirname(__DIR__, 4) . '/install/assets/modules/store.tpl';

    $params = invokeSiteUpdateMethod($this->command, 'parseInstallerDocblock', [$moduleFile]);
    $moduleCode = invokeSiteUpdateMethod($this->command, 'readInstallerModuleCode', [$moduleFile]);

    expect($params['name'])->toBe('Extras');
    expect($params['version'])->toBe('0.2.0');
    expect($params['guid'])->toBe('store435243542tf542t5t');
    expect($moduleCode)->toContain("assets/modules/store/core.php");
});

test('composer detection uses a probe the local shell actually understands', function () {
    $source = file_get_contents(dirname(__DIR__, 3) . '/src/Console/SiteUpdateCommand.php');

    expect($source)
        ->toContain("'where ' . escapeshellarg(\$command) . ' >NUL 2>NUL'")
        ->toContain("'command -v ' . escapeshellarg(\$command) . ' >/dev/null 2>&1'");
});

test('shellCommandExists finds a command that is really on PATH', function () {
    $present = windows_os() ? 'where' : 'sh';

    expect(invokeSiteUpdateMethod($this->command, 'shellCommandExists', [$present]))->toBeTrue()
        ->and(invokeSiteUpdateMethod($this->command, 'shellCommandExists', ['evo-no-such-command-here']))->toBeFalse();
});

test('composer binary candidates cover windows layouts as well as posix ones', function () {
    $programData = getenv('ProgramData');
    $appData = getenv('APPDATA');

    putenv('ProgramData=C:\ProgramData');
    putenv('APPDATA=C:\Users\evo\AppData\Roaming');

    $candidates = invokeSiteUpdateMethod($this->command, 'composerBinaryCandidates');

    expect($candidates)
        ->toContain('/usr/local/bin/composer')
        ->toContain('C:/ProgramData/ComposerSetup/bin/composer.bat')
        ->toContain('C:/Users/evo/AppData/Roaming/Composer/composer.bat');

    $programData === false ? putenv('ProgramData') : putenv('ProgramData=' . $programData);
    $appData === false ? putenv('APPDATA') : putenv('APPDATA=' . $appData);
});

test('isExecutableFile accepts a windows shim that is_executable rejects', function () {
    $path = sys_get_temp_dir() . '/evo-composer-probe-' . getmypid() . '.bat';
    file_put_contents($path, '@echo off');

    try {
        $expected = windows_os() ? true : is_executable($path);

        expect(invokeSiteUpdateMethod($this->command, 'isExecutableFile', [$path]))->toBe($expected)
            ->and(invokeSiteUpdateMethod($this->command, 'isExecutableFile', [$path . '.missing']))->toBeFalse();
    } finally {
        @unlink($path);
    }
});

/**
 * Update package guards.
 *
 * A failed or truncated download must stop the command before any site file is
 * replaced, and before composer, migrations and seeders run against code that
 * was never updated.
 */

function updateGuardWorkspace(): string
{
    $path = sys_get_temp_dir() . '/evo-update-guard-' . getmypid() . '-' . uniqid();
    mkdir($path, 0777, true);

    return $path;
}

test('downloadUpdateArchive rejects a download that returned nothing', function () {
    $workspace = updateGuardWorkspace();
    $target = $workspace . '/new_version.zip';

    try {
        // A failed transfer warns before returning false; that diagnostic is the
        // expected behaviour here, so it is swallowed rather than reported as noise.
        set_error_handler(static fn () => true);
        try {
            $missing = invokeSiteUpdateMethod($this->command, 'downloadUpdateArchive', [$workspace . '/absent.zip', $target]);
        } finally {
            restore_error_handler();
        }

        // An empty body stands in for a transfer that connected but delivered nothing.
        $empty = invokeSiteUpdateMethod($this->command, 'downloadUpdateArchive', ['data://text/plain,', $target]);

        expect($missing)->toBeFalse()
            ->and($empty)->toBeFalse()
            ->and(file_exists($target))->toBeFalse();
    } finally {
        SiteUpdateCommand::rmdirs($workspace);
    }
});

test('downloadUpdateArchive stores a payload it actually received', function () {
    $workspace = updateGuardWorkspace();
    $source = $workspace . '/source.zip';
    $target = $workspace . '/new_version.zip';
    file_put_contents($source, 'payload-bytes');

    try {
        expect(invokeSiteUpdateMethod($this->command, 'downloadUpdateArchive', [$source, $target]))->toBeTrue()
            ->and(file_get_contents($target))->toBe('payload-bytes');
    } finally {
        SiteUpdateCommand::rmdirs($workspace);
    }
});

test('extractUpdateArchive refuses an empty file that opens as a valid archive', function () {
    $workspace = updateGuardWorkspace();
    $file = $workspace . '/new_version.zip';
    file_put_contents($file, '');
    $tempDir = $workspace . '/_temp';

    try {
        // ZipArchive::open() reports success for a zero byte file, so the entry
        // count is what has to rule it out.
        expect(invokeSiteUpdateMethod($this->command, 'extractUpdateArchive', [$file, $tempDir]))->toBeFalse();
    } finally {
        SiteUpdateCommand::rmdirs($workspace);
    }
});

test('extractUpdateArchive refuses a truncated or wrong payload without throwing', function () {
    $workspace = updateGuardWorkspace();
    $tempDir = $workspace . '/_temp';

    try {
        foreach (['<html>404 Not Found</html>', "PK\x03\x04truncated-archive"] as $index => $content) {
            $file = $workspace . '/payload' . $index . '.zip';
            file_put_contents($file, $content);

            expect(invokeSiteUpdateMethod($this->command, 'extractUpdateArchive', [$file, $tempDir]))->toBeFalse();
        }
    } finally {
        SiteUpdateCommand::rmdirs($workspace);
    }
});

test('extractUpdateArchive unpacks a real archive', function () {
    $workspace = updateGuardWorkspace();
    $file = $workspace . '/new_version.zip';
    $tempDir = $workspace . '/_temp';

    $zip = new ZipArchive();
    $zip->open($file, ZipArchive::CREATE);
    $zip->addFromString('evolution-3.5.9/index.php', '<?php // new core');
    $zip->close();

    try {
        expect(invokeSiteUpdateMethod($this->command, 'extractUpdateArchive', [$file, $tempDir]))->toBeTrue()
            ->and(file_get_contents($tempDir . '/evolution-3.5.9/index.php'))->toBe('<?php // new core');
    } finally {
        SiteUpdateCommand::rmdirs($workspace);
    }
});

test('resolveExtractedRoot names the single unpacked directory and rejects anything else', function () {
    $workspace = updateGuardWorkspace();

    try {
        $one = $workspace . '/one';
        mkdir($one . '/evolution-3.5.9', 0777, true);

        $none = $workspace . '/none';
        mkdir($none, 0777, true);

        $many = $workspace . '/many';
        mkdir($many . '/evolution-3.5.9', 0777, true);
        mkdir($many . '/planted', 0777, true);

        expect(invokeSiteUpdateMethod($this->command, 'resolveExtractedRoot', [$one]))->toBe('evolution-3.5.9')
            ->and(invokeSiteUpdateMethod($this->command, 'resolveExtractedRoot', [$none]))->toBe('')
            ->and(invokeSiteUpdateMethod($this->command, 'resolveExtractedRoot', [$many]))->toBe('')
            ->and(invokeSiteUpdateMethod($this->command, 'resolveExtractedRoot', [$workspace . '/absent']))->toBe('');
    } finally {
        SiteUpdateCommand::rmdirs($workspace);
    }
});

test('startUpdate aborts on a bad package before touching the site', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/SiteUpdateCommand.php');
    $start = strpos($source, 'Start download Evolution CMS');
    $move = strpos($source, 'self::moveFiles($temp_dir');
    $prelude = substr($source, $start, $move - $start);

    // Every guard has to sit between the download and the first site write.
    expect($prelude)->toContain('downloadUpdateArchive')
        ->and($prelude)->toContain('extractUpdateArchive')
        ->and($prelude)->toContain('resolveExtractedRoot')
        ->and(substr_count($prelude, 'return;'))->toBe(3);
});
