<?php namespace EvolutionCMS\Support;

use EvolutionCMS\Mail;
use Throwable;

/**
 * Sends Manager mail tests without exposing the configured transport state.
 *
 * The legacy mailer logs an object dump on failure, which can contain SMTP
 * credentials. This dedicated mailer retains only the details required to map
 * failures to sanitized Manager feedback.
 *
 * @since 3.5.8
 */
class MailTestMailer extends Mail
{
    /**
     * Captures a mail failure without invoking the credential-bearing legacy dump.
     *
     * @param string $msg PHPMailer failure description.
     * @return void
     *
     * @since 3.5.8
     */
    public function SetError($msg)
    {
        ++$this->error_count;

        if ($this->Mailer === 'smtp' && $this->smtp !== null) {
            $lastError = $this->smtp->getError();
            if (!empty($lastError['error'])) {
                $msg .= ' ' . $lastError['error'];
            }
            if (!empty($lastError['detail'])) {
                $msg .= ' ' . $lastError['detail'];
            }
            if (!empty($lastError['smtp_code'])) {
                $msg .= ' ' . $lastError['smtp_code'];
            }
            if (!empty($lastError['smtp_code_ex'])) {
                $msg .= ' ' . $lastError['smtp_code_ex'];
            }
        }

        $this->ErrorInfo = (string)$msg;
    }

    /**
     * Maps raw transport failures to localized feedback keys.
     *
     * PHP mail failures remain generic, while SMTP failures are categorized
     * without returning raw server details or credentials to the caller.
     *
     * @param Throwable|null $exception Optional exception raised by PHPMailer.
     * @return string
     *
     * @since 3.5.8
     */
    public function failureMessageKey(?Throwable $exception = null): string
    {
        if ($this->Mailer !== 'smtp') {
            return 'mail_test_error';
        }

        $details = strtolower($this->ErrorInfo . ' ' . ($exception?->getMessage() ?? ''));

        if ($this->containsAny($details, ['tls', 'ssl', 'certificate', 'crypto'])) {
            return 'mail_test_error_encryption';
        }

        if ($this->containsAny($details, ['authenticate', 'authentication', 'username', 'password', '535'])) {
            return 'mail_test_error_authentication';
        }

        if ($this->containsAny($details, ['recipient', 'address rejected', '550', '551', '553'])) {
            return 'mail_test_error_recipient';
        }

        if ($this->containsAny($details, ['connect', 'connection', 'timed out', 'timeout', 'getaddrinfo', 'refused', 'dns'])) {
            return 'mail_test_error_connection';
        }

        return 'mail_test_error';
    }

    /**
     * Checks whether diagnostic text contains any classification marker.
     *
     * @param string $details Lowercase diagnostic text.
     * @param array<int, string> $needles Failure markers to search for.
     * @return bool
     *
     * @since 3.5.8
     */
    private function containsAny(string $details, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($details, $needle)) {
                return true;
            }
        }

        return false;
    }
}
