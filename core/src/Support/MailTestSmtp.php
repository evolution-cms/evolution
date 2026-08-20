<?php namespace EvolutionCMS\Support;

use PHPMailer\PHPMailer\SMTP;

/**
 * Retains the last PHP stream warning for sanitized mail-test classification.
 *
 * PHPMailer replaces the detailed stream warning with a generic connection
 * error after an implicit TLS connection fails. This test-only transport keeps
 * the warning in memory without exposing it to the Manager response or logs.
 *
 * @since 3.5.8
 */
class MailTestSmtp extends SMTP
{
    protected string $connectionErrorDetails = '';

    public function connectionErrorDetails(): string
    {
        return $this->connectionErrorDetails;
    }

    /**
     * Capture the raw stream warning before PHPMailer replaces its error state.
     *
     * @param int $errno PHP error level.
     * @param string $errmsg PHP stream warning.
     * @param string $errfile Source file path.
     * @param int $errline Source line number.
     * @return void
     */
    protected function errorHandler($errno, $errmsg, $errfile = '', $errline = 0)
    {
        $this->connectionErrorDetails = (string)$errmsg;

        parent::errorHandler($errno, $errmsg, $errfile, $errline);
    }
}
