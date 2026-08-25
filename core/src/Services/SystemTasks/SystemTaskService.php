<?php namespace EvolutionCMS\Services\SystemTasks;

use Carbon\Carbon;
use EvolutionCMS\Models\SystemCliTask;
use EvolutionCMS\Models\SystemCliTaskLog;
use EvolutionCMS\Services\Store\CatalogService;
use Illuminate\Support\Str;

class SystemTaskService
{
    public const DEFAULT_LEASE_SECONDS = 900;

    /**
     * How far down the queue one acquire attempt looks for a startable task.
     *
     * Bounded so a queue holding thousands of blocked tasks cannot turn a
     * once-a-minute worker tick into a full table scan.
     */
    public const ACQUIRE_SCAN_LIMIT = 50;

    protected CatalogService $catalogService;

    public function __construct(?CatalogService $catalogService = null)
    {
        $this->catalogService = $catalogService ?: new CatalogService();
    }

    public function createTaskFromStoreRequest($type, array $request, array $requesterSnapshot = [], $isSuperAdmin = false)
    {
        $type = trim((string) $type);
        $preflight = $this->runCreatePreflight($type, $requesterSnapshot, (bool) $isSuperAdmin);
        if (!$preflight['ok']) {
            return $preflight;
        }

        switch ($type) {
            case 'console_install':
                $response = $this->createConsoleInstallTask(
                    isset($request['catalog_item_id']) ? (string) $request['catalog_item_id'] : '',
                    isset($request['version']) ? (string) $request['version'] : '',
                    $requesterSnapshot
                );
                if (!empty($response['ok'])) {
                    $response['warnings'] = $preflight['warnings'];
                }
                return $response;

            case 'console_uninstall':
                $response = $this->createConsoleUninstallTask(
                    isset($request['catalog_item_id']) ? (string) $request['catalog_item_id'] : '',
                    isset($request['version']) ? (string) $request['version'] : '',
                    $requesterSnapshot
                );
                if (!empty($response['ok'])) {
                    $response['warnings'] = $preflight['warnings'];
                }
                return $response;

            case 'site_update':
                $response = $this->createSiteUpdateTask(
                    isset($request['target_ref']) ? (string) $request['target_ref'] : '',
                    $requesterSnapshot,
                    (bool) $isSuperAdmin,
                    isset($request['update_repository']) ? (string) $request['update_repository'] : '',
                    $this->normalizeBooleanRequest($request['backup_database'] ?? true)
                );
                if (!empty($response['ok'])) {
                    $response['warnings'] = $preflight['warnings'];
                }
                return $response;
        }

        // Types a package registered. The built-in three keep the explicit
        // cases above because each takes a differently shaped request; a
        // registered type declares one creator and receives the request whole,
        // so the store endpoint needs no knowledge of what is in it.
        $creator = SystemTaskRegistry::creator($type);

        if ($creator !== null) {
            $response = $creator($request, $requesterSnapshot, (bool) $isSuperAdmin);

            if (!is_array($response)) {
                return [
                    'ok' => false,
                    'error_code' => 'TASK_CREATE_FAILED',
                    'message' => 'The creator registered for system task type "' . $type . '" returned no result.',
                ];
            }

            if (!empty($response['ok'])) {
                $response['warnings'] = $preflight['warnings'];
            }

            return $response;
        }

        // Registered, runnable, but with no creator: the package queues it
        // through its own code and deliberately did not expose it here.
        return [
            'ok' => false,
            'error_code' => 'TASK_TYPE_NOT_ALLOWED',
            'message' => 'Unsupported system task type.',
        ];
    }

    public function createConsoleInstallTask($catalogItemId, $requestedVersion = '', array $requesterSnapshot = [])
    {
        $catalogItem = $this->resolveConsoleCatalogItem($catalogItemId);
        if (!$catalogItem) {
            return [
                'ok' => false,
                'error_code' => 'SNAPSHOT_INVALID',
                'message' => 'Console package was not found in the current catalog snapshot.',
            ];
        }

        $resolvedVersion = $this->resolveConsoleVersion($catalogItem, $requestedVersion);
        if ($resolvedVersion === '') {
            return [
                'ok' => false,
                'error_code' => 'SNAPSHOT_INVALID',
                'message' => 'Requested console package version is not allowed by the current catalog snapshot.',
            ];
        }

        $snapshot = $this->buildConsoleInstallSnapshot($catalogItem, $resolvedVersion);
        $task = $this->persistQueuedTask(
            'console_install',
            isset($catalogItem['composer_name']) ? (string) $catalogItem['composer_name'] : '',
            $resolvedVersion,
            $snapshot,
            $requesterSnapshot
        );

        $this->appendLog($task, 'info', 'queued', 'Console install task queued.', [
            'composer_name' => isset($catalogItem['composer_name']) ? (string) $catalogItem['composer_name'] : '',
            'resolved_version' => $resolvedVersion,
        ]);

        return [
            'ok' => true,
            'task' => $this->buildTaskPayloadWithLogs($task),
        ];
    }

