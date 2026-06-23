<?php

/**
 * Guards that the four update entry points stay consistent:
 *  - web installer (install/src/controllers/install.php)
 *  - install-folder CLI installer (install/cli-install.php)
 *  - "php artisan make:site update" (core/src/Console/SiteUpdateCommand.php)
 *  - manager / CMS update (delegates to make:site update)
 *
 * The core migration set (core/database/migrations) is authoritative. Every path
 * must end up applying the same migrations and the same update seeders.
 */

$baseline = '2025_12_25_000000_initial_schema';
$coreMigrationsDir = dirname(__DIR__, 3) . '/database/migrations';
$stubMigrationsDir = dirname(__DIR__, 4) . '/install/stubs/migrations';

$migrationNames = static function (string $dir): array {
    return array_map(
        static fn ($file) => basename($file, '.php'),
        (array) glob($dir . '/*.php')
    );
};

test('post-baseline install migrations are mirrored into core', function () use ($baseline, $coreMigrationsDir, $stubMigrationsDir, $migrationNames) {
    // The core path (make:site update / CMS) only runs core/database/migrations, so
    // any post-baseline migration shipped in install/stubs must also live in core or
    // that path would silently skip it.
    $coreNames = $migrationNames($coreMigrationsDir);
    $postBaselineStub = array_filter(
        $migrationNames($stubMigrationsDir),
        static fn ($name) => strcmp($name, $baseline) > 0
    );

    $missingFromCore = array_values(array_diff($postBaselineStub, $coreNames));

    expect($missingFromCore)->toBe([]);
});

test('the sid column fix is part of the authoritative core migration set', function () use ($coreMigrationsDir) {
    expect(is_file($coreMigrationsDir . '/2026_01_17_000000_fix_columns.php'))->toBeTrue();
});

test('installer paths run the core migrate after seeding', function () {
    // So core-only migrations (e.g. the system task tables) reach installer-based sites.
    $webInstaller = (string) file_get_contents(dirname(__DIR__, 4) . '/install/src/controllers/install.php');
    $cliInstaller = (string) file_get_contents(dirname(__DIR__, 4) . '/install/cli-install.php');

    expect($webInstaller)->toContain("Console::call('migrate', ['--force' => true])")
        ->and($cliInstaller)->toContain("Console::call('migrate', ['--force' => true])");
});

test('make:site update applies the update seeders', function () {
    $command = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/SiteUpdateCommand.php');

    expect($command)->toContain('protected function runUpdateSeeders')
        ->and($command)->toContain('$this->runUpdateSeeders();')
        ->and($command)->toContain('install/stubs/seeds/update');
});
