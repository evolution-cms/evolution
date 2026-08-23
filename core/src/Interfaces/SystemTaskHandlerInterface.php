<?php namespace EvolutionCMS\Interfaces;

use EvolutionCMS\Models\SystemCliTask;

/**
 * A unit of work `system:task-worker` knows how to run.
 *
 * The three flows the CMS ships — console install, console uninstall and site
 * update — already have this shape, so registering them is a declaration
 * rather than a rewrite. A package registers its own handler through
 * SystemTaskRegistry and inherits the queue, the lease, the progress log, the
 * cancellation flag and the worker health tracking instead of shipping a
 * second scheduler beside the CMS's own.
 */
interface SystemTaskHandlerInterface
{
    /**
     * Run one task to completion, or throw.
     *
     * The worker owns the surrounding bookkeeping: it has already claimed the
     * lease and marked the task picked, and it marks the task succeeded or
     * failed from what this returns or throws. A handler therefore never
     * writes `status` itself — it reports progress and returns.
     *
     * `$report` is `function (string $step, int $progress, string $message, string $level = 'info', array $context = []): void`.
     * Calling it renews nothing by itself, but the worker refreshes the lease
     * around it, so a long handler that reports periodically will not have its
     * task stolen while it is still working.
     *
     * @param callable|null $report null when the caller wants no progress trail
     * @return array{message?: string, result?: array} both keys optional
     *
     * @throws \Throwable any failure; the worker records it as TASK_EXECUTION_FAILED
     */
    public function execute(SystemCliTask $task, ?callable $report = null);
}