    public function createSiteUpdateTask($targetRef = '', array $requesterSnapshot = [], $isSuperAdmin = false, $updateRepository = '', $backupDatabase = true)
    {
        if (!$isSuperAdmin) {
            return [
                'ok' => false,
                'error_code' => 'ACL_DENIED',
                'message' => 'Site update tasks are limited to super administrators.',
            ];
        }

        $targetRef = trim((string) $targetRef);
        if ($targetRef === '') {
            return [
                'ok' => false,
                'error_code' => 'SNAPSHOT_INVALID',
                'message' => 'Site update target ref is required.',
            ];
        }

        $updateRepository = $this->normalizeSiteUpdateRepository($updateRepository);
        if ($updateRepository === false) {
            return [
                'ok' => false,
                'error_code' => 'SNAPSHOT_INVALID',
                'message' => 'Site update repository must use the owner/repository format.',
            ];
        }

        $snapshot = [
            'task_type' => 'site_update',
            'catalog_source' => 'core-maintenance',
            'catalog_fetched_at' => Carbon::now()->toAtomString(),
            'target_ref' => $targetRef,
            'backup_database' => (bool) $backupDatabase,
            'capabilities' => [
                'site_update' => true,
                'database_backup' => (bool) $backupDatabase,
            ],
        ];
        if ($updateRepository !== '') {
            $snapshot['update_repository'] = $updateRepository;
        }

        $task = $this->persistQueuedTask(
            'site_update',
            'core',
            $targetRef,
            $snapshot,
            $requesterSnapshot
        );

        $this->appendLog($task, 'info', 'queued', 'Site update task queued.', [
            'target_ref' => $targetRef,
            'update_repository' => $updateRepository,
            'backup_database' => (bool) $backupDatabase,
        ]);

        return [
            'ok' => true,
            'task' => $this->buildTaskPayloadWithLogs($task),
        ];
    }

    protected function normalizeSiteUpdateRepository($repository)
    {
        $repository = trim((string) $repository, " \t\n\r\0\x0B/");
        if ($repository === '') {
            return '';
        }

        if (preg_match('~^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$~', $repository) !== 1) {
            return false;
        }

        return $repository;
    }

    protected function normalizeBooleanRequest($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return !in_array(strtolower(trim((string) $value)), ['0', 'false', 'off', 'no'], true);
    }

    public function createConsoleUninstallTask($catalogItemId, $requestedVersion = '', array $requesterSnapshot = [])
    {
        $catalogItem = $this->resolveConsoleCatalogItem($catalogItemId);
        if (!$catalogItem) {
            return [
                'ok' => false,
                'error_code' => 'SNAPSHOT_INVALID',
                'message' => 'Console package was not found in the current catalog snapshot.',
            ];
        }

        $composerName = trim((string) ($catalogItem['composer_name'] ?? ''));
        if ($composerName === '') {
            return [
                'ok' => false,
                'error_code' => 'SNAPSHOT_INVALID',
                'message' => 'Console package uninstall snapshot is incomplete.',
            ];
        }

        $resolvedVersion = trim((string) ($requestedVersion !== '' ? $requestedVersion : ($catalogItem['current_version'] ?? $catalogItem['version'] ?? '')));
        $snapshot = $this->buildConsoleUninstallSnapshot($catalogItem, $resolvedVersion);
        $task = $this->persistQueuedTask(
            'console_uninstall',
            $composerName,
            $resolvedVersion,
            $snapshot,
            $requesterSnapshot
        );

        $this->appendLog($task, 'info', 'queued', 'Console uninstall task queued.', [
            'composer_name' => $composerName,
            'resolved_version' => $resolvedVersion,
        ]);

        return [
            'ok' => true,
            'task' => $this->buildTaskPayloadWithLogs($task),
        ];
    }

    public function getTaskStatusPayload($id = 0, $uuid = '', array $requesterSnapshot = [], $isSuperAdmin = false)
    {
        $task = $this->findTask($id, $uuid);
        if (!$task) {
            return [
                'ok' => false,
                'error_code' => 'TASK_NOT_FOUND',
                'message' => 'System task was not found.',
            ];
        }

        if (!$this->canAccessTask($task, $requesterSnapshot, (bool) $isSuperAdmin)) {
            return [
                'ok' => false,
                'error_code' => 'ACL_DENIED',
                'message' => 'You do not have access to this system task.',
            ];
        }

        return [
            'ok' => true,
            'task' => $this->buildTaskStatusPayload($task),
        ];
    }

