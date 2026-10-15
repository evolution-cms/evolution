<?php
/** Run with: php core/tests/standalone/DatabaseBackupTest.php (no site bootstrap or production DB). */
$vendor = dirname(__DIR__, 2) . '/vendor';
require_once $vendor . '/composer/ClassLoader.php';
$testLoader = new Composer\Autoload\ClassLoader();
foreach (require $vendor . '/composer/autoload_psr4.php' as $prefix => $paths) {
    if (preg_match('/^(Illuminate|Carbon|Psr|Symfony)\\\\/', $prefix)) {
        $testLoader->addPsr4($prefix, $paths);
    }
}
$testLoader->register();
foreach (require $vendor . '/composer/autoload_files.php' as $file) {
    if (str_contains(str_replace('\\', '/', $file), '/illuminate/')) { require_once $file; }
}
Illuminate\Support\Facades\Cache::swap(new Illuminate\Cache\Repository(new Illuminate\Cache\ArrayStore()));
require_once dirname(__DIR__, 2) . '/src/Services/DatabaseBackupService.php';
require_once dirname(__DIR__, 2) . '/src/Services/DatabaseBackupArchive.php';
require_once dirname(__DIR__, 2) . '/src/Services/BackgroundDatabaseBackup.php';

use EvolutionCMS\Services\DatabaseBackupArchive;
use EvolutionCMS\Services\BackgroundDatabaseBackup;

