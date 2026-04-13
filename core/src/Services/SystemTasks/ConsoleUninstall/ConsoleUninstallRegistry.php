<?php namespace EvolutionCMS\Services\SystemTasks\ConsoleUninstall;

use InvalidArgumentException;

class ConsoleUninstallRegistry
{
    /** @var array<string, string|ConsoleUninstallHandlerInterface> */
    protected array $handlers = [];

    public function __construct(array $handlers = [])
    {
        foreach ($handlers as $packageName => $handler) {
            $this->register($packageName, $handler);
        }
    }

    public function register(string $packageName, $handler): void
    {
        $normalizedPackageName = $this->normalizePackageName($packageName);
        if ($normalizedPackageName === '') {
            throw new InvalidArgumentException('Console uninstall package name must not be empty.');
        }

        if (!$handler instanceof ConsoleUninstallHandlerInterface && !is_string($handler)) {
            throw new InvalidArgumentException('Console uninstall handler must be an instance or class-string.');
        }

        $this->handlers[$normalizedPackageName] = $handler;
    }

    public function has(string $packageName): bool
    {
        return array_key_exists($this->normalizePackageName($packageName), $this->handlers);
    }

    public function preview(string $packageName, array $context = []): ConsoleUninstallPlan
    {
        $handler = $this->resolve($packageName);
        if ($handler === null) {
            return ConsoleUninstallPlan::unsupported($packageName, [
                'Console uninstall handler is not registered for this package.',
            ]);
        }

        return $handler->preview($context);
    }

    public function apply(string $packageName, ConsoleUninstallPlan $plan, array $context = []): ConsoleUninstallResult
    {
        $handler = $this->resolve($packageName);
        if ($handler === null) {
            return ConsoleUninstallResult::unsupported($packageName);
        }

        return $handler->apply($plan, $context);
    }

    public function resolve(string $packageName): ?ConsoleUninstallHandlerInterface
    {
        $normalizedPackageName = $this->normalizePackageName($packageName);
        if ($normalizedPackageName === '' || !isset($this->handlers[$normalizedPackageName])) {
            return null;
        }

        $handler = $this->handlers[$normalizedPackageName];
        if (is_string($handler)) {
            $handler = new $handler();
            if (!$handler instanceof ConsoleUninstallHandlerInterface) {
                throw new InvalidArgumentException('Resolved console uninstall handler must implement the uninstall contract.');
            }
            $this->handlers[$normalizedPackageName] = $handler;
        }

        return $handler;
    }

    protected function normalizePackageName(string $packageName): string
    {
        return strtolower(trim($packageName));
    }
}