    public function getTaskResultPayload($id = 0, $uuid = '', array $requesterSnapshot = [], $isSuperAdmin = false)
    {
        $task = $this->findTask($id, $uuid);
        if (!$task) {
            return [
                'ok' => false,
                'error_code' => 'TASK_NOT_FOUND',
                'message' => 'System task was not found.',
            ];
        }

        if (!$this->canAccessTask($task, $requesterSnapshot, (bool) $isSuperAdmin)) {
            return [
                'ok' => false,
                'error_code' => 'ACL_DENIED',
                'message' => 'You do not have access to this system task.',
            ];
        }

        $schedulerHealthService = new SchedulerHealthService();
        $workerHealthService = new WorkerHealthService();

        return [
            'ok' => true,
            'task' => array_merge(
                $this->buildTaskStatusPayload($task),
                [
                    'result' => is_array($task->result_json) ? $task->result_json : [],
                    'logs' => $this->getRecentLogs($task),
                    'scheduler_health' => $schedulerHealthService->getStatusPayload(),
                    'worker_health' => $workerHealthService->getStatusPayload($schedulerHealthService),
                ]
            ),
        ];
    }

    public function cancelQueuedTaskPayload($id = 0, $uuid = '', array $requesterSnapshot = [], $isSuperAdmin = false)
    {
        $task = $this->findTask($id, $uuid);
        if (!$task) {
            return [
                'ok' => false,
                'error_code' => 'TASK_NOT_FOUND',
                'message' => 'System task was not found.',
            ];
        }

        if (!$this->canAccessTask($task, $requesterSnapshot, (bool) $isSuperAdmin)) {
            return [
                'ok' => false,
                'error_code' => 'ACL_DENIED',
                'message' => 'You do not have access to this system task.',
            ];
        }

        if ((string) $task->status !== 'queued') {
            return [
                'ok' => false,
                'error_code' => 'TASK_NOT_CANCELLABLE',
                'message' => 'Only queued tasks can be cancelled safely.',
                'task' => $this->buildTaskPayloadWithLogs($task),
            ];
        }

        $now = Carbon::now();
        $task->fill([
            'status' => 'failed',
            'step' => 'cancelled',
            'message' => 'Queued task was cancelled.',
            'error_code' => 'TASK_CANCELLED',
            'heartbeat_at' => $now,
            'lease_expires_at' => null,
            'locked_by' => '',
            'cancellation_requested_at' => $now,
            'finished_at' => $now,
            'updated_at' => $now,
        ]);
        $task->save();
        $task = $task->fresh();

        $this->appendLog($task, 'warning', 'cancelled', 'Queued task was cancelled by operator.', []);

        return [
            'ok' => true,
            'task' => $this->buildTaskPayloadWithLogs($task),
        ];
    }

    /**
     * Claim the next task this worker is allowed to start.
     *
     * Oldest first, but a candidate is skipped rather than the whole scan
     * abandoned when its type may not start yet: an exclusive task waiting for
     * a running one must not stop a concurrent task behind it from being
     * picked, or one long install would idle the worker for its whole duration.
     *
     * The claim itself is an optimistic `UPDATE … WHERE status = 'queued'` and
     * is the only thing that makes two workers safe on one queue — the
     * concurrency check above it is advisory and can race, which is why the
     * scan continues when the update touches no rows.
     */
    public function acquireNextQueuedTask($lockOwner, $host = '', $pid = null, $leaseSeconds = self::DEFAULT_LEASE_SECONDS)
    {
        $candidates = SystemCliTask::query()
            ->where('status', 'queued')
            ->orderBy('id')
            ->limit(self::ACQUIRE_SCAN_LIMIT)
            ->get();

        foreach ($candidates as $candidate) {
            if (!$this->canStartTaskNow($candidate)) {
                continue;
            }

            $now = Carbon::now();
            $updated = SystemCliTask::query()
                ->where('id', $candidate->id)
                ->where('status', 'queued')
                ->update([
                    'status' => 'picked',
                    'step' => 'picked',
                    'progress' => 5,
                    'message' => 'Picked by worker',
                    'locked_by' => trim((string) $lockOwner),
                    'attempt_count' => ((int) $candidate->attempt_count) + 1,
                    'lease_expires_at' => $now->copy()->addSeconds((int) $leaseSeconds),
                    'worker_host' => trim((string) $host),
                    'worker_pid' => $pid,
                    'started_at' => $candidate->started_at ?: $now,
                    'heartbeat_at' => $now,
                    'updated_at' => $now,
                ]);

            if ((int) $updated !== 1) {
                // Another worker won the race for this row. The next candidate
                // may still be ours.
                continue;
            }

            $task = SystemCliTask::query()->find($candidate->id);
            if ($task) {
                $this->appendLog($task, 'info', 'picked', 'Task picked by worker.', [
                    'lock_owner' => trim((string) $lockOwner),
                ]);
            }

            return $task;
        }

        return null;
    }

