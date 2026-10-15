<?php

/*
 * Symbols of the optional seiger/stask package, for static analysis only (phpstan.neon scanFiles).
 * Core integrates with sTask when it is installed (EvolutionCMS\Workers\DatabaseBackup,
 * EvolutionCMS\Services\BackgroundDatabaseBackup) but does not require it. Never loaded at runtime.
 */

namespace Seiger\sTask\Models {
    class sTaskModel extends \Illuminate\Database\Eloquent\Model {}
    class sWorker extends \Illuminate\Database\Eloquent\Model {}
}

namespace Seiger\sTask\Services {
    class WorkerService {}
    class TaskProgress
    {
        public static function readProgress(int $id): ?array { return null; }
    }
}

namespace Seiger\sTask\Workers {
    abstract class BaseWorker
    {
        public function settings(): array { return []; }
        public function getConfig(string $key, $default = null) { return $default; }
        public function createTask(string $action, ?array $options = null): \Seiger\sTask\Models\sTaskModel { return new \Seiger\sTask\Models\sTaskModel(); }
        protected function pushProgress(\Seiger\sTask\Models\sTaskModel $task, array $delta = []): void {}
        protected function markFinished(\Seiger\sTask\Models\sTaskModel $task, ?string $result = null, ?string $message = null): void {}
    }
}

namespace Seiger\sTask {
    class sTask
    {
        public function create(string $identifier, string $action, array $data = [], string $priority = 'normal', ?int $userId = null): Models\sTaskModel { return new Models\sTaskModel(); }
    }
}
