<?php namespace EvolutionCMS\Services\SystemTasks;

/**
 * Puts the tail of a failed child process into the task log.
 *
 * Composer and artisan print their banner first and the error last, so the
 * head of the output names the step and the tail names the failure. The
 * manager renders log messages only, so the tail goes in there whole.
 *
 * @since 3.5.8
 */
trait ReportsProcessFailure
{
    protected function reportProcessFailure(?callable $report, $step, $progress, $label, $exitCode, $output, array $context = [])
    {
        $lines = preg_split('/\r\n|\r|\n/', trim((string) $output));
        $tail = trim(implode("\n", array_slice($lines, -40)));
        if ($tail === '') {
            $tail = $label . ' produced no output.';
        }

        $this->report($report, $step, $progress, $tail, 'error', $context + [
            'command' => $label,
            'exit_code' => (int) $exitCode,
            'output' => mb_substr((string) $output, -16000),
        ]);
    }

    /**
     * The one-line reason for the exception: last lines on failure, first lines otherwise.
     */
    protected function pickSummaryLines(array $lines, bool $fromEnd, int $count)
    {
        return $fromEnd ? array_slice($lines, -$count) : array_slice($lines, 0, $count);
    }
}
