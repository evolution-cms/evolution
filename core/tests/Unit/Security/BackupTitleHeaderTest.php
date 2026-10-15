<?php

use EvolutionCMS\Services\DatabaseBackupService;

/*
| The backup title is written into a comment line of the SQL dump ("# Description: ..." for MySQL,
| "-- Description: ..." for SQLite), by DatabaseBackupService::buildSqlHeader() and by both legacy
| dumpers. Only trim() was applied, so a line break inside the title ended the comment and the rest
| of the title became a statement that a later restore executes. The backup list then printed the
| header fields raw.
*/

it('turns line breaks and control characters of a header value into spaces', function () {
    expect(DatabaseBackupService::headerValue("nightly\r\nDROP TABLE evo_users;\n--\x00x"))
        ->toBe('nightly DROP TABLE evo_users; -- x')
        ->and(DatabaseBackupService::headerValue('  Before update  '))->toBe('Before update')
        ->and(DatabaseBackupService::headerValue('Перед оновленням'))->toBe('Перед оновленням')
        ->and(DatabaseBackupService::headerValue(['x']))->toBe('')
        ->and(DatabaseBackupService::headerValue(null))->toBe('');
});

it('writes the backup title through the helper in every dump header', function (string $file) {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/' . $file);

    expect($source)->toMatch("/Description: ['\"] \\. (self|\\\\EvolutionCMS\\\\Services\\\\DatabaseBackupService)::headerValue\\(\\\$_REQUEST\\['backup_title'\\] \\?\\? ''\\)/")
        ->and($source)->not->toContain("trim(\$_REQUEST['backup_title'] ?? '')");
})->with([
    'service' => ['Services/DatabaseBackupService.php'],
    'mysql' => ['Support/MysqlDumper.php'],
    'sqlite' => ['Support/SqliteDumper.php'],
]);

it('escapes the dump header fields in the backup list', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/actions/bkmanager.static.php');

    expect($source)->toContain("e((string) (\$details['Description'] ?? 'Undefined'))")
        ->and($source)->toContain("confirmRevert(<?= e((string) json_encode((string) \$filename)) ?>)")
        ->and($source)->not->toContain("<td><?= (\$details['Description'] ?? 'Undefined') ?></td>");
});
