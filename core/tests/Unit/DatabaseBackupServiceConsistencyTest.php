<?php

test('mysql and sqlite dumpers wrap snapshot reads in database transactions', function () {
    $mysql = file_get_contents(__DIR__ . '/../../src/Support/MysqlDumper.php');
    $sqlite = file_get_contents(__DIR__ . '/../../src/Support/SqliteDumper.php');

    expect($mysql)
        ->toContain('START TRANSACTION WITH CONSISTENT SNAPSHOT')
        ->toContain("if (\$callBack !== 'snapshot')")
        ->toContain('$pdo->exec(\'COMMIT\')')
        ->toContain('$pdo->exec(\'ROLLBACK\')')
        ->and($sqlite)
        ->toContain('BEGIN IMMEDIATE TRANSACTION')
        ->toContain('$pdo->exec(\'COMMIT\')')
        ->toContain('$pdo->exec(\'ROLLBACK\')');
});

test('postgres snapshots are written to a temporary file before atomic publish', function () {
    $source = file_get_contents(__DIR__ . '/../../src/Services/DatabaseBackupService.php');

    expect($source)
        ->toContain('buildTempSnapshotFilePath')
        ->toContain('>> \' . escapeshellarg((string) $tempFilePath)')
        ->toContain('unlink($tempFilePath)')
        ->toContain('rename($tempFilePath, (string) $filePath)')
        ->not->toContain('>> \' . escapeshellarg((string) $filePath)');
});
