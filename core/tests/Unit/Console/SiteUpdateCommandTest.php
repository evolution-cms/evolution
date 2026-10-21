<?php use EvolutionCMS\Console\SiteUpdateCommand;

beforeEach(function () {
    $this->command = new SiteUpdateCommand();
});

function invokeSiteUpdateMethod(SiteUpdateCommand $command, string $method, array $args = [])
{
    $reflection = new ReflectionClass($command);
    $instanceMethod = $reflection->getMethod($method);

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
    $source = file_get_contents(dirname(__DIR__, 3) . '/src/Traits/RunsComposerShell.php');

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
        ->and(substr_count($prelude, 'return false;'))->toBe(3);
});

test('a failed update exits with a failure code', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/SiteUpdateCommand.php');

    // The manager update task reads the exit code: a mistyped fork branch is no success.
    expect(substr_count($source, 'return $this->startUpdate() === false ? self::FAILURE : self::SUCCESS;'))->toBe(2)
        ->and($source)->toContain("Could not read the tags of ' . \$updateRepository . ' from GitHub. Nothing was changed.');\n\n            return false;");
});

$upstreamTags = ['nightly-3.5.x', '3.5.8', '3.5.7', '3.5.10-beta', '2.0.4', '4.0.0'];

test('an explicit ref always wins, in either direction', function () use ($upstreamTags) {
    $nightly = ['version' => '3.5.9', 'ref' => '3.5.x'];
    $release = ['version' => '3.5.8'];

    expect(invokeSiteUpdateMethod($this->command, 'resolveTargetRef', ['3.5.8', $nightly, $upstreamTags]))->toBe('3.5.8')
        ->and(invokeSiteUpdateMethod($this->command, 'resolveTargetRef', ['3.5.x', $release, $upstreamTags]))->toBe('3.5.x')
        ->and(invokeSiteUpdateMethod($this->command, 'resolveTargetRef', ['3.5.x', $nightly, $upstreamTags]))->toBe('3.5.x');
});

test('a site updated from a branch or the nightly tag follows it again', function () use ($upstreamTags) {
    expect(invokeSiteUpdateMethod($this->command, 'resolveTargetRef', ['null', ['version' => '3.5.9', 'ref' => '3.5.x'], $upstreamTags]))->toBe('3.5.x')
        ->and(invokeSiteUpdateMethod($this->command, 'resolveTargetRef', ['null', ['version' => '3.5.9', 'ref' => 'nightly-3.5.x'], $upstreamTags]))->toBe('nightly-3.5.x')
        // Once the branch version matches the release, the note still keeps it on the branch.
        ->and(invokeSiteUpdateMethod($this->command, 'resolveTargetRef', ['null', ['version' => '3.5.8', 'ref' => '3.5.x'], $upstreamTags]))->toBe('3.5.x');
});

test('an update without a version never moves a development build back to a release', function () use ($upstreamTags) {
    // 3.5.x without a note: installed by Composer, from a git checkout, or by the 3.5.8 updater.
    expect(invokeSiteUpdateMethod($this->command, 'resolveTargetRef', ['null', ['version' => '3.5.9'], $upstreamTags]))->toBe('3.5.x')
        // A pinned commit is no line to follow, but neither is it downgraded.
        ->and(invokeSiteUpdateMethod($this->command, 'resolveTargetRef', ['null', ['version' => '3.5.9', 'ref' => '922ece660'], $upstreamTags]))->toBe('3.5.x')
        ->and(invokeSiteUpdateMethod($this->command, 'resolveTargetRef', ['null', ['version' => '3.6.0'], []]))->toBe('3.6.x');
});

test('a release moves only to a newer release of its major version', function () use ($upstreamTags) {
    expect(invokeSiteUpdateMethod($this->command, 'resolveTargetRef', ['null', ['version' => '3.5.7'], $upstreamTags]))->toBe('3.5.8')
        ->and(invokeSiteUpdateMethod($this->command, 'resolveTargetRef', ['null', ['version' => '3.5.8'], $upstreamTags]))->toBe('')
        ->and(invokeSiteUpdateMethod($this->command, 'resolveTargetRef', ['null', ['version' => 'unknown'], $upstreamTags]))->toBe('');
});

test('latestStableTag compares versions and skips other majors, pre-releases and moving tags', function () {
    $tags = ['nightly-3.5.x', '3.5.9', '3.5.10', '3.5.11-beta', '4.0.0', 'v3.9.9'];

    expect(invokeSiteUpdateMethod($this->command, 'latestStableTag', [$tags, 3]))->toBe('3.5.10')
        ->and(invokeSiteUpdateMethod($this->command, 'latestStableTag', [$tags, 2]))->toBe('');
});

