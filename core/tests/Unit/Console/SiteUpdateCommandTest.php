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
    expect(strpos($source, '$this->cleanupUpdatePlaceholderFiles();'))->toBeLessThan(strpos($source, '$this->installComposerDependencies('));
    expect($normalizedPairs)->toHaveCount(5);
    expect($normalizedPairs[0][0])->toEndWith('/ht.access');
    expect($normalizedPairs[0][1])->toEndWith('/.htaccess');
    expect($normalizedPairs[1][0])->toEndWith('/sample-robots.txt');
    expect($normalizedPairs[1][1])->toEndWith('/robots.txt');
    expect($normalizedPairs[2][0])->toEndWith('/core/custom/.env.docker.example');
    expect($normalizedPairs[2][1])->toEndWith('/core/custom/.env.docker');
    expect($normalizedPairs[3][0])->toEndWith('/core/custom/composer.json.example');
    expect($normalizedPairs[3][1])->toEndWith('/core/custom/composer.json');
    expect($normalizedPairs[4][0])->toEndWith('/core/custom/config/cms/settings/ControllerNamespace.php.example');
    expect($normalizedPairs[4][1])->toEndWith('/core/custom/config/cms/settings/ControllerNamespace.php');
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

    expect($command)
        ->toBe("composer update 'evolution-cms/eai:1.2.3' 'seiger/stask:1.0.10' 'seiger/scommerce' --with-all-dependencies --no-dev --no-interaction --prefer-dist --optimize-autoloader --classmap-authoritative --no-scripts");

    $composerBinary === false ? putenv('COMPOSER_BINARY') : putenv('COMPOSER_BINARY=' . $composerBinary);
    $composerBin === false ? putenv('COMPOSER_BIN') : putenv('COMPOSER_BIN=' . $composerBin);
});

test('site updater accepts configured composer binary', function () {
    $composerBinary = getenv('COMPOSER_BINARY');

    putenv('COMPOSER_BINARY=/home/evo/.composer/composer');

    expect(invokeSiteUpdateMethod($this->command, 'composerInstallCommand'))
        ->toBe("'/home/evo/.composer/composer' install --no-dev --no-interaction --prefer-dist --optimize-autoloader --classmap-authoritative --no-scripts");
    expect(invokeSiteUpdateMethod($this->command, 'composerDumpAutoloadCommand'))
        ->toBe("'/home/evo/.composer/composer' dump-autoload -o --no-dev --classmap-authoritative --no-scripts");

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
