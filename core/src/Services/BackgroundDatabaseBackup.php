<?php namespace EvolutionCMS\Services;

/**
 * Optional sTask bridge for manager snapshots; update-time snapshots stay synchronous.
 * @since 3.6.0
 */
class BackgroundDatabaseBackup
{
    /** The permission a manager needs to take a snapshot, queued or not. @since 3.6.0 */
    public const PERMISSION = 'bk_manager';

    /**
     * May this manager account take a snapshot: it exists, is not blocked, and its role holds
     * bk_manager. sTask lets any manager create and run a task of any active worker, and a queued
     * task runs without a session, so the worker asks this for the account that started the task.
     * @since 3.6.0
     */
    public static function userMayBackup(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        $row = \EvolutionCMS\Models\User::query()
            ->join('user_attributes', 'user_attributes.internalKey', '=', 'users.id')
            ->where('users.id', $userId)
            ->first(['user_attributes.role', 'user_attributes.blocked', 'user_attributes.blockeduntil', 'user_attributes.blockedafter']);
        if ($row === null || (int) $row->role <= 0 || (int) $row->blocked !== 0
            || (int) $row->blockeduntil > time()
            || ((int) $row->blockedafter > 0 && (int) $row->blockedafter < time())) {
            return false;
        }
        return \EvolutionCMS\Models\RolePermissions::query()
            ->where('role_id', (int) $row->role)
            ->where('permission', self::PERMISSION)
            ->exists();
    }

    /** Worker setting holding the manager who allowed scheduled snapshots, 0 when nobody did. @since 3.6.0 */
    public const AUTHORIZED_BY = 'backup_authorized_by';

    /**
     * sTask stores the schedule without asking for bk_manager or recording who set it, and runs it
     * without a session. So whenever a manager saves the DatabaseBackup worker, record that manager
     * as the one who allows the schedule - or nobody, when the schedule is off or the manager lacks
     * bk_manager. A value posted with the settings never counts. Saves without a manager session
     * (discovery, CLI) keep the stored value.
     * @since 3.6.0
     */
    public static function authorizeSchedule(\Illuminate\Database\Eloquent\Model $worker): void
    {
        if ($worker->getAttribute('identifier') !== 'DatabaseBackup') {
            return;
        }
        $settings = $worker->getAttribute('settings');
        $settings = is_array($settings) ? $settings : [];
        $original = $worker->exists ? $worker->getOriginal('settings') : [];
        $previous = (int) (is_array($original) ? ($original[self::AUTHORIZED_BY] ?? 0) : 0);
        $userId = (int) evo()->getLoginUserID('mgr');
        if ($userId <= 0) {
            $authorizedBy = $previous;
        } elseif (!empty($settings['schedule']['enabled']) && evo()->hasPermission(self::PERMISSION, 'mgr')) {
            $authorizedBy = $userId;
        } else {
            $authorizedBy = 0;
        }
        $settings[self::AUTHORIZED_BY] = $authorizedBy;
        $worker->setAttribute('settings', $settings);
        if ($authorizedBy !== $previous) {
            evo()->logEvent(0, 1, $authorizedBy > 0
                ? 'Scheduled database backups are now allowed by user ' . $authorizedBy . '.'
                : 'Scheduled database backups are no longer allowed: the worker was saved by user ' . $userId
                    . (empty($settings['schedule']['enabled']) ? ' with the schedule off.' : ' without the ' . self::PERMISSION . ' permission.'),
                'Backup manager');
        }
    }

    /**
     * The manager a scheduled snapshot runs as: the one recorded by authorizeSchedule(), and only
     * while that account still may take a snapshot. 0 refuses the run.
     * @since 3.6.0
     */
    public static function scheduledUser(array $settings): int
    {
        $userId = (int) ($settings[self::AUTHORIZED_BY] ?? 0);
        return self::userMayBackup($userId) ? $userId : 0;
    }

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
