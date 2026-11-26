<?php namespace EvolutionCMS\Services\SystemTasks;

/**
 * A task failure the manager can name: the worker stores its error code and result instead of TASK_EXECUTION_FAILED.
 *
 * @since 3.5.10
 */
class TaskFailedException extends \RuntimeException
{
    protected string $errorCode;

    protected array $result;

    public function __construct(string $errorCode, string $message, array $result = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
        $this->result = $result;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function result(): array
    {
        return $this->result;
    }
}
