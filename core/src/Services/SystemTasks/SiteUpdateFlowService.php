<?php namespace EvolutionCMS\Services\SystemTasks;

use EvolutionCMS\Interfaces\SystemTaskHandlerInterface;
use EvolutionCMS\Models\SystemCliTask;
use EvolutionCMS\Services\DatabaseBackupService;
use Symfony\Component\Process\Process;

class SiteUpdateFlowService implements SystemTaskHandlerInterface
{
    use ReportsProcessFailure;

    /**
     * Seconds of silence from make:site after which the task still reports itself alive.
     *
     * The heartbeat is what the manager shows as the server's last activity, and every report
     * renews the task lease: an update that ran longer than the lease without a word would
     * otherwise count as abandoned while it is still replacing the site.
     */
    public const HEARTBEAT_SECONDS = 15;

    /** Output lines of make:site copied into the task log; the rest only replaces its message. */
    public const MAX_STREAMED_LINES = 200;

    /**
     * Progress reached once make:site prints a line starting with the key, in the order it runs.
     */
    protected const MILESTONES = [
        'Start download' => 45,
        'Start unpacking' => 50,
        'Check Composer packages' => 55,
        'Checking Evolution CMS version' => 58,
        'Restore Composer lock' => 60,
        'Install Composer dependencies' => 62,
        'Rebuild optimized autoload' => 66,
        'Discover Composer packages' => 68,
        'Run Core Migrations' => 70,
        'Apply update seeders' => 74,
        'Clear compiled views' => 76,
        'Settings version set' => 77,
        'Remove Install Directory' => 78,
    ];

    protected int $heartbeatSeconds = self::HEARTBEAT_SECONDS;

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

        $process = $this->createProcess($this->buildArtisanProcessArguments('make:site', $arguments));
        $process->setTimeout(null);

        $this->report($report, 'site_update', 40, 'Running Evolution CMS update to ' . $targetRef . '.', 'info', $reportContext);

        $exitCode = $this->runStreaming($process, $report, 40, $reportContext);
        $output = trim($process->getOutput() . "\n" . $process->getErrorOutput());

        if ((int) $exitCode !== 0) {
            $this->reportProcessFailure($report, 'site_update', 80, 'make:site', $exitCode, $output, $reportContext);

            throw $this->processFailureException('Site update failed with exit code ' . (int) $exitCode . '.', $output, $this->summarizeOutput($output, true));
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

    /**
     * @param list<string> $arguments
     */
    protected function createProcess(array $arguments): Process
    {
        return new Process($arguments, $this->corePath, null, null, null);
    }

    /**
     * Run make:site and pass on what it prints while it runs.
     *
     * Its output used to reach the task only once the update had ended, so the manager showed the
     * same "running" line for minutes and could not tell a long Composer install from a dead worker.
     * Each line is now reported as it comes (milestones move the progress), and a silence longer
     * than HEARTBEAT_SECONDS reports the step again without a log line.
     *
     * @return int|null Exit code.
     */
    protected function runStreaming(Process $process, ?callable $report, int $progress, array $context): ?int
    {
        $process->start();
        // one buffer per stream: a line half written to stdout must not take in what stderr prints meanwhile
        $pending = ['out' => '', 'err' => ''];
        $streamed = 0;
        $started = time();
        $lastReport = $started;
        $lastLine = '';

        $consume = function (string $stream, string $chunk, bool $final) use (&$pending, &$streamed, &$lastReport, &$lastLine, &$progress, $report, $context) {
            $pending[$stream] .= $chunk;
            $lines = preg_split('/\r\n|\r|\n/', $pending[$stream]);
            $pending[$stream] = $final ? '' : (string) array_pop($lines);

            foreach ($lines as $line) {
                $line = $this->cleanOutputLine($line);
                if ($line === '') {
                    continue;
                }

                $milestone = $this->milestoneProgress($line);
                $progress = max($progress, $milestone ?? 0);
                $lastLine = $line;
                $lastReport = time();
                $log = $milestone !== null || $streamed < self::MAX_STREAMED_LINES;
                if ($log) {
                    $streamed++;
                }
                $this->report($report, 'site_update', $progress, $line, $log ? 'info' : null, $log ? $context : []);
            }
        };

        while ($process->isRunning()) {
            $consume('out', $process->getIncrementalOutput(), false);
            $consume('err', $process->getIncrementalErrorOutput(), false);

            if (time() - $lastReport >= $this->heartbeatSeconds) {
                $lastReport = time();
                $this->report($report, 'site_update', $progress, $this->fitMessage(
                    $lastLine !== '' ? $lastLine : 'Running Evolution CMS update.',
                    ' (' . $this->formatElapsed(time() - $started) . ')'
                ), null);
            }

            usleep(200000);
        }

        $consume('out', $process->getIncrementalOutput(), true);
        $consume('err', $process->getIncrementalErrorOutput(), true);

        return $process->getExitCode();
    }

    protected function cleanOutputLine(string $line): string
    {
        $line = (string) preg_replace('/\e\[[0-9;?]*[A-Za-z]/', '', $line);
        $line = trim(strip_tags($line));
        $line = trim((string) preg_replace('~^Evolution CMS \d+\.\d+\.\d+(?:\.\d+)?\s*~', '', $line));

        return $this->fitMessage($line);
    }

    /**
     * Shorten the text, never the suffix, so the whole message fits the task message column.
     */
    protected function fitMessage(string $text, string $suffix = ''): string
    {
        $room = max(0, SystemTaskService::MESSAGE_LENGTH - mb_strlen($suffix));

        return mb_substr($text, 0, $room) . $suffix;
    }

    protected function milestoneProgress(string $line): ?int
    {
        foreach (self::MILESTONES as $prefix => $progress) {
            if (strncmp($line, $prefix, strlen($prefix)) === 0) {
                return $progress;
            }
        }

        return null;
    }

    protected function formatElapsed(int $seconds): string
    {
        return intdiv($seconds, 60) . ':' . str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT);
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

    protected function summarizeOutput($output, bool $fromEnd = false)
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

        return implode(' ', $this->pickSummaryLines($lines, $fromEnd, 5));
    }

    /**
     * @param string|null $level Null renews the task's heartbeat and message without a log line.
     */
    protected function report(?callable $report, $step, $progress, $message, $level = 'info', array $context = [])
    {
        if ($report !== null) {
            $report((string) $step, (int) $progress, (string) $message, $level === null ? null : (string) $level, $context);
        }
    }
}
