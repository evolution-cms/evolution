<?php namespace EvolutionCMS\Controllers;

use EvolutionCMS\Facades\ManagerTheme;
use EvolutionCMS\Interfaces\ManagerTheme as ManagerThemeContract;
use EvolutionCMS\Support\MailTestMailer;
use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

/**
 * Handles the native Manager action that sends a one-time test mail message.
 *
 * The action uses only the currently saved mail configuration and returns a
 * JSON payload for the System Settings UI without saving pending form values.
 *
 * @since 3.5.8
 */
class MailTest extends AbstractController implements ManagerThemeContract\PageControllerInterface
{
    /**
     * Native Manager action ID reserved for the mail test.
     */
    public const ACTION_ID = 201;

    /**
     * JSON payload rendered for the Manager client.
     *
     * @var array{success: bool, message: string}
     */
    protected array $response = [
        'success' => false,
        'message' => '',
    ];

    /**
     * Restricts the mail test to authenticated Manager users with settings access.
     *
     * @return bool
     *
     * @since 3.5.8
     */
    public function canView(): bool
    {
        return ManagerTheme::isAuthManager()
            && ManagerTheme::hasManagerAccess()
            && $this->managerTheme->getCore()->hasPermission('settings');
    }

    /**
     * The one-time mail action does not acquire a Manager editing lock.
     *
     * @return null
     *
     * @since 3.5.8
     */
    public function checkLocked(): ?string
    {
        return null;
    }

    /**
     * Validates the Manager request and sends through the saved mail method.
     *
     * The response contains only localized, sanitized feedback. Transport
     * credentials and raw mailer state are never included in the payload.
     *
     * @return bool Always true so the Manager dispatcher renders the JSON payload.
     *
     * @since 3.5.8
     */
    public function process(): bool
    {
        $request = request();

        if (!$request->isMethod('post')) {
            $this->fail(__('global.mail_test_method_not_allowed'));

            return true;
        }

        $sessionToken = (string)($_SESSION['_token'] ?? '');
        $requestToken = (string)$request->input('_token', '');

        if (
            $sessionToken === ''
            || $requestToken === ''
            || !hash_equals($sessionToken, $requestToken)
        ) {
            $this->fail(__('global.mail_test_csrf_error'));

            return true;
        }

        $destination = trim((string)$request->input('destination', ''));
        if (
            strlen($destination) > 254
            || filter_var($destination, FILTER_VALIDATE_EMAIL) === false
            || !PHPMailer::validateAddress($destination)
        ) {
            $this->fail(__('global.mail_test_invalid_destination'));

            return true;
        }

        $evo = $this->managerTheme->getCore();
        $method = (string)$evo->getConfig('email_method');

        if (!in_array($method, ['mail', 'smtp'], true)) {
            $this->fail(__('global.mail_test_unsupported_method'));

            return true;
        }

        if ($method === 'mail' && $evo->debug) {
            $this->fail(__('global.mail_test_debug_mode'));

            return true;
        }

        $methodLabel = __('global.mail_test_method_' . $method);
        $mailer = (new MailTestMailer())->init($evo);
        $mailer->addAddress($destination);
        $mailer->Subject = __('global.mail_test_subject', [
            'destination' => $destination,
            'site' => (string)$evo->getConfig('site_name'),
        ]);
        $textBody = __('global.mail_test_body', [
            'site' => (string)$evo->getConfig('site_name'),
            'time' => date(DATE_ATOM),
            'method' => $methodLabel,
        ]);
        $mailer->isHTML(true);
        $mailer->Body = $this->renderHtmlMessage(
            $mailer->Subject,
            $textBody,
            __('global.mail_test_automated_note'),
            (string)$evo->getVersionData('version')
        );

        try {
            if (!$mailer->send()) {
                $this->fail(__('global.' . $mailer->failureMessageKey()));

                return true;
            }
        } catch (Throwable $exception) {
            $this->fail(__('global.' . $mailer->failureMessageKey($exception)));

            return true;
        }

        $this->response = [
            'success' => true,
            'message' => __('global.mail_test_success', [
                'method' => $methodLabel,
            ]),
        ];

        return true;
    }

    /**
     * Encodes the Manager action result for the fetch client.
     *
     * Native Manager actions render strings, so domain failures are expressed
     * through the payload's success flag rather than an HTTP status code.
     *
     * @param array<string, mixed> $params Unused native controller render parameters.
     * @return string
     *
     * @since 3.5.8
     */
    public function render(array $params = []): string
    {
        return json_encode($this->response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Stores sanitized failure feedback for the JSON response.
     *
     * @param string $message Localized message safe for Manager display.
     * @return void
     *
     * @since 3.5.8
     */
    private function fail(string $message): void
    {
        $this->response = [
            'success' => false,
            'message' => $message,
        ];
    }

    /**
     * Render the HTML-only body for a test mail message.
     *
     * @param string $subject Localized message subject.
     * @param string $textBody Localized message body.
     * @param string $footer Localized automated-message note.
     * @param string $version Current Evolution CMS version.
     * @return string Complete email-safe HTML document.
     *
     * @since 3.5.8
     */
    private function renderHtmlMessage(string $subject, string $textBody, string $footer, string $version): string
    {
        $subject = htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        $textBody = nl2br(htmlspecialchars($textBody, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'));
        $footer = htmlspecialchars($footer, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        $version = htmlspecialchars($version, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');

        return <<<HTML
<!doctype html>
<html>
<body style="margin:0;padding:24px;background:#f3f6f8;color:#243447;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr><td align="center">
      <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #dfe7ec;border-radius:12px;overflow:hidden;">
        <tr><td style="padding:24px 28px;background:#167d3f;color:#ffffff;">
          <div style="font-size:14px;line-height:20px;opacity:.88;">Evolution CMS {$version}</div>
          <h1 style="margin:6px 0 0;text-align:center;font-size:24px;line-height:32px;font-weight:700;">{$subject}</h1>
        </td></tr>
        <tr><td style="padding:28px;font-size:16px;line-height:26px;color:#334155;">{$textBody}</td></tr>
        <tr><td style="padding:16px 28px;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:12px;line-height:18px;color:#64748b;">{$footer}</td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }
}
