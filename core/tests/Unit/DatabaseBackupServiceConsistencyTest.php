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
        ->toContain('fopen($tempFilePath, \'ab\')')
        ->toContain('unlink($tempFilePath)')
        ->toContain('rename($tempFilePath, (string) $filePath)')
        // The dump must never be written straight to the published path, or a
        // failed run would leave a truncated snapshot where a good one was.
        ->not->toContain('fopen((string) $filePath');
});

test('postgres snapshots pass the password by environment, not a shell prefix', function () {
    $source = file_get_contents(__DIR__ . '/../../src/Services/DatabaseBackupService.php');

    expect($source)
        // A leading `PGPASSWORD=… pg_dump` assignment is POSIX shell syntax.
        // cmd.exe answers "'PGPASSWORD' is not recognized", so that form could
        // never produce a backup on Windows. Matched on the concatenation the
        // old code used rather than on the bare name, which still appears in
        // the comment explaining why it went away.
        ->not->toContain("'PGPASSWORD=' . escapeshellarg")
        ->toContain('[\'PGPASSWORD\' => $password]')
        ->toContain('new Process(');
});

test('postgres dump output is streamed rather than buffered in memory', function () {
    $source = file_get_contents(__DIR__ . '/../../src/Services/DatabaseBackupService.php');

    // A dump is as large as the database; collecting it with getOutput()
    // before writing would blow memory on exactly the databases worth backing
    // up. The run callback writes each chunk straight to the handle.
    expect($source)
        ->toContain('$process->run(static function ($type, $buffer) use ($handle)')
        ->toContain('fwrite($handle, $buffer)')
        ->not->toContain('fwrite($handle, $process->getOutput())');
});

test('a snapshot never takes the name of a compressed snapshot from the same second', function () {
    $directory = sys_get_temp_dir() . '/evo-snapshot-names-' . bin2hex(random_bytes(6));
    mkdir($directory);
    $service = new class(dirname(__DIR__, 3) . '/') extends \EvolutionCMS\Services\DatabaseBackupService {
        public function nameIn(string $directory): string
        {
            return basename($this->buildSnapshotFilePath($directory));
        }
    };

    try {
        $first = $service->nameIn($directory);
        // A queued snapshot is compressed and its .sql removed; a second one in the same
        // second used to pick the same name and fail on "Compressed snapshot already exists".
        file_put_contents($directory . '/' . $first . '.gz', 'gz');
        $second = $service->nameIn($directory);
        file_put_contents($directory . '/' . $second, 'sql');
        $third = $service->nameIn($directory);

        expect($first)->toMatch('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/')
            ->and($second)->not->toBe($first)
            ->and($third)->not->toBe($first)
            ->and($third)->not->toBe($second);
    } finally {
        array_map('unlink', glob($directory . '/*') ?: []);
        rmdir($directory);
    }
});
