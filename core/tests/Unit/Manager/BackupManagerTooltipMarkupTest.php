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

test('backup manager snapshot detail parser keeps windows drive colons', function () {
    $basePath = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR;
    $backupManager = file_get_contents($basePath . 'manager/actions/bkmanager.static.php');

    expect($backupManager)
        ->toContain('function parseBackupSnapshotDetailValue')
        ->toContain('ltrim($value, " \t:")')
        ->toContain('parseBackupSnapshotDetailValue($line, $fileLabel)')
        ->not->toContain("str_replace([\n                                                                \$fileLabel,\n                                                                ':'");
});
