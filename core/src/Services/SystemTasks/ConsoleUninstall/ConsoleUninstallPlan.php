<?php namespace EvolutionCMS\Services\SystemTasks\ConsoleUninstall;

class ConsoleUninstallPlan
{
    protected string $packageName;
    protected bool $supported;
    protected array $summary;
    protected array $operations;
    protected array $warnings;

    public function __construct(string $packageName, bool $supported = false, array $summary = [], array $operations = [], array $warnings = [])
    {
        $this->packageName = trim($packageName);
        $this->supported = $supported;
        $this->summary = $summary;
        $this->operations = $operations;
        $this->warnings = $warnings;
    }

    public static function unsupported(string $packageName, array $warnings = []): self
    {
        return new self($packageName, false, [], [], $warnings);
    }

    public function toArray(): array
    {
        return [
            'package_name' => $this->packageName,
            'supported' => $this->supported,
            'summary' => $this->summary,
            'operations' => $this->operations,
            'warnings' => $this->warnings,
        ];
    }

    public function isSupported(): bool
    {
        return $this->supported;
    }

    public function getPackageName(): string
    {
        return $this->packageName;
    }
}
