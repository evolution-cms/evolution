<?php namespace EvolutionCMS\Services;

/**
 * Optional sTask bridge for manager snapshots; update-time snapshots stay synchronous.
 * @since 3.6.0
 */
class BackgroundDatabaseBackup
{
    /**
     * Hydrate opted-in sTask models from live snapshots without requiring an sTask package patch.
     * Runs only on retrieved task models; no database writes or changes to other workers.
     * @since 3.6.0
     */
    public static function hydrateProgress(\Illuminate\Database\Eloquent\Model $task): void
    {
        if ((int) $task->getAttribute('status') !== 50
            || ($task->getAttribute('meta')['progress_source'] ?? null) !== 'snapshot'
            || !$task->getKey() || !class_exists(\Seiger\sTask\Services\TaskProgress::class)) {
            return;
        }
        $snapshot = \Seiger\sTask\Services\TaskProgress::readProgress((int) $task->getKey());
        if (isset($snapshot['progress']) && is_numeric($snapshot['progress'])) {
            $task->setAttribute('progress', max(0, min(100, (int) $snapshot['progress'])));
            $task->syncOriginalAttribute('progress');
        }
    }
    /**
     * Queue a snapshot when sTask is installed, migrated and the worker is enabled.
     * Return null only when the optional integration is unavailable; queue errors propagate.
     * @since 3.6.0
     */
    public function queue(string $description = '', ?int $userId = null): ?int
    {
        foreach ([
                \Seiger\sTask\Workers\BaseWorker::class,
                \Seiger\sTask\Models\sWorker::class,
                \Seiger\sTask\Models\sTaskModel::class,
                \Seiger\sTask\sTask::class,
                \Seiger\sTask\Services\WorkerService::class
            ] as $class) {
            if (!class_exists($class)) {
                return null;
            }
        }
        $model = new \Seiger\sTask\Models\sWorker();
        $tasks = new \Seiger\sTask\Models\sTaskModel();
        if (!$model->getConnection()->getSchemaBuilder()->hasTable($model->getTable()) || !$tasks->getConnection()->getSchemaBuilder()->hasTable($tasks->getTable())) {
            return null;
        }
        $worker = $model->firstOrCreate(['identifier' => 'DatabaseBackup'], [
            'scope' => 'EvolutionCMS', 'class' => \EvolutionCMS\Workers\DatabaseBackup::class,
            'active' => true, 'hidden' => false, 'settings' => [], 'position' => 0,
        ]);
        if (!$worker->active) {
            return null;
        }
        if ($worker->class !== \EvolutionCMS\Workers\DatabaseBackup::class) {
            throw new \RuntimeException('DatabaseBackup worker identifier is already in use.');
        }
        $task = app(\Seiger\sTask\sTask::class)->create('DatabaseBackup', 'make', [
            'description' => DatabaseBackupArchive::headerValue($description),
            'progress_source' => 'snapshot',
        ], 'normal', $userId);
        return (int) $task->id;
    }
}
