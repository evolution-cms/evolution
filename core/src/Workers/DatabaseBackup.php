<?php namespace EvolutionCMS\Workers;

use EvolutionCMS\Services\BackgroundDatabaseBackup;
use EvolutionCMS\Services\DatabaseBackupArchive;
use Illuminate\Support\Facades\Cache;
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
     * sTask creates tasks for any manager through its own run endpoint, so require the same
     * permission as the synchronous snapshot in the Backup manager. Without a manager session the
     * caller is sTask's scheduler: the task then runs as the manager who allowed the schedule
     * (BackgroundDatabaseBackup::authorizeSchedule()), and only while that account may back up.
     * @since 3.6.0
     */
    public function createTask(string $action, ?array $options = null): sTaskModel
    {
        if ((int) evo()->getLoginUserID('mgr') > 0) {
            if (!evo()->hasPermission(BackgroundDatabaseBackup::PERMISSION, 'mgr')) {
                evo()->logEvent(0, 2, 'Database backup task refused: it needs the '
                    . BackgroundDatabaseBackup::PERMISSION . ' permission.', 'Backup manager');
                throw new \RuntimeException(__('global.error_no_privileges'));
            }
            return parent::createTask($action, $options);
        }

        $authorizedBy = (int) $this->getConfig(BackgroundDatabaseBackup::AUTHORIZED_BY, 0);
        if (BackgroundDatabaseBackup::scheduledUser($this->settings()) === 0) {
            // The scheduler asks every tick: log a refusal once, until the state changes.
            if (Cache::get(self::SCHEDULE_REFUSED) !== $authorizedBy) {
                Cache::forever(self::SCHEDULE_REFUSED, $authorizedBy);
                evo()->logEvent(0, 2, $authorizedBy > 0
                    ? 'Scheduled database backup refused: user ' . $authorizedBy . ' who allowed it no longer has the '
                        . BackgroundDatabaseBackup::PERMISSION . ' permission or is blocked.'
                    : 'Scheduled database backup refused: no manager with the ' . BackgroundDatabaseBackup::PERMISSION
                        . ' permission has saved the schedule.', 'Backup manager');
            }
            throw new \RuntimeException(__('global.error_no_privileges'));
        }
        Cache::forget(self::SCHEDULE_REFUSED);
        $task = parent::createTask($action, $options);
        $task->update(['started_by' => $authorizedBy,
            'meta' => array_merge((array) ($task->meta ?? []), [self::SCHEDULED => true])]);
        return $task;
    }

    /** Cache key remembering the last logged schedule refusal. @since 3.6.0 */
    private const SCHEDULE_REFUSED = 'database-backup:schedule-refused';

    /** Task meta flag of a task the scheduler created; it only ever narrows what may run. @since 3.6.0 */
    private const SCHEDULED = 'backup_scheduled';

    /** The worker settings as stored now, not as cached when this instance was resolved. @since 3.6.0 */
    protected function currentSettings(): array
    {
        $settings = \Seiger\sTask\Models\sWorker::query()->where('identifier', $this->identifier())->value('settings');
        $settings = is_string($settings) ? json_decode($settings, true) : $settings;
        return is_array($settings) ? $settings : [];
    }

    /**
     * Build a restorable CMS snapshot and report failures to the queue runner.
     * @since 3.6.0
     */
    public function taskMake(sTaskModel $task, array $opt = []): void
    {
        // The task runs without a session and may have been created past createTask(), so check
        // the account that started it, as it stands now.
        if (!BackgroundDatabaseBackup::userMayBackup((int) $task->started_by)) {
            evo()->logEvent(0, 2, 'Database backup task ' . (int) $task->id . ' refused: user '
                . (int) $task->started_by . ' does not have the ' . BackgroundDatabaseBackup::PERMISSION
                . ' permission.', 'Backup manager');
            throw new \RuntimeException(__('global.error_no_privileges'));
        }
        // A scheduled task queued before the schedule was revoked must not run once more.
        if (!empty($opt[self::SCHEDULED])
            && BackgroundDatabaseBackup::scheduledUser($this->currentSettings()) !== (int) $task->started_by) {
            evo()->logEvent(0, 2, 'Scheduled database backup task ' . (int) $task->id
                . ' refused: the schedule is no longer allowed by user ' . (int) $task->started_by . '.', 'Backup manager');
            throw new \RuntimeException(__('global.error_no_privileges'));
        }
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
        $meta['backup_metrics'] = $snapshot['metrics'] ?? [];
        $task->update(['progress' => 100, 'meta' => $meta]);
        $this->markFinished($task, $snapshot['filename'], '**' . __('global.bkmgr_backup_created') . ':** '
            . $snapshot['filename'] . ' (' . niceSize($snapshot['size']) . '; '
            . __('global.bkmgr_original_size') . ': ' . niceSize($snapshot['original_size']) . '; '
            . niceEta(microtime(true) - $startedAt) . ').');
    }
}
