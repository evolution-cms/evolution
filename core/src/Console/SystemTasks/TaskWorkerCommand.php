<?php namespace EvolutionCMS\Console\SystemTasks;

use EvolutionCMS\Services\SystemTasks\ConsoleInstallFlowService;
use EvolutionCMS\Services\SystemTasks\ConsoleUninstallFlowService;
use EvolutionCMS\Services\SystemTasks\SiteUpdateFlowService;
use EvolutionCMS\Services\SystemTasks\SystemTaskService;
use EvolutionCMS\Services\SystemTasks\WorkerHealthService;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;

class TaskWorkerCommand extends Command
{
    protected $signature = 'system:task-worker
                            {--once : Process at most one queued system task}';

    protected $description = 'Record system task worker activity and prepare queued task execution';

    public function handle()
    {
        $workerHealth = new WorkerHealthService();
        $taskService = new SystemTaskService();
        $host = $this->resolveHostName();
        $pid = function_exists('getmypid') ? getmypid() : null;
        $lockOwner = $host . ':' . (string) ($pid ?: '0') . ':' . uniqid('worker-', true);

        $workerHealth->markRun($host, $pid);

        $task = $taskService->acquireNextQueuedTask($lockOwner, $host, $pid);
        if (!$task) {
            $this->line('[system:task-worker] no queued tasks');
            return self::SUCCESS;
        }

        $workerHealth->markPick($host, $pid);

        try {
            switch ((string) $task->type) {
                case 'console_install':
                    $flow = new ConsoleInstallFlowService();
                    $result = $flow->execute($task, function ($step, $progress, $message, $level = 'info', array $context = []) use (&$task, $taskService) {
                        $task = $taskService->updateTaskProgress(
                            $task,
                            'running',
                            (int) $progress,
                            (string) $step,
                            (string) $message,
                            (string) $level,
                            $context
                        );
                    });

                    $taskService->markTaskSucceeded(
                        $task,
                        isset($result['message']) ? (string) $result['message'] : 'System task completed successfully.',
                        isset($result['result']) && is_array($result['result']) ? $result['result'] : []
                    );
                    $workerHealth->markSuccess($host, $pid);
                    $this->info('[system:task-worker] console install task completed');
                    return self::SUCCESS;

                case 'console_uninstall':
                    $flow = new ConsoleUninstallFlowService();
                    $result = $flow->execute($task, function ($step, $progress, $message, $level = 'info', array $context = []) use (&$task, $taskService) {
                        $task = $taskService->updateTaskProgress(
                            $task,
                            'running',
                            (int) $progress,
                            (string) $step,
                            (string) $message,
                            (string) $level,
                            $context
                        );
                    });

                    $taskService->markTaskSucceeded(
                        $task,
                        isset($result['message']) ? (string) $result['message'] : 'System task completed successfully.',
                        isset($result['result']) && is_array($result['result']) ? $result['result'] : []
                    );
                    $workerHealth->markSuccess($host, $pid);
                    $this->info('[system:task-worker] console uninstall task completed');
                    return self::SUCCESS;

                case 'site_update':
                    $flow = new SiteUpdateFlowService();
                    $result = $flow->execute($task, function ($step, $progress, $message, $level = 'info', array $context = []) use (&$task, $taskService) {
                        $task = $taskService->updateTaskProgress(
                            $task,
                            'running',
                            (int) $progress,
                            (string) $step,
                            (string) $message,
                            (string) $level,
                            $context
                        );
                    });

                    $taskService->markTaskSucceeded(
                        $task,
                        isset($result['message']) ? (string) $result['message'] : 'Site update completed successfully.',
                        isset($result['result']) && is_array($result['result']) ? $result['result'] : []
                    );
                    $workerHealth->markSuccess($host, $pid);
                    $this->info('[system:task-worker] site update task completed');
                    return self::SUCCESS;

                default:
                    $taskService->markTaskFailed(
                        $task,
                        'TASK_TYPE_NOT_ALLOWED',
                        'Unsupported system task type for this worker.'
                    );
                    $workerHealth->markFailure('TASK_TYPE_NOT_ALLOWED', $host, $pid);
                    Log::warning('[system:task-worker] unsupported task type', [
                        'task_id' => (int) $task->id,
                        'type' => (string) $task->type,
                    ]);
                    $this->warn('[system:task-worker] unsupported task type');
                    return self::SUCCESS;
            }
        } catch (\Throwable $exception) {
            $errorCode = 'TASK_EXECUTION_FAILED';
            $taskService->markTaskFailed($task, $errorCode, $exception->getMessage(), [
                'exception' => get_class($exception),
            ]);
            $workerHealth->markFailure($errorCode, $host, $pid);
            Log::error('[system:task-worker] task execution failed', [
                'task_id' => (int) $task->id,
                'task_type' => (string) $task->type,
                'target' => (string) $task->target,
                'error_code' => $errorCode,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);
            $this->error('[system:task-worker] ' . $exception->getMessage());

            return self::FAILURE;
        }
    }

    public function schedule(Schedule $schedule)
    {
        $schedule
            ->command('system:task-worker --once')
            ->everyMinute()
            ->withoutOverlapping()
            ->name('system:task-worker');
    }

    protected function resolveHostName()
    {
        $host = function_exists('gethostname') ? gethostname() : '';

        return is_string($host) && $host !== '' ? $host : php_uname('n');
    }
}
