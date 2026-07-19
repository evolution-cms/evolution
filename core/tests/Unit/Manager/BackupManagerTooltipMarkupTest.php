<?php

test('backup manager tooltip uses clean plain text lines', function () {
    $basePath = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR;
    $backupManager = file_get_contents($basePath . 'manager/actions/bkmanager.static.php');
    $tooltipCss = file_get_contents($basePath . 'manager/media/style/default/css/custom.css');

    expect($backupManager)->toContain('implode("\n", $tooltipLines)')
        ->and($backupManager)->not->toContain('\n<br>')
        ->and($backupManager)->toContain("['Server version', 'PHP Version', 'Host']")
        ->and($tooltipCss)->toContain('white-space: pre-line');
});

test('backup manager delegates snapshots to database backup service', function () {
    $basePath = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR;
    $backupManager = file_get_contents($basePath . 'manager/actions/bkmanager.static.php');

    expect($backupManager)
        ->toContain('new EvolutionCMS\Services\DatabaseBackupService(EVO_BASE_PATH)')
        ->toContain('->createSnapshot(')
        ->not->toContain('$output .= "-- Evolution CMS Version:"')
        ->not->toContain(' --clean --inserts --no-owner --no-privileges >> ');
});
