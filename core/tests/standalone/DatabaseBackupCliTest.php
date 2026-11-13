<?php
/** Exercise native-client orchestration with a local fake executable, without any database access. */
require dirname(__DIR__, 2) . '/vendor/autoload.php';

$directory = sys_get_temp_dir() . '/evo-cli-backup-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
$binary = $directory . '/fake-mariadb-dump';
$body = <<<'PHP'
#!/usr/bin/env php
<?php
$option = $argv[1] ?? '';
$path = substr($option, strlen('--defaults-extra-file='));
file_put_contents(__DIR__ . '/options-path', $path);
if (!str_starts_with($option, '--defaults-extra-file=') || !is_file($path)
    || (fileperms($path) & 0777) !== 0600
    || !str_contains(file_get_contents($path), 'fake-only-test-password')
    || str_contains(implode(' ', $argv), 'fake-only-test-password')) { exit(7); }
$separator = array_search('--', $argv, true);
if ($separator === false || array_slice($argv, $separator + 1) !== ['example_database', 'evo_sample']) { exit(8); }
echo "CREATE TABLE evo_sample (id INT);\nINSERT INTO evo_sample VALUES (1);\n";
PHP;
file_put_contents($binary, $body);
chmod($binary, 0700);
$service = new class extends EvolutionCMS\Services\DatabaseBackupArchive {
    public function runClient(string $binary, string $target): bool {
        return $this->writeMySqlSnapshot($binary, ['host' => 'localhost', 'username' => 'test', 'password' => 'fake-only-test-password'],
            'example_database', ['evo_sample'], $target,
            "-- Evolution CMS Version:3.5.8\n-- Database: example_database\n-- Description: Backup\n", 1000);
    }
};
try {
    $updates = [];
    $service->setProgressCallback(function ($processed, $total) use (&$updates) { $updates[] = [$processed, $total]; });
    $target = $directory . '/snapshot.sql';
    $service->runClient($binary, $target);
    $sql = file_get_contents($target);
    if (!str_starts_with($sql, '-- Evolution CMS Version:3.5.8') || !str_contains($sql, 'INSERT INTO evo_sample VALUES (1);')) {
        throw new RuntimeException('CLI snapshot lost its header or data.');
    }
    if (is_file(trim(file_get_contents($directory . '/options-path'))) || glob($directory . '/.database-backup-*')) {
        throw new RuntimeException('Private staging files were not removed.');
    }
    if (count($updates) < 3 || end($updates) !== [1000, 1000]) { throw new RuntimeException('Client progress was not reported.'); }
    file_put_contents($binary, str_replace('echo "CREATE TABLE', 'fwrite(STDERR, "fake-only-test-password"); exit(2); echo "CREATE TABLE', $body));
    try {
        $service->runClient($binary, $directory . '/failed.sql');
        throw new LogicException('Failed dump was accepted.');
    } catch (RuntimeException $error) {
        if (str_contains($error->getMessage(), 'fake-only-test-password')) { throw new LogicException('Client error exposed a password.'); }
    }
    if (is_file($directory . '/failed.sql') || glob($directory . '/.database-backup-*')
        || is_file(trim(file_get_contents($directory . '/options-path')))) {
        throw new RuntimeException('Failed client left a published snapshot or private files.');
    }
    echo "PASS: streaming CLI dump, metadata header, table scope, private credentials, progress, error redaction, failure cleanup.\n";
} finally {
    foreach (glob($directory . '/*') ?: [] as $file) { unlink($file); }
    foreach (glob($directory . '/.database-backup-*') ?: [] as $file) { unlink($file); }
    rmdir($directory);
}