function checkBackup(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

$directory = sys_get_temp_dir() . '/evo-backup-test-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
try {
    // Optional classes must not be required when sTask is absent.
    checkBackup((new BackgroundDatabaseBackup())->queue() === null, 'Missing sTask must select synchronous fallback.');
    require dirname(__DIR__, 2) . '/src/Workers/DatabaseBackup.php';
    checkBackup(!class_exists('EvolutionCMS\\Workers\\DatabaseBackup', false), 'Optional worker must load safely without sTask.');

    $sql = "-- Evolution CMS Version:3.5.9\n-- Description: round trip\n"
        . "CREATE TABLE sample (id INTEGER, value TEXT);\n"
        . "INSERT INTO sample VALUES (1, 'Text; with semicolon');\n";
    $source = $directory . '/snapshot.sql';
    file_put_contents($source, $sql);
    $compressionUpdates = [];
    $archive = (new DatabaseBackupArchive())->compress($source, static function ($stage, $processed, $total) use (&$compressionUpdates) {
        $compressionUpdates[] = [$stage, $processed, $total];
    });
    checkBackup($compressionUpdates[0] === ['compress', 0, strlen($sql)], 'Compression must report its byte total.');
    checkBackup(end($compressionUpdates) === ['compress', strlen($sql), strlen($sql)], 'Compression must report all consumed bytes.');
    checkBackup(!is_file($source) && gzdecode(file_get_contents($archive)) === $sql, 'Gzip content must match source SQL.');
    checkBackup(DatabaseBackupArchive::originalSize($archive) === strlen($sql), 'Known uncompressed size must be available.');
    Illuminate\Support\Facades\Cache::flush();
    checkBackup(DatabaseBackupArchive::originalSize($archive) === strlen($sql), 'Original size must be recoverable after a cache flush.');
    $handle = DatabaseBackupArchive::open($archive);
    checkBackup(fgets($handle) === "-- Evolution CMS Version:3.5.9\n", 'Manager must read metadata from gzip.');
    fclose($handle);
    $nativeMetadata = DatabaseBackupArchive::metadata($archive);
    checkBackup($nativeMetadata['Evolution CMS Version'] === '3.5.9'
        && $nativeMetadata['Description'] === 'round trip', 'Gzip CMS metadata must be parsed.');
    $cliHeader = "-- MariaDB dump 10.19\n-- Host: localhost    Database: example_database\n"
        . "-- Server version\t11.4.10-MariaDB\n"
        . "-- Dumping data for table `evo_other`\nINSERT INTO evo_other VALUES ('settings_version','9.9.9');\n"
        . "-- Dumping data for table `evo_system_settings`\nINSERT INTO evo_system_settings VALUES ('settings_version','3.5.8');\n";
    $cliPath = $directory . '/cli.sql';
    file_put_contents($cliPath, $cliHeader);
    file_put_contents($cliPath . '.gz', gzencode($cliHeader));
    $cliMetadata = DatabaseBackupArchive::metadata($cliPath);
    checkBackup($cliMetadata === DatabaseBackupArchive::metadata($cliPath . '.gz'), 'Plain and gzip headers must yield the same metadata.');
    checkBackup($cliMetadata === ['Host' => 'localhost', 'Database' => 'example_database',
        'Server version' => '11.4.10-MariaDB', 'Evolution CMS Version' => '3.5.8', 'Description' => 'Backup'],
        'Legacy CMS version must come from system_settings, with a standard backup description.');
    // Keep size/mtime unchanged so this checks cache invalidation rather than a new key.
    $originalTime = filemtime($cliPath);
    file_put_contents($cliPath, str_replace("'3.5.8'", "'3.5.9'", $cliHeader));
    touch($cliPath, $originalTime);
    clearstatcache(true, $cliPath);
    checkBackup(DatabaseBackupArchive::legacyVersion($cliPath) === '3.5.8', 'Repeated reads must use Laravel Cache.');
    Illuminate\Support\Facades\Cache::flush();
    checkBackup(DatabaseBackupArchive::legacyVersion($cliPath) === '3.5.9', 'A cache flush must reread the dump.');
    $boundedPath = $directory . '/bounded.sql.gz';
    file_put_contents($boundedPath, gzencode(str_repeat("-- Padding\n", 64) . "-- Description: outside header\n"));
    checkBackup(DatabaseBackupArchive::metadata($boundedPath) === ['Description' => 'Backup'], 'Header reading must remain bounded.');

    $restoredTemporary = null;
    DatabaseBackupArchive::restore($archive, function ($path) use (&$restoredTemporary) {
        $restoredTemporary = $path;
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec(file_get_contents($path));
        checkBackup($pdo->query('SELECT value FROM sample')->fetchColumn() === 'Text; with semicolon', 'Restored SQL must execute correctly.');
    });
    checkBackup(!file_exists($restoredTemporary), 'Restore temp file must be removed.');
    try {
        DatabaseBackupArchive::restore($archive, function ($path) use (&$restoredTemporary) {
            $restoredTemporary = $path;
            throw new RuntimeException('Expected importer failure.');
        });
        throw new LogicException('Importer failure was swallowed.');
    } catch (RuntimeException $exception) {
        checkBackup($exception->getMessage() === 'Expected importer failure.', 'Restore must propagate importer failure.');
    }
    checkBackup(!file_exists($restoredTemporary), 'Failed restore must remove its temp file.');

    $corrupt = $directory . '/corrupt.sql.gz';
    foreach ([substr(file_get_contents($archive), 0, -5), 'not gzip'] as $bytes) {
        file_put_contents($corrupt, $bytes);
        $called = false;
        try {
            DatabaseBackupArchive::restore($corrupt, function () use (&$called) { $called = true; });
            throw new LogicException('Corrupt gzip was accepted.');
        } catch (RuntimeException $exception) {
            checkBackup(!$called, 'Corrupt gzip must never reach the importer.');
        }
    }
    file_put_contents($source, $sql);
    try {
        (new DatabaseBackupArchive())->compress($source);
        throw new LogicException('Existing archive was overwritten.');
    } catch (RuntimeException $exception) {
        checkBackup(file_get_contents($source) === $sql, 'Compression failure must retain source SQL.');
    }
    $seen = null;
    DatabaseBackupArchive::restore($source, function ($path) use (&$seen) { $seen = $path; });
    checkBackup($seen === $source && is_file($source), 'Plain snapshots must pass through unchanged.');
    checkBackup(DatabaseBackupArchive::headerValue("title\nDROP TABLE sample;\r") === 'title DROP TABLE sample;', 'Description must remain a SQL comment.');
    $native = new class($directory) extends DatabaseBackupArchive {
        private string $testDirectory;
        public function __construct(string $directory) { $this->testDirectory = $directory . '/'; }
        protected function resolveSnapshotPath() { return $this->testDirectory; }
        protected function prepareSnapshotPath($path) {}
        public function createSnapshot($description = '') {
            $path = $this->testDirectory . 'queued.sql';
            file_put_contents($path, '-- Description: ' . $description . "\nSELECT 1;\n");
            return ['path' => $path, 'description' => $description, 'filename' => basename($path), 'size' => filesize($path)];
        }
    };
    $lock = fopen($directory . '/.database-backup.lock', 'c+');
    flock($lock, LOCK_EX);
    try {
        $native->create('locked');
        throw new LogicException('Concurrent backup was accepted.');
    } catch (RuntimeException $exception) {
        checkBackup(str_contains($exception->getMessage(), 'already running'), 'Lock failure must be reported.');
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
    $result = $native->create("Queued\nbackup");
    checkBackup($result['filename'] === 'queued.sql.gz' && $result['size'] > 0, 'Worker snapshot must return gzip metadata.');
    checkBackup($result['description'] === 'Queued backup', 'Worker must pass sanitized description to the CMS generator.');
    unlink($result['path']);
    $defaultResult = $native->create('  ');
    checkBackup($defaultResult['description'] === 'Backup', 'Empty descriptions must be saved as Backup.');
    unlink($directory . '/.database-backup.lock');
    echo "PASS: fallback, optional worker loading, gzip round trip, metadata, SQLite restore, cleanup, corruption, collision, plain SQL, header safety, concurrent lock, queued snapshot.\n";
} finally {
    foreach (glob($directory . '/*') ?: [] as $file) { unlink($file); }
    rmdir($directory);
}