    /**
     * May a task of this type start while the queue looks like this?
     *
     * An unregistered type answers true on purpose. It cannot run, and letting
     * the worker pick it is what turns it into a task marked failed with
     * TASK_TYPE_NOT_ALLOWED — refusing to pick it here would instead leave it
     * queued forever, in front of everything behind it.
     */
    protected function canStartTaskNow(SystemCliTask $task): bool
    {
        $type = (string) $task->type;

        if (!SystemTaskRegistry::has($type)) {
            return true;
        }

        if (SystemTaskRegistry::isExclusive($type)) {
            return $this->countActiveTasks(SystemTaskRegistry::exclusiveTypes()) === 0;
        }

        return $this->countActiveTasks([$type]) < SystemTaskRegistry::parallelism($type);
    }

    /**
     * How many tasks of these types a worker is currently holding.
     *
     * Counts claimed work only — `picked` and `running` — and only while the
     * lease is live, so an abandoned task stops occupying a slot once its
     * lease lapses instead of shrinking the pipe permanently.
     *
     * @param string[] $types
     */
    protected function countActiveTasks(array $types): int
    {
        if ($types === []) {
            return 0;
        }

        $now = Carbon::now();

        return (int) SystemCliTask::query()
            ->whereIn('status', ['picked', 'running'])
            ->whereIn('type', $types)
            ->where(function ($query) use ($now) {
                $query->whereNull('lease_expires_at')
                    ->orWhere('lease_expires_at', '>=', $now);
            })
            ->count();
    }

    public function updateTaskProgress(SystemCliTask $task, $status, $progress, $step, $message, $level = null, array $context = [])
    {
        $now = Carbon::now();
        $task->fill([
            'status' => trim((string) $status),
            'progress' => max(0, min(100, (int) $progress)),
            'step' => trim((string) $step),
            'message' => trim((string) $message),
            'heartbeat_at' => $now,
            'lease_expires_at' => $now->copy()->addSeconds(self::DEFAULT_LEASE_SECONDS),
            'updated_at' => $now,
        ]);
        $task->save();
        $task = $task->fresh();

        if ($level !== null && trim((string) $message) !== '') {
            $this->appendLog($task, trim((string) $level) ?: 'info', trim((string) $step), trim((string) $message), $context);
        }

        return $task;
    }

    public function markTaskSucceeded(SystemCliTask $task, $message, array $result = [])
    {
        $now = Carbon::now();
        $task->fill([
            'status' => 'succeeded',
            'step' => 'completed',
            'progress' => 100,
            'message' => trim((string) $message),
            'result_json' => $result,
            'error_code' => '',
            'heartbeat_at' => $now,
            'lease_expires_at' => null,
            'locked_by' => '',
            'finished_at' => $now,
            'updated_at' => $now,
        ]);
        $task->save();
        $task = $task->fresh();

        $this->appendLog($task, 'info', 'completed', trim((string) $message), [
            'result' => $result,
        ]);

        return $task;
    }

    public function markTaskFailed(SystemCliTask $task, $errorCode, $message, array $result = [])
    {
        $now = Carbon::now();
        $task->fill([
            'status' => 'failed',
            'step' => 'failed',
            'message' => trim((string) $message),
            'error_code' => trim((string) $errorCode),
            'result_json' => $result,
            'heartbeat_at' => $now,
            'lease_expires_at' => null,
            'locked_by' => '',
            'finished_at' => $now,
            'updated_at' => $now,
        ]);
        $task->save();
        $task = $task->fresh();

        $this->appendLog($task, 'error', 'failed', trim((string) $message), [
            'error_code' => trim((string) $errorCode),
            'result' => $result,
        ]);
        $this->writeSystemEventLog($task, 3, trim((string) $message), trim((string) $errorCode), $result);

        return $task;
    }

    public function appendLog(SystemCliTask $task, $level, $step, $message, array $context = [])
    {
        $nextSeq = (int) SystemCliTaskLog::query()
            ->where('task_id', $task->id)
            ->max('seq') + 1;

        return SystemCliTaskLog::query()->create([
            'task_id' => $task->id,
            'seq' => $nextSeq,
            'level' => trim((string) $level) ?: 'info',
            'step' => trim((string) $step),
            'message' => trim((string) $message),
            'context_json' => $context,
            'created_at' => Carbon::now(),
        ]);
    }

