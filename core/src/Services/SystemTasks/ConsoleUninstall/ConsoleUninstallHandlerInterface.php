<?php namespace EvolutionCMS\Services\SystemTasks\ConsoleUninstall;

interface ConsoleUninstallHandlerInterface
{
    public function preview(array $context = []): ConsoleUninstallPlan;

    public function apply(ConsoleUninstallPlan $plan, array $context = []): ConsoleUninstallResult;
}
