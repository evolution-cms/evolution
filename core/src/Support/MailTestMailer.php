<?php namespace EvolutionCMS\Support;

use EvolutionCMS\Mail;
use PHPMailer\PHPMailer\SMTP;
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
    protected ?int $expiredCertificateValidTo = null;

    /**
     * Use a test-only SMTP transport that retains connection diagnostics.
     *
     * @return SMTP
     */
    public function getSMTPInstance()
    {
        if (!is_object($this->smtp)) {
            $this->smtp = new MailTestSmtp();
        }

        return $this->smtp;
    }

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
            if ($this->smtp instanceof MailTestSmtp) {
                $msg .= ' ' . $this->smtp->connectionErrorDetails();
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

        if (
            $this->containsAny($details, ['certificate', 'tls', 'ssl', 'crypto'])
            && ($validTo = $this->probePeerCertificateValidTo()) !== null
            && $validTo < time()
        ) {
            $this->expiredCertificateValidTo = $validTo;

            return 'mail_test_error_certificate_expired';
        }

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
     * Returns sanitized localization parameters for the classified failure.
     *
     * @return array<string, string>
     */
    public function failureMessageParameters(): array
    {
        if ($this->expiredCertificateValidTo === null) {
            return [];
        }

        return [
            'date' => gmdate('Y-m-d H:i:s \\U\\T\\C', $this->expiredCertificateValidTo),
        ];
    }

    /**
     * Read the implicit-TLS peer certificate without validating or authenticating.
     *
     * This probe runs only after PHPMailer has already reported a TLS-related
     * failure. It never sends SMTP credentials or message content.
     */
    protected function probePeerCertificateValidTo(): ?int
    {
        if ($this->SMTPSecure !== static::ENCRYPTION_SMTPS) {
            return null;
        }

        $hostEntry = trim(explode(';', (string)$this->Host, 2)[0]);
        if (!preg_match('/^(?:ssl:\/\/)?([^:]+)(?::(\d+))?$/i', $hostEntry, $matches)) {
            return null;
        }

        $host = $matches[1];
        $port = isset($matches[2]) ? (int)$matches[2] : (int)$this->Port;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'capture_peer_cert' => true,
                'peer_name' => $host,
            ],
        ]);
        $socket = @stream_socket_client(
            'ssl://' . $host . ':' . $port,
            $errno,
            $error,
            min(5, max(1, (int)$this->Timeout)),
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!is_resource($socket)) {
            return null;
        }

        try {
            $parameters = stream_context_get_params($socket);
            $certificate = $parameters['options']['ssl']['peer_certificate'] ?? null;
            if ($certificate === null) {
                return null;
            }

            $certificateData = openssl_x509_parse($certificate, false);
            $validTo = $certificateData['validTo_time_t'] ?? null;

            return is_int($validTo) ? $validTo : null;
        } finally {
            fclose($socket);
        }
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
