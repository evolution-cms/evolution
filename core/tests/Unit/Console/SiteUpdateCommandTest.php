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
    $url = invokeSiteUpdateMethod($this->command, 'buildArchiveUrl', ['evolution-cms/evolution', '3.5.4']);

    expect($url)->toBe('https://codeload.github.com/evolution-cms/evolution/zip/refs/tags/3.5.4');
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
    expect($source)->not->toContain('cli-install.php --typeInstall=2');
});

test('site updater repairs composer vendor state before artisan commands', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/SiteUpdateCommand.php');

    expect($source)
        ->toContain('$this->composerInstallCommand()')
        ->toContain('buildCustomComposerUpdateCommand')
        ->toContain("composer dump-autoload -o --no-dev --classmap-authoritative")
        ->not->toContain('new Application()')
        ->not->toContain("runCoreShellCommand('composer update')");

    expect(strpos($source, 'installComposerDependencies();'))->toBeLessThan(strpos($source, '$this->runCoreMigrations();'));
    expect(strpos($source, '$this->composerInstallCommand()'))->toBeLessThan(strpos($source, 'buildCustomComposerUpdateCommand($customPackages)'));
    expect(invokeSiteUpdateMethod($this->command, 'composerInstallCommand'))
        ->toBe('composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --classmap-authoritative');
});

test('site updater builds constrained composer update for custom packages', function () {
    $command = invokeSiteUpdateMethod($this->command, 'buildCustomComposerUpdateCommand', [[
        'evolution-cms/eai',
        'php',
        'ext-json',
        'Seiger/sTask',
        'bad package',
        'evolution-cms/eai',
    ]]);

    expect($command)
        ->toBe("composer update 'evolution-cms/eai' 'seiger/stask' --with-all-dependencies --no-dev --no-interaction --prefer-dist --optimize-autoloader --classmap-authoritative");
});
