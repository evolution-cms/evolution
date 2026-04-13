<?php namespace EvolutionCMS\Services\SystemTasks\ConsoleUninstall;

class ConsoleUninstallResult
{
    protected bool $ok;
    protected string $packageName;
    protected string $message;
    protected string $errorCode;
    protected array $summary;
    protected array $warnings;

    public function __construct(bool $ok, string $packageName, string $message = '', string $errorCode = '', array $summary = [], array $warnings = [])
    {
        $this->ok = $ok;
        $this->packageName = trim($packageName);
        $this->message = $message;
        $this->errorCode = trim($errorCode);
        $this->summary = $summary;
        $this->warnings = $warnings;
    }

    public static function unsupported(string $packageName, string $message = 'Console uninstall handler is not registered for this package.'): self
    {
        return new self(false, $packageName, $message, 'CONSOLE_UNINSTALL_NOT_SUPPORTED');
    }

    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'package_name' => $this->packageName,
            'message' => $this->message,
            'error_code' => $this->errorCode,
            'summary' => $this->summary,
            'warnings' => $this->warnings,
        ];
    }

    public function isOk(): bool
    {
        return $this->ok;
    }
}
