<?php
namespace Seiger\sTask\Workers { abstract class BaseWorker {} }
namespace Seiger\sTask\Services { class WorkerService {} }
namespace Seiger\sTask\Models {
    class sTaskModel {
        public function getTable() { return 's_tasks'; }
        public function getConnection() { return new sWorker(); }
    }
    class sWorker {
        public static bool $migrated = true;
        public static ?self $registered = null;
        public bool $active = true;
        public string $class;
        public function getConnection() { return $this; }
        public function getSchemaBuilder() { return $this; }
        public function getTable() { return 's_workers'; }
        public function hasTable($name) { return self::$migrated; }
        public function firstOrCreate($where, $defaults) {
            if (self::$registered === null) {
                self::$registered = new self();
                self::$registered->class = $defaults['class'];
                self::$registered->active = $defaults['active'];
            }
            return self::$registered;
        }
    }
}
namespace Seiger\sTask {
    class sTask {
        public static array $arguments = [];
        public static bool $fail = false;
        public function create(...$args) {
            if (self::$fail) { throw new \RuntimeException('queue failure'); }
            self::$arguments = $args;
            return (object) ['id' => 42];
        }
    }
}
namespace {
    require dirname(__DIR__, 2) . '/src/Services/DatabaseBackupService.php';
    require dirname(__DIR__, 2) . '/src/Services/DatabaseBackupArchive.php';
    require dirname(__DIR__, 2) . '/src/Services/BackgroundDatabaseBackup.php';
    function app($class) { return new $class(); }
    function verifyQueue(bool $value): void { if (!$value) { throw new \LogicException('Queue assertion failed.'); } }
    $bridge = new \EvolutionCMS\Services\BackgroundDatabaseBackup();
    \Seiger\sTask\Models\sWorker::$migrated = false;
    verifyQueue($bridge->queue() === null);
    \Seiger\sTask\Models\sWorker::$migrated = true;
    verifyQueue($bridge->queue("Release\nbackup", 7) === 42);
    verifyQueue(\Seiger\sTask\sTask::$arguments === ['DatabaseBackup', 'make', [
        'description' => 'Release backup', 'progress_source' => 'snapshot',
    ], 'normal', 7]);
    \Seiger\sTask\Models\sWorker::$registered->active = false;
    verifyQueue($bridge->queue() === null);
    \Seiger\sTask\Models\sWorker::$registered->active = true;
    \Seiger\sTask\Models\sWorker::$registered->class = 'OtherWorker';
    try { $bridge->queue(); throw new \LogicException('Identifier collision accepted.'); }
    catch (\RuntimeException $exception) { verifyQueue(str_contains($exception->getMessage(), 'already in use')); }
    \Seiger\sTask\Models\sWorker::$registered->class = \EvolutionCMS\Workers\DatabaseBackup::class;
    \Seiger\sTask\sTask::$fail = true;
    try { $bridge->queue(); throw new \LogicException('Queue error incorrectly selected fallback.'); }
    catch (\RuntimeException $exception) { verifyQueue($exception->getMessage() === 'queue failure'); }
    echo "PASS: missing tables, worker registration, task arguments, disabled worker, identifier collision, queue error propagation.\n";
}
