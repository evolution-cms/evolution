<?php

/*
 * 3.6 drops migrations_install, the installer log of releases before 3.5.10, once `migrations` holds
 * every row of it: a site that skipped 3.5.10 has it copied by the same migrate run.
 */

function dropInstallerLogRun(array $args = []): array
{
    $out = evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/drop-installer-log.php', $args, $exit);
    expect($exit)->toBe(0, $out);

    return json_decode(trim($out), true, flags: JSON_THROW_ON_ERROR);
}

test('the old installer log is copied into the baseline and then dropped', function () {
    expect(dropInstallerLogRun())->toBe([
        'error' => null,
        'migrations' => [
            '2018_06_29_182342_create_site_content_table' => 0,
            '2022_05_15_185152_create_s_gallery_table' => 0,
            '2026_11_23_000000_drop_migrations_install_table' => 1,
        ],
        'installer_log' => false,
    ]);
});

test('a site without the old installer log only records the migration', function () {
    expect(dropInstallerLogRun(['fresh']))->toBe([
        'error' => null,
        'migrations' => ['2026_11_23_000000_drop_migrations_install_table' => 1],
        'installer_log' => false,
    ]);
});

test('the drop refuses while migrations lacks a row of the old log', function () {
    expect(dropInstallerLogRun(['uncopied']))->toBe([
        'error' => '2 migrations of migrations_install are not in migrations; it was kept.',
        'migrations' => [],
        'installer_log' => true,
    ]);
});
