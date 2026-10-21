<?php
namespace Seiger\sTask\Models {
    class sTaskModel {
        public int $id = 1;
        public int $started_by = 1;
        public int $progress = 0;
        public function update(array $values): void {
            if (isset($values['progress'])) { $this->progress = $values['progress']; }
        }
    }
}
namespace Seiger\sTask\Workers {
    class BaseWorker {
        public array $snapshots = [];
        protected function pushProgress($task, array $snapshot): void { $this->snapshots[] = $snapshot; }
        protected function markFinished($task, $result, $message): void {
            $this->pushProgress($task, ['status' => 'finished', 'progress' => $task->progress, 'message' => $message]);
        }
    }
}
namespace EvolutionCMS\Services {
    class DatabaseBackupArchive {
        public function create(string $description, ?callable $progress): array {
            foreach (['dump', 'compress'] as $stage) {
                foreach ([0, 25, 50, 75, 100] as $done) { $progress($stage, $done, 100); }
            }
            return ['filename' => 'test.sql.gz', 'size' => 1234, 'original_size' => 12345];
        }
    }
    // The permission guard has its own test (BackgroundBackupPermissionTest); let this task through.
    class BackgroundDatabaseBackup {
        public const PERMISSION = 'bk_manager';
        public static function userMayBackup(int $userId): bool { return true; }
    }
}
namespace EvolutionCMS\Workers {
    // Advance a deterministic clock instead of sleeping in a worker test.
    function microtime($asFloat) { static $time = 100.0; return $time += 3.0; }
}
namespace {
    class DB {
        public static PDO $pdo;
        public static function connection() { return new class { public function getPdo() { return DB::$pdo; } }; }
    }
    function evo() { return $GLOBALS['backupProgressEvo']; }
    function snapshot($source, $target) { return rename($source, $target); }
    function niceEta($seconds) { return (string) round($seconds) . 's'; }
    function niceSize($bytes) { return (string) $bytes; }
    function __($key) { return $key; }
    require dirname(__DIR__, 2) . '/src/Workers/DatabaseBackup.php';
    $worker = new EvolutionCMS\Workers\DatabaseBackup();
    $task = new Seiger\sTask\Models\sTaskModel();
    $worker->taskMake($task);
    $last = 0;
    $intermediate = $hasEta = $dump = $compress = false;
    foreach ($worker->snapshots as $snapshot) {
        $current = $snapshot['progress'];
        if ($current < $last) { throw new RuntimeException('Progress moved backwards.'); }
        $last = $current;
        $intermediate = $intermediate || ($current > 0 && $current < 100);
        $hasEta = $hasEta || (!empty($snapshot['eta']) && $snapshot['eta'] !== '—');
        $dump = $dump || ($snapshot['message'] ?? '') === 'global.bkmgr_export_progress';
        $compress = $compress || ($snapshot['message'] ?? '') === 'global.bkmgr_compress_progress';
    }
    if (!$intermediate || !$hasEta || !$dump || !$compress || $last !== 100 || $task->progress !== 100) {
        throw new RuntimeException('Worker did not publish progress, ETA, phases and final 100%.');
    }
    echo "PASS: worker progress, ETA, phase changes, monotonic updates, persisted final 100%.\n";
    $message = end($worker->snapshots)['message'];
    if (!str_contains($message, '**global.bkmgr_backup_created:**') || !str_contains($message, '12345')
        || !preg_match('/; [0-9]+s\)\.$/', $message)) {
        throw new RuntimeException('Completion message lost its phrase, original size or elapsed duration.');
    }
    require_once dirname(__DIR__, 2) . '/src/Services/DatabaseBackupService.php';
    require dirname(__DIR__, 2) . '/src/Support/SqliteDumper.php';
    $directory = sys_get_temp_dir() . '/evo-dump-progress-' . bin2hex(random_bytes(8));
    mkdir($directory . '/assets/backup', 0700, true);
    define('EVO_BASE_PATH', $directory . '/');
    DB::$pdo = new PDO('sqlite::memory:');
    DB::$pdo->exec('CREATE TABLE sample (id INTEGER, value TEXT)');
    $insert = DB::$pdo->prepare('INSERT INTO sample VALUES (?, ?)');
    for ($i = 0; $i < 1000; $i++) { $insert->execute([$i, 'row ' . $i]); }
    $GLOBALS['backupProgressEvo'] = new class {
        public function getDatabase() { return new class {
            public function getConfig() { return ['host' => '', 'database' => ':memory:']; }
            public function getVersion() { return 'sqlite'; }
        }; }
        public function getVersionData() { return ['version' => '3.5.8']; }
        public function getConfig($key) { return 'Test'; }
        public function getPhpCompat() { return new class { public function entities($value) { return $value; } }; }
        public function toDateFormat($time) { return (string) $time; }
    };
    try {
        $dumper = new EvolutionCMS\Support\SqliteDumper(':memory:');
        $dumper->setDBtables(['sample']);
        $dumper->setSnapshotFile($directory . '/snapshot.sql');
        $updates = [];
        $legacyTemp = $directory . '/assets/backup/temp.php';
        file_put_contents($legacyTemp, 'another backup');
        $dumper->setProgressCallback(function ($processed, $total) use (&$updates, $legacyTemp) {
            $updates[] = $processed / max(1, $total);
            // Reproduce the old manager refresh deleting the shared temporary file mid-export.
            if (is_file($legacyTemp)) { unlink($legacyTemp); }
        });
        $dumper->createDump('snapshot');
        if (!in_array(0.25, $updates, true) || !in_array(0.75, $updates, true) || end($updates) !== 1.0) {
            throw new RuntimeException('Native dumper did not report intermediate row progress.');
        }
        $restored = new PDO('sqlite::memory:');
        if (!str_contains(file_get_contents($directory . '/snapshot.sql'), '-- Evolution CMS Version:3.5.8')) {
            throw new RuntimeException('Manager refresh removed the snapshot header.');
        }
        $restored->exec(file_get_contents($directory . '/snapshot.sql'));
        if ((int) $restored->query('SELECT COUNT(*) FROM sample')->fetchColumn() !== 1000) {
            throw new RuntimeException('Progress reporting changed dump contents.');
        }
        echo "PASS: native SQLite dump row progress and 1000-row restore.\n";
    } finally {
        foreach ([$directory . '/snapshot.sql', $directory . '/assets/backup/temp.php'] as $file) {
            if (is_file($file)) { unlink($file); }
        }
        rmdir($directory . '/assets/backup');
        rmdir($directory . '/assets');
        rmdir($directory);
    }
}
