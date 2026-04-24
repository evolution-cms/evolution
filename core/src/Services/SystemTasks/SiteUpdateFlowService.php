<?php namespace EvolutionCMS\Services\SystemTasks;

use EvolutionCMS\Models\SystemCliTask;
use EvolutionCMS\Services\DatabaseBackupService;
use Symfony\Component\Process\Process;

class SiteUpdateFlowService
{
    protected string $corePath;

    public function __construct(?string $corePath = null)
    {
        $this->corePath = $corePath ?: (defined('EVO_CORE_PATH') ? EVO_CORE_PATH : dirname(__DIR__, 2) . '/');
        $this->corePath = rtrim($this->corePath, '/\\') . '/';
    }

    public function execute(SystemCliTask $task, ?callable $report = null)
    {
        $snapshot = is_array($task->payload_json) ? $task->payload_json : [];
        $targetRef = $this->resolveTargetRef($task, $snapshot);
        $updateRepository = $this->resolveUpdateRepository($snapshot);
        $backupDatabase = $this->shouldCreateDatabaseBackup($snapshot);

        if ($targetRef === '') {
            throw new \RuntimeException('Site update target ref is missing.');
        }

        $reportContext = [
            'target_ref' => $targetRef,
        ];
        if ($updateRepository !== '') {
            $reportContext['update_repository'] = $updateRepository;
        }

        $this->report($report, 'preflight', 10, 'Validated site update snapshot.', 'info', $reportContext);

        $databaseBackup = null;
        if ($backupDatabase) {
            $this->report($report, 'database_backup', 20, 'Creating database backup before site update.', 'info', $reportContext);
            $databaseBackup = (new DatabaseBackupService($this->resolveBasePath()))->createSnapshot(
                'Automatic backup before Evolution CMS update to ' . $targetRef
            );
            $backupMessage = 'Database backup created: ' . $databaseBackup['filename'];
            if ($databaseBackup['version'] !== '') {
                $backupMessage .= ' (Evolution CMS ' . $databaseBackup['version'] . ')';
            }
            $this->report($report, 'database_backup', 30, $backupMessage . '.', 'info', [
                'filename' => $databaseBackup['filename'],
                'database' => $databaseBackup['database'],
                'driver' => $databaseBackup['driver'],
                'version' => $databaseBackup['version'],
                'size' => $databaseBackup['size'],
            ] + $reportContext);
        } else {
            $this->report($report, 'database_backup', 20, 'Database backup skipped by operator.', 'warning', $reportContext);
        }

        $arguments = [
            'command_site' => 'update',
            'version' => $targetRef,
        ];
        if ($updateRepository !== '') {
            $arguments['repository'] = '--repository=' . $updateRepository;
        }

        $process = new Process($this->buildArtisanProcessArguments('make:site', $arguments), $this->corePath, null, null, null);
        $process->setTimeout(null);

        $this->report($report, 'site_update', 40, 'Running Evolution CMS update to ' . $targetRef . '.', 'info', $reportContext);

        $process->run();

        $exitCode = $process->getExitCode();
        $output = trim($process->getOutput() . "\n" . $process->getErrorOutput());

        if ($output !== '') {
            $this->report($report, 'site_update', 80, $this->summarizeOutput($output), 'info', $reportContext);
        }

        if ((int) $exitCode !== 0) {
            $reason = $this->summarizeOutput($output);
            if ($reason !== '') {
                throw new \RuntimeException('Site update failed with exit code ' . (int) $exitCode . '. ' . $reason);
            }

            throw new \RuntimeException('Site update failed with exit code ' . (int) $exitCode . '.');
        }

        $this->report($report, 'finalize', 95, 'Site update flow completed.', 'info', $reportContext);

        $result = [
            'task_type' => 'site_update',
            'target_ref' => $targetRef,
        ];
        if ($updateRepository !== '') {
            $result['update_repository'] = $updateRepository;
        }
        if ($databaseBackup !== null) {
            $result['database_backup'] = $databaseBackup;
        }

        return [
            'message' => 'Site update completed successfully.',
            'result' => $result,
        ];
    }

    protected function resolveTargetRef(SystemCliTask $task, array $snapshot)
    {
        $targetRef = trim((string) ($snapshot['target_ref'] ?? $task->requested_version));

        return str_replace(["\0", "\r", "\n"], '', $targetRef);
    }

    protected function resolveUpdateRepository(array $snapshot): string
    {
        $repository = trim((string) ($snapshot['update_repository'] ?? ''));

        return str_replace(["\0", "\r", "\n"], '', $repository);
    }

    protected function shouldCreateDatabaseBackup(array $snapshot): bool
    {
        if (!array_key_exists('backup_database', $snapshot)) {
            return true;
        }

        $value = $snapshot['backup_database'];
        if (is_bool($value)) {
            return $value;
        }

        return !in_array(strtolower(trim((string) $value)), ['0', 'false', 'off', 'no'], true);
    }

    protected function resolveBasePath(): string
    {
        if (defined('EVO_BASE_PATH')) {
            return EVO_BASE_PATH;
        }

        return dirname(rtrim($this->corePath, '/\\')) . '/';
    }

    protected function buildArtisanProcessArguments($command, array $arguments)
    {
        $parts = [PHP_BINARY, $this->corePath . 'artisan', $command];

        foreach ($arguments as $value) {
            if ($value === false || $value === null || $value === '') {
                continue;
            }

            $parts[] = (string) $value;
        }

        return $parts;
    }

    protected function summarizeOutput($output)
    {
        $lines = preg_split('/\r\n|\r|\n/', trim((string) $output));
        $lines = array_values(array_filter(array_map(function ($line) {
            $line = trim((string) $line);
            $line = preg_replace('~^Evolution CMS \d+\.\d+\.\d+(?:\.\d+)?\s*~', '', $line);
            $line = strip_tags($line);

            return trim((string) $line);
        }, $lines), function ($line) {
            return $line !== '';
        }));

        if ($lines === []) {
            return '';
        }

        return implode(' ', array_slice($lines, 0, 5));
    }

    protected function report(?callable $report, $step, $progress, $message, $level = 'info', array $context = [])
    {
        if ($report !== null) {
            $report((string) $step, (int) $progress, (string) $message, (string) $level, $context);
        }
    }
}