test('extractTagNames reads names from the GitHub tags response', function () {
    $response = [['name' => 'nightly-3.5.x'], ['name' => '3.5.8'], ['commit' => []], 'junk'];

    expect(invokeSiteUpdateMethod($this->command, 'extractTagNames', [$response]))->toBe(['nightly-3.5.x', '3.5.8'])
        ->and(invokeSiteUpdateMethod($this->command, 'extractTagNames', [null]))->toBe([]);
});

test('buildArchiveUrl downloads a known non-release tag from the tag path', function () {
    $url = invokeSiteUpdateMethod($this->command, 'buildArchiveUrl', ['evolution-cms/evolution', 'nightly-3.5.x', ['nightly-3.5.x', '3.5.8']]);

    expect($url)->toBe('https://codeload.github.com/evolution-cms/evolution/zip/refs/tags/nightly-3.5.x')
        ->and(invokeSiteUpdateMethod($this->command, 'buildArchiveUrl', ['evolution-cms/evolution', '3.5.x', ['nightly-3.5.x']]))
        ->toBe('https://codeload.github.com/evolution-cms/evolution/zip/refs/heads/3.5.x');
});

function versionFileCommand(string $file): SiteUpdateCommand
{
    return new class($file) extends SiteUpdateCommand {
        public function __construct(private string $file)
        {
            parent::__construct();
        }

        protected function versionFilePath(): string
        {
            return $this->file;
        }
    };
}

test('recordInstallSource notes the ref in the overlaid version file and keeps its data', function () {
    $workspace = updateGuardWorkspace();
    $file = $workspace . '/version.php';
    // What the 3.5.x archive ships.
    file_put_contents($file, SiteUpdateCommand::buildVersionFile([
        'version' => '3.5.9',
        'release_date' => 'Oct 3, 2026',
        'branch' => 'Evolution CMS',
        'full_appname' => 'Evolution CMS 3.5.9 (Oct 3, 2026)',
    ]));
    $command = versionFileCommand($file);

    try {
        invokeSiteUpdateMethod($command, 'recordInstallSource', ['3.5.x', 'evolution-cms/evolution']);
        // The next nightly update records the ref over the same data again.
        invokeSiteUpdateMethod($command, 'recordInstallSource', ['3.5.x', 'evolution-cms/evolution']);

        expect(include $file)->toBe([
            'version' => '3.5.9',
            'release_date' => 'Oct 3, 2026',
            'branch' => 'Evolution CMS',
            'full_appname' => 'Evolution CMS 3.5.9 (Oct 3, 2026)',
            'ref' => '3.5.x',
        ])
            ->and(invokeSiteUpdateMethod($command, 'describeInstalledVersion'))->toBe('Evolution CMS 3.5.9 (Oct 3, 2026) from 3.5.x');
    } finally {
        SiteUpdateCommand::rmdirs($workspace);
    }
});

test('a crafted ref is written to the version file as data', function () {
    $workspace = updateGuardWorkspace();
    $file = $workspace . '/version.php';
    file_put_contents($file, SiteUpdateCommand::buildVersionFile(['version' => '3.5.9']));
    $command = versionFileCommand($file);
    $ref = "x'];echo 'pwned';//";

    try {
        invokeSiteUpdateMethod($command, 'recordInstallSource', [$ref, "x/y'];echo 'pwned';//"]);

        expect((include $file)['ref'])->toBe($ref)
            ->and((include $file)['repository'])->toBe("x/y'];echo 'pwned';//");
    } finally {
        SiteUpdateCommand::rmdirs($workspace);
    }
});

test('a fork branch is noted with its repository, a release of the official one drops both', function () {
    $workspace = updateGuardWorkspace();
    $file = $workspace . '/version.php';
    $archive = ['version' => '3.5.9', 'full_appname' => 'Evolution CMS 3.5.9 (Oct 3, 2026)'];
    file_put_contents($file, SiteUpdateCommand::buildVersionFile($archive));
    $command = versionFileCommand($file);

    try {
        invokeSiteUpdateMethod($command, 'recordInstallSource', ['fix-table-prefix', 'middleDuckAi/evolution']);

        expect(include $file)->toBe($archive + ['ref' => 'fix-table-prefix', 'repository' => 'middleDuckAi/evolution'])
            ->and(invokeSiteUpdateMethod($command, 'describeInstalledVersion'))
            ->toBe('Evolution CMS 3.5.9 (Oct 3, 2026) from middleDuckAi/evolution fix-table-prefix');

        // Back to 3.5.8: the version file of an archive never carries the note, and
        // even one that did is cleared for a release of the official repository.
        invokeSiteUpdateMethod($command, 'recordInstallSource', ['3.5.8', 'evolution-cms/evolution']);

        expect(include $file)->toBe($archive)
            ->and(invokeSiteUpdateMethod($command, 'describeInstalledVersion'))->toBe('Evolution CMS 3.5.9 (Oct 3, 2026)');

        // A release tag of a fork keeps the fork, so an update without a version stays there.
        invokeSiteUpdateMethod($command, 'recordInstallSource', ['3.5.9', 'middleDuckAi/evolution']);

        expect(include $file)->toBe($archive + ['repository' => 'middleDuckAi/evolution']);
    } finally {
        SiteUpdateCommand::rmdirs($workspace);
    }
});

