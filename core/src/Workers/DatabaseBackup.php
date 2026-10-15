<?php namespace EvolutionCMS\Workers;

use EvolutionCMS\Services\DatabaseBackupArchive;
use Seiger\sTask\Models\sTaskModel;
use Seiger\sTask\Workers\BaseWorker;

// Keep core autoloadable on installations without the optional package.
if (!class_exists(BaseWorker::class)) {
    return;
}

/**
 * Run the CMS snapshot mechanism outside the manager request and compress its result.
 * @since 3.6.0
 */
class DatabaseBackup extends BaseWorker
{
    /** Stable queue identifier. @since 3.6.0 */
    public function identifier(): string { return 'DatabaseBackup'; }
    /** Group core workers together. @since 3.6.0 */
    public function scope(): string { return 'EvolutionCMS'; }
    /** Database icon for the workers list. @since 3.6.0 */
    public function icon(): string { return '<i class="fas fa-database"></i>'; }
    /** Localized manager title. @since 3.6.0 */
    public function title(): string { return __('global.bkmgr_snapshot_title'); }
    /** Localized worker description. @since 3.6.0 */
    public function description(): string { return __('global.bkmgr_snapshot_title'); }
    /** Show the standard progress widget. @since 3.6.0 */
    public function renderWidget(): string
    {
        return view('sTask::widgets.defaultWorkerWidget', ['identifier' => $this->identifier()])->render();
    }
    /**
     * Build a restorable CMS snapshot and report failures to the queue runner.
     * @since 3.6.0
     */
    public function taskMake(sTaskModel $task, array $opt = []): void
    {
        @set_time_limit(0);
        $startedAt = microtime(true);
        $lastUpdateAt = 0.0;
        $lastStage = '';
        $lastProgress = 0;
        $meta = (array) ($task->meta ?? []);
        $meta['progress_source'] = 'snapshot';
        $task->update(['progress' => 0, 'meta' => $meta]);
        $this->pushProgress($task, ['status' => 'running', 'progress' => 0, 'eta' => '—']);
        // Do not swallow exceptions: the runner must never turn a failed dump into success.
        $snapshot = (new DatabaseBackupArchive())->create((string) ($opt['description'] ?? ''),
            function (string $stage, float $processed, float $total) use ($task, $startedAt, &$lastUpdateAt, &$lastStage, &$lastProgress): void {
                $now = microtime(true);
                if ($stage === $lastStage && $now - $lastUpdateAt < 2.0 && $processed < $total) { return; }
                $fraction = $total > 0 ? min(1, max(0, $processed / $total)) : 0;
                $progress = $stage === 'dump' ? (int) floor(85 * $fraction) : 85 + (int) floor(14 * $fraction);
                $progress = max($lastProgress, min(99, $progress));
                $eta = $progress > 0 ? niceEta(($now - $startedAt) * (100 - $progress) / $progress) : '—';
                $message = __('global.' . ($stage === 'dump' ? 'bkmgr_export_progress' : 'bkmgr_compress_progress'));
                // The dump owns a consistent read transaction: publish live state without DB writes.
                $task->progress = $progress;
                $this->pushProgress($task, ['status' => 'running', 'progress' => $progress,
                    'processed' => $progress, 'total' => 100, 'eta' => $eta, 'message' => $message]);
                $lastUpdateAt = $now;
                $lastStage = $stage;
                $lastProgress = $progress;
            });
        $task->update(['progress' => 100]);
        $this->markFinished($task, $snapshot['filename'], '**' . __('global.bkmgr_backup_created') . ':** '
            . $snapshot['filename'] . ' (' . niceSize($snapshot['size']) . '; '
            . __('global.bkmgr_original_size') . ': ' . niceSize($snapshot['original_size']) . '; '
            . niceEta(microtime(true) - $startedAt) . ').');
    }
}