    protected function persistQueuedTask($type, $target, $requestedVersion, array $snapshot, array $requesterSnapshot = [])
    {
        $now = Carbon::now();
        $task = SystemCliTask::query()->create([
            'uuid' => (string) Str::uuid(),
            'type' => trim((string) $type),
            'target' => trim((string) $target),
            'requested_version' => trim((string) $requestedVersion),
            'status' => 'queued',
            'step' => 'queued',
            'progress' => 10,
            'message' => 'Queued',
            'payload_json' => $snapshot,
            'result_json' => [],
            'created_by' => isset($requesterSnapshot['user_id']) ? (int) $requesterSnapshot['user_id'] : null,
            'locked_by' => '',
            'attempt_count' => 0,
            'worker_host' => '',
            'worker_pid' => null,
            'error_code' => '',
            'catalog_snapshot_hash' => hash('sha256', json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            'requested_by_snapshot' => $requesterSnapshot,
            'started_at' => null,
            'heartbeat_at' => null,
            'cancellation_requested_at' => null,
            'finished_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $task->fresh();
    }

    protected function resolveConsoleCatalogItem($catalogItemId)
    {
        $catalogItemId = trim((string) $catalogItemId);
        if ($catalogItemId === '') {
            return null;
        }

        foreach ($this->catalogService->getConsoleCatalog() as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (isset($item['id']) && (string) $item['id'] === $catalogItemId) {
                return $item;
            }
        }

        return null;
    }

    protected function resolveConsoleVersion(array $catalogItem, $requestedVersion)
    {
        $requestedVersion = trim((string) $requestedVersion);
        $options = $catalogItem['url']['fieldValue'] ?? [];
        if (!is_array($options)) {
            $options = [];
        }

        $allowedVersions = [];
        foreach ($options as $option) {
            if (!is_array($option) || !isset($option['file'])) {
                continue;
            }

            $allowedVersions[] = trim((string) $option['file']);
        }

        if ($requestedVersion !== '' && in_array($requestedVersion, $allowedVersions, true)) {
            return $requestedVersion;
        }

        $fallback = isset($catalogItem['version']) ? trim((string) $catalogItem['version']) : '';
        if ($fallback !== '' && in_array($fallback, $allowedVersions, true)) {
            return $fallback;
        }

        return $allowedVersions[0] ?? '';
    }

    protected function buildConsoleInstallSnapshot(array $catalogItem, $resolvedVersion)
    {
        $defaultBranch = isset($catalogItem['readme_branch']) ? (string) $catalogItem['readme_branch'] : '';
        $composerVersion = $this->normalizeConsoleComposerVersion((string) $resolvedVersion, $defaultBranch);

        return [
            'task_type' => 'console_install',
            'catalog_source' => 'https://evo.im/extras.json',
            'catalog_fetched_at' => Carbon::now()->toAtomString(),
            'package_type' => isset($catalogItem['install_method']) ? (string) $catalogItem['install_method'] : 'console-extra',
            'package_name' => isset($catalogItem['name']) ? (string) $catalogItem['name'] : '',
            'display_title' => isset($catalogItem['title']) ? (string) $catalogItem['title'] : (isset($catalogItem['name']) ? (string) $catalogItem['name'] : ''),
            'composer_name' => isset($catalogItem['composer_name']) ? (string) $catalogItem['composer_name'] : '',
            'resolved_version' => trim((string) $resolvedVersion),
            'composer_version' => $composerVersion,
            'repo_full_name' => isset($catalogItem['repo_full_name']) ? (string) $catalogItem['repo_full_name'] : '',
            'source_url' => isset($catalogItem['source_url']) ? (string) $catalogItem['source_url'] : '',
            'readme_branch' => $defaultBranch,
            'source_kind' => 'console',
            'source_label' => '',
            'capabilities' => [
                'discover' => true,
                'publish' => true,
                'migrate' => true,
            ],
        ];
    }

    protected function buildConsoleUninstallSnapshot(array $catalogItem, $resolvedVersion)
    {
        $composerName = isset($catalogItem['composer_name']) ? (string) $catalogItem['composer_name'] : '';

        return [
            'task_type' => 'console_uninstall',
            'catalog_source' => 'https://evo.im/extras.json',
            'catalog_fetched_at' => Carbon::now()->toAtomString(),
            'package_type' => isset($catalogItem['install_method']) ? (string) $catalogItem['install_method'] : 'console-extra',
            'package_name' => isset($catalogItem['name']) ? (string) $catalogItem['name'] : '',
            'display_title' => isset($catalogItem['title']) ? (string) $catalogItem['title'] : (isset($catalogItem['name']) ? (string) $catalogItem['name'] : ''),
            'composer_name' => $composerName,
            'resolved_version' => trim((string) $resolvedVersion),
            'repo_full_name' => isset($catalogItem['repo_full_name']) ? (string) $catalogItem['repo_full_name'] : '',
            'source_url' => isset($catalogItem['source_url']) ? (string) $catalogItem['source_url'] : '',
            'source_kind' => 'console',
            'source_label' => '',
            'cleanup_files' => $this->resolveConsoleUninstallCleanupFiles($composerName),
            'capabilities' => [
                'composer_remove_only' => true,
                'artifact_cleanup' => false,
            ],
        ];
    }

    protected function buildTaskStatusPayload(SystemCliTask $task)
    {
        $payload = is_array($task->payload_json) ? $task->payload_json : [];
        $displayTitle = trim((string) ($payload['display_title'] ?? $payload['package_name'] ?? ''));

        return [
            'id' => (int) $task->id,
            'uuid' => (string) $task->uuid,
            'type' => (string) $task->type,
            'target' => (string) $task->target,
            'display_title' => $displayTitle,
            'source_kind' => trim((string) ($payload['source_kind'] ?? '')),
            'source_label' => trim((string) ($payload['source_label'] ?? '')),
            'requested_version' => (string) $task->requested_version,
            'status' => (string) $task->status,
            'step' => (string) $task->step,
            'progress' => (int) $task->progress,
            'message' => (string) $task->message,
            'error_code' => (string) $task->error_code,
            'created_at' => $task->created_at ? $task->created_at->toAtomString() : null,
            'started_at' => $task->started_at ? $task->started_at->toAtomString() : null,
            'heartbeat_at' => $task->heartbeat_at ? $task->heartbeat_at->toAtomString() : null,
            'finished_at' => $task->finished_at ? $task->finished_at->toAtomString() : null,
            'can_retry' => false,
            'can_refresh_state' => in_array((string) $task->status, ['finished', 'succeeded'], true),
        ];
    }

    protected function buildTaskPayloadWithLogs(SystemCliTask $task)
    {
        return array_merge(
            $this->buildTaskStatusPayload($task),
            [
                'logs' => $this->getRecentLogs($task),
            ]
        );
    }

    protected function getRecentLogs(SystemCliTask $task)
    {
        return SystemCliTaskLog::query()
            ->where('task_id', $task->id)
            ->orderBy('seq')
            ->get(['seq', 'level', 'step', 'message', 'created_at'])
            ->map(static function (SystemCliTaskLog $log) {
                return [
                    'seq' => (int) $log->seq,
                    'level' => (string) $log->level,
                    'step' => (string) $log->step,
                    'message' => (string) $log->message,
                    'created_at' => $log->created_at ? $log->created_at->toAtomString() : null,
                ];
            })
            ->values()
            ->all();
    }

    protected function writeSystemEventLog(SystemCliTask $task, $type, $message, $errorCode = '', array $result = [])
    {
        try {
            if (!function_exists('evo')) {
                return;
            }

            $core = evo();
            if (!$core || !method_exists($core, 'logEvent')) {
                return;
            }

            $details = [
                'System task failed.',
                'Type: ' . (string) $task->type,
                'Target: ' . (string) $task->target,
                'Task ID: ' . (int) $task->id,
                'Step: ' . (string) $task->step,
            ];

            if (trim((string) $errorCode) !== '') {
                $details[] = 'Error code: ' . trim((string) $errorCode);
            }

            if (trim((string) $message) !== '') {
                $details[] = 'Message: ' . trim((string) $message);
            }

            if (!empty($result)) {
                $details[] = 'Context: ' . json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            $core->logEvent(
                0,
                (int) $type,
                implode("\n", $details),
                'Store System Tasks'
            );
        } catch (\Throwable $exception) {
            // Event log writes must never break task state updates.
        }
    }

    protected function findTask($id = 0, $uuid = '')
    {
        $id = (int) $id;
        $uuid = trim((string) $uuid);

        if ($id > 0) {
            return SystemCliTask::query()->find($id);
        }

        if ($uuid !== '') {
            return SystemCliTask::query()->where('uuid', $uuid)->first();
        }

        return null;
    }

    protected function normalizeConsoleComposerVersion($resolvedVersion, $defaultBranch = '')
    {
        $resolvedVersion = trim((string) $resolvedVersion);
        $defaultBranch = trim((string) $defaultBranch);

        if ($resolvedVersion === '') {
            return '';
        }

        if (str_starts_with($resolvedVersion, 'dev-')) {
            return $resolvedVersion;
        }

        if ($defaultBranch !== '' && $resolvedVersion === $defaultBranch) {
            return 'dev-' . $resolvedVersion;
        }

        return $resolvedVersion;
    }

    protected function resolveConsoleUninstallCleanupFiles($composerName)
    {
        $composerName = trim((string) $composerName);
        if ($composerName === '') {
            return [];
        }

        $packageComposer = $this->loadInstalledPackageComposer($composerName);
        if (!is_array($packageComposer)) {
            return [];
        }

        $corePath = defined('EVO_CORE_PATH')
            ? EVO_CORE_PATH
            : dirname(__DIR__, 2) . '/';

        $providersDir = $corePath . 'custom/config/app/providers/';
        $aliasesDir = $corePath . 'custom/config/app/aliases/';
        $cleanupFiles = [];

        $providers = $packageComposer['extra']['laravel']['providers'] ?? [];
        $priorities = $packageComposer['extra']['laravel']['priority'] ?? [];
        if (is_array($providers)) {
            foreach ($providers as $provider) {
                if (!is_string($provider) || trim($provider) === '') {
                    continue;
                }

                $className = basename(str_replace('\\', '/', trim($provider)));
                $cleanupFiles[] = $providersDir . $className . '.php';

                if (isset($priorities[$provider]) && (int) $priorities[$provider] > 0 && (int) $priorities[$provider] < 1000) {
                    $cleanupFiles[] = $providersDir . str_pad((string) ((int) $priorities[$provider]), 3, '0', STR_PAD_LEFT) . '_' . $className . '.php';
                }
            }
        }

        $aliases = $packageComposer['extra']['laravel']['aliases'] ?? [];
        if (is_array($aliases)) {
            foreach (array_keys($aliases) as $alias) {
                if (!is_string($alias) || trim($alias) === '') {
                    continue;
                }

                $fileName = preg_replace('~[^A-Za-z0-9_]+~', '', trim($alias)) . '.php';
                if ($fileName !== '.php') {
                    $cleanupFiles[] = $aliasesDir . $fileName;
                }
            }
        }

        return array_values(array_unique(array_filter($cleanupFiles, static function ($path) {
            return is_string($path) && trim($path) !== '';
        })));
    }

    protected function loadInstalledPackageComposer($composerName)
    {
        if (!class_exists('\\Composer\\InstalledVersions')) {
            return null;
        }

        try {
            $installPath = \Composer\InstalledVersions::getInstallPath($composerName);
        } catch (\Throwable $exception) {
            return null;
        }

        if (!is_string($installPath) || trim($installPath) === '') {
            return null;
        }

        $composerPath = rtrim($installPath, '/\\') . '/composer.json';
        if (!file_exists($composerPath)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($composerPath), true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function runCreatePreflight($type, array $requesterSnapshot = [], $isSuperAdmin = false)
    {
        // An unknown type is refused before anything else is disclosed. It has
        // no handler, so it could never run; answering it with a scheduler
        // health verdict would only tell an unauthorised prober how the
        // installation is doing.
        if (!SystemTaskRegistry::has($type)) {
            return [
                'ok' => false,
                'error_code' => 'TASK_TYPE_NOT_ALLOWED',
                'message' => 'Unsupported system task type.',
            ];
        }

        if (empty($requesterSnapshot['permissions']['exec_module'])) {
            return [
                'ok' => false,
                'error_code' => 'ACL_DENIED',
                'message' => 'System task creation requires module execution permission.',
            ];
        }

        // Which extra permissions a type needs is the type's own declaration,
        // so a package gates its work with its own permission key instead of
        // borrowing one of the CMS's.
        foreach (SystemTaskRegistry::permissions($type) as $permission) {
            if (empty($requesterSnapshot['permissions'][$permission])) {
                return [
                    'ok' => false,
                    'error_code' => 'ACL_DENIED',
                    'message' => 'Queueing a "' . SystemTaskRegistry::label($type)
                        . '" task requires the ' . $permission . ' permission.',
                ];
            }
        }

        if (SystemTaskRegistry::requiresSuperAdmin($type) && !$isSuperAdmin) {
            return [
                'ok' => false,
                'error_code' => 'ACL_DENIED',
                'message' => '"' . SystemTaskRegistry::label($type) . '" tasks are limited to super administrators.',
            ];
        }

        // The global lock covers exclusive types only, and is asked about only
        // when the new task is itself exclusive. Before the registry every type
        // was exclusive, so for the three built-in flows this is the behaviour
        // it always had; what it stops doing is letting a package's long batch
        // hold the updater hostage for the length of the batch.
        $activeTask = SystemTaskRegistry::isExclusive($type) ? $this->findActiveMutatingTask() : null;
        if ($activeTask) {
            $activeTaskPayload = $this->buildTaskStatusPayload($activeTask);
            $activeTaskPayload['can_cancel_queued'] = ((string) $activeTask->status === 'queued');
            return [
                'ok' => false,
                'error_code' => 'GLOBAL_LOCK_ACTIVE',
                'message' => 'Another system task is already queued or running.',
                'active_task' => $activeTaskPayload,
            ];
        }

        $schedulerHealthService = new SchedulerHealthService();
        $schedulerStatus = $schedulerHealthService->getStatusPayload();
        $workerStatus = (new WorkerHealthService())->getStatusPayload($schedulerHealthService);
        $warnings = [];

        if (($schedulerStatus['status'] ?? 'unhealthy') === 'unhealthy') {
            return [
                'ok' => false,
                'error_code' => 'SCHEDULER_UNHEALTHY',
                'message' => 'Scheduler health is unhealthy. Start the scheduler before queueing system tasks.',
            ];
        }

        // A type reserved for super administrators is one that rewrites the
        // installation, and half-healthy infrastructure is a bad place to start
        // one: a lease that expires mid-update leaves a partly-updated site.
        // Everything else is allowed to start and merely warned about.
        if (SystemTaskRegistry::requiresSuperAdmin($type)) {
            if (($schedulerStatus['status'] ?? 'unhealthy') === 'degraded') {
                return [
                    'ok' => false,
                    'error_code' => 'SITE_UPDATE_BLOCKED',
                    'message' => SystemTaskRegistry::label($type) . ' tasks are blocked while scheduler health is degraded.',
                ];
            }

            if (($workerStatus['status'] ?? 'unknown') === 'unhealthy') {
                return [
                    'ok' => false,
                    'error_code' => 'SITE_UPDATE_BLOCKED',
                    'message' => SystemTaskRegistry::label($type) . ' tasks are blocked while worker health is unhealthy.',
                ];
            }
        } else {
            if (($schedulerStatus['status'] ?? '') === 'degraded') {
                $warnings[] = [
                    'code' => 'SCHEDULER_DEGRADED',
                    'message' => 'Scheduler health is degraded. The queued task may start late.',
                ];
            }

            if (in_array((string) ($workerStatus['status'] ?? 'unknown'), ['degraded', 'unhealthy', 'unknown'], true)) {
                $warnings[] = [
                    'code' => 'WORKER_STATUS_WARNING',
                    'message' => 'Worker health is not fully healthy. Monitor the queued install until it starts.',
                ];
            }
        }

        return [
            'ok' => true,
            'warnings' => $warnings,
        ];
    }

    /**
     * The exclusive task currently holding the queue, if any.
     *
     * Only exclusive types count. A concurrent type is ordinary background
     * work — a package's image batch may run for a day, and letting that block
     * a site update for a day would make the shared queue unusable for exactly
     * the work it was opened up for.
     */
    protected function findActiveMutatingTask()
    {
        $exclusiveTypes = SystemTaskRegistry::exclusiveTypes();

        if ($exclusiveTypes === []) {
            return null;
        }

        $now = Carbon::now();

        return SystemCliTask::query()
            ->whereIn('status', ['queued', 'picked', 'running'])
            ->whereIn('type', $exclusiveTypes)
            ->where(function ($query) use ($now) {
                // A queued task holds the lock unconditionally — it has no
                // lease yet. A picked or running one holds it only while its
                // lease is live: a worker killed mid-task would otherwise hold
                // the global lock forever, and every later task would be
                // refused with GLOBAL_LOCK_ACTIVE until somebody edited the row
                // by hand. A null lease is treated as live, because that is the
                // shape a task takes between being claimed and being stamped.
                $query->where('status', 'queued')
                    ->orWhereNull('lease_expires_at')
                    ->orWhere('lease_expires_at', '>=', $now);
            })
            ->orderBy('id')
            ->first();
    }

    protected function canAccessTask(SystemCliTask $task, array $requesterSnapshot = [], $isSuperAdmin = false)
    {
        if ($isSuperAdmin) {
            return true;
        }

        if (empty($requesterSnapshot['permissions']['exec_module'])) {
            return false;
        }

        if (empty($requesterSnapshot['permissions']['system_tasks.view'])) {
            return false;
        }

        $requesterId = isset($requesterSnapshot['user_id']) ? (int) $requesterSnapshot['user_id'] : 0;
        $createdBy = (int) $task->created_by;
        if ($requesterId > 0 && $createdBy > 0) {
            return $requesterId === $createdBy;
        }

        $requesterSessionHash = trim((string) ($requesterSnapshot['session_hash'] ?? ''));
        $taskSnapshot = is_array($task->requested_by_snapshot) ? $task->requested_by_snapshot : [];
        $taskSessionHash = trim((string) ($taskSnapshot['session_hash'] ?? ''));

        return $requesterSessionHash !== '' && $requesterSessionHash === $taskSessionHash;
    }
}