test('parseGitHubSource reads the URLs a browser shows for a branch, tag or commit', function () {
    $parse = fn (string $value) => invokeSiteUpdateMethod($this->command, 'parseGitHubSource', [$value]);

    expect($parse('https://github.com/middleDuckAi/evolution/tree/fix-table-prefix'))
        ->toBe(['repository' => 'middleDuckAi/evolution', 'ref' => 'fix-table-prefix'])
        ->and($parse('https://github.com/middleDuckAi/evolution/tree/feature/nested-branch/'))
        ->toBe(['repository' => 'middleDuckAi/evolution', 'ref' => 'feature/nested-branch'])
        ->and($parse('github.com/middleDuckAi/evolution/releases/tag/3.5.9-fork'))
        ->toBe(['repository' => 'middleDuckAi/evolution', 'ref' => '3.5.9-fork'])
        ->and($parse('https://www.github.com/middleDuckAi/evolution.git/commit/922ece66071acecaea9afb8486791738acc6de5e?diff=split'))
        ->toBe(['repository' => 'middleDuckAi/evolution', 'ref' => '922ece66071acecaea9afb8486791738acc6de5e'])
        ->and($parse('https://github.com/evolution-cms/evolution/tree/3.5.x'))
        ->toBe(['repository' => 'evolution-cms/evolution', 'ref' => '3.5.x'])
        // Plain refs keep their meaning.
        ->and($parse('3.5.x'))->toBeNull()
        ->and($parse('3.5.8'))->toBeNull()
        ->and($parse('null'))->toBeNull()
        ->and($parse('https://gitlab.com/middleDuckAi/evolution/tree/x'))->toBeNull();
});

test('parseGitHubSource refuses a URL without a ref or with a ref git would refuse', function (string $url) {
    invokeSiteUpdateMethod($this->command, 'parseGitHubSource', [$url]);
})->with([
    'https://github.com/middleDuckAi/evolution',
    'https://github.com/middleDuckAi/evolution/tree/a..b',
    'https://github.com/middleDuckAi/evolution/tree/a%20b',
    'https://github.com/middleDuckAi/evolution/tree/a%0Ab',
    'https://github.com/middleDuckAi/evolution/tree/a~1',
])->throws(InvalidArgumentException::class);

test('the command help lists every version format with an example', function () {
    $help = $this->command->getHelp();
    $argument = $this->command->getDefinition()->getArgument('version')->getDescription();

    expect($help)->toContain('Version formats:')
        ->and($help)->toContain('(none)')
        ->and($help)->toContain('3.5.8')
        ->and($help)->toContain('3.5.x')
        ->and($help)->toContain('nightly-3.5.x')
        ->and($help)->toContain('922ece660')
        ->and($help)->toContain('https://github.com/<owner>/<repo>/tree/<branch or tag>')
        ->and($help)->toContain('https://github.com/<owner>/<repo>/releases/tag/<tag>')
        ->and($help)->toContain('https://github.com/<owner>/<repo>/commit/<hash>')
        ->and($help)->toContain('--repository=<owner>/<repo>')
        ->and($help)->toContain('php artisan make:site update https://github.com/middleDuckAi/evolution/tree/add-folder-copying')
        ->and($argument)->toContain('3.5.8')
        ->and($argument)->toContain('3.5.x')
        ->and($argument)->toContain('nightly-3.5.x')
        ->and($argument)->toContain('https://github.com/owner/evolution/tree/branch');
});

test('a version that cannot be used is answered with the formats', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/SiteUpdateCommand.php');
    $download = substr($source, strpos($source, "Could not download the update package. Nothing was changed.'"), 300);

    expect($source)->toContain("catch (\\InvalidArgumentException \$exception) {\n            \$this->error(\$exception->getMessage());\n            \$this->line(self::VERSION_FORMATS);")
        ->and($download)->toContain('$this->line(self::VERSION_FORMATS);');
});

test('an update without a version stays in the repository the site came from', function () {
    expect(invokeSiteUpdateMethod($this->command, 'resolveUpdateRepository', ['middleDuckAi/evolution']))->toBe('middleDuckAi/evolution')
        ->and(invokeSiteUpdateMethod($this->command, 'resolveUpdateRepository', [' middleDuckAi/evolution/ ']))->toBe('middleDuckAi/evolution');

    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/SiteUpdateCommand.php');

    // An explicit plain version is how a site switches back from a fork.
    expect($source)->toContain("\$requestedVersion === 'null' ? (string) (\$currentVersion['repository'] ?? '') : ''");
});

