<?php namespace Tests\Unit\SystemTasks;

use EvolutionCMS\Services\SystemTasks\ConsoleUninstall\ConsoleUninstallHandlerInterface;
use EvolutionCMS\Services\SystemTasks\ConsoleUninstall\ConsoleUninstallPlan;
use EvolutionCMS\Services\SystemTasks\ConsoleUninstall\ConsoleUninstallResult;

/**
 * Test-only uninstall handler used by the console uninstall registry specs.
 *
 * The class intentionally lives in its own PSR-4 matching file so Composer can
 * build optimized development autoloads without reporting a namespace/path
 * mismatch during package installs and updates.
 */
class TestConsoleUninstallHandler implements ConsoleUninstallHandlerInterface
{
    /**
     * Build a deterministic uninstall preview for registry delegation tests.
     *
     * The returned plan mirrors a small package uninstall so assertions can
     * verify that the registry delegates to registered handlers without
     * touching the filesystem or database.
     *
     * @param array<string, mixed> $context
     * @return ConsoleUninstallPlan Preview plan used by the test registry.
     */
    public function preview(array $context = []): ConsoleUninstallPlan
    {
        return new ConsoleUninstallPlan('evolution-cms/etinymce', true, [
            'files' => 2,
            'db' => 1,
        ], [
            ['type' => 'file', 'path' => 'assets/plugins/tinymce4'],
        ], []);
    }

    /**
     * Return a deterministic successful uninstall result for registry tests.
     *
     * The method does not perform any destructive action. It only echoes the
     * package name from the supplied plan and exposes fixed counters so the
     * registry behavior can be asserted safely.
     *
     * @param ConsoleUninstallPlan $plan Prepared uninstall plan.
     * @param array<string, mixed> $context
     * @return ConsoleUninstallResult Successful fake uninstall result.
     */
    public function apply(ConsoleUninstallPlan $plan, array $context = []): ConsoleUninstallResult
    {
        return new ConsoleUninstallResult(true, $plan->getPackageName(), 'Uninstall completed.', '', [
            'deleted_files' => 2,
            'deleted_records' => 1,
        ]);
    }
}