test('the commit of a branch archive is noted, so the manager can tell when the branch moved', function () {
    $workspace = updateGuardWorkspace();
    $archive = $workspace . '/new_version.zip';
    $file = $workspace . '/version.php';
    $zip = new ZipArchive();
    $zip->open($archive, ZipArchive::CREATE);
    $zip->addFromString('evolution-3.5.x/index.php', '<?php');
    // What codeload.github.com writes into every archive.
    $zip->setArchiveComment('5fa4986a322edf06392a415a06d6e98126355ba9');
    $zip->close();
    file_put_contents($file, SiteUpdateCommand::buildVersionFile(['version' => '3.5.9']));
    $command = versionFileCommand($file);

    try {
        expect(invokeSiteUpdateMethod($command, 'extractUpdateArchive', [$archive, $workspace . '/_temp']))->toBeTrue();

        invokeSiteUpdateMethod($command, 'recordInstallSource', ['3.5.x', 'evolution-cms/evolution']);
        expect(include $file)->toBe(['version' => '3.5.9', 'ref' => '3.5.x', 'commit' => '5fa4986a322edf06392a415a06d6e98126355ba9']);

        // A release carries its own version; the commit goes with the ref.
        invokeSiteUpdateMethod($command, 'recordInstallSource', ['3.5.8', 'evolution-cms/evolution']);
        expect(include $file)->toBe(['version' => '3.5.9']);
    } finally {
        SiteUpdateCommand::rmdirs($workspace);
    }
});

test('an archive comment that is no commit hash is not noted', function () {
    $workspace = updateGuardWorkspace();
    $archive = $workspace . '/new_version.zip';
    $zip = new ZipArchive();
    $zip->open($archive, ZipArchive::CREATE);
    $zip->addFromString('evolution-3.5.x/index.php', '<?php');
    $zip->setArchiveComment("x'];echo 1;//");
    $zip->close();
    $command = versionFileCommand($workspace . '/version.php');

    try {
        invokeSiteUpdateMethod($command, 'extractUpdateArchive', [$archive, $workspace . '/_temp']);

        $property = new ReflectionProperty(SiteUpdateCommand::class, 'archiveCommit');
        expect($property->getValue($command))->toBe('');
    } finally {
        SiteUpdateCommand::rmdirs($workspace);
    }
});

test('recordInstallSource leaves a missing version file alone', function () {
    $workspace = updateGuardWorkspace();
    $file = $workspace . '/version.php';

    try {
        invokeSiteUpdateMethod(versionFileCommand($file), 'recordInstallSource', ['3.5.x', 'middleDuckAi/evolution']);

        expect(file_exists($file))->toBeFalse();
    } finally {
        SiteUpdateCommand::rmdirs($workspace);
    }
});

test('clearCompiledViews drops the views of the replaced version, also on a switch back', function () {
    $workspace = updateGuardWorkspace();
    file_put_contents($workspace . '/b24302c270cd0a5c777ca9ba56636912.php', '<?php new SimplePie\SimplePie();');
    file_put_contents($workspace . '/.gitignore', "*\n");
    $command = new class($workspace) extends SiteUpdateCommand {
        public function __construct(private string $path)
        {
            parent::__construct();
        }

        protected function compiledViewsPath(): string
        {
            return $this->path;
        }

        public function line($string, $style = null, $verbosity = null)
        {
        }
    };

    try {
        invokeSiteUpdateMethod($command, 'clearCompiledViews');

        expect(glob($workspace . '/*.php'))->toBe([])
            ->and(is_file($workspace . '/.gitignore'))->toBeTrue();
    } finally {
        SiteUpdateCommand::rmdirs($workspace);
    }
});

test('make:site clears compiled views after the update seeders', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/SiteUpdateCommand.php');

    expect(strpos($source, '$this->clearCompiledViews();'))->toBeGreaterThan(strpos($source, '$this->runUpdateSeeders();'));
});

test('only a release rewrites the version file from release metadata', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/SiteUpdateCommand.php');
    $start = strpos($source, 'self::moveFiles($temp_dir');
    $end = strpos($source, '$delete_file = EVO_BASE_PATH');
    $block = substr($source, $start, $end - $start);

    // The nightly release is tagged nightly-3.5.x, which composer.json cannot carry
    // as a version: written to the version file it broke the update halfway.
    expect($block)->toContain("if (\$this->isSemanticVersionTag(\$targetRef)) {\n                \$releases = \$this->fetchRepositoryMetadata(\$updateRepository, 'releases');")
        ->and($block)->toContain('$this->recordInstallSource($targetRef, $updateRepository);');
});
