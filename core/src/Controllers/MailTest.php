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
            'site' => (string)$evo->getConfig('site_name'),
        ]);
        $mailer->Body = __('global.mail_test_body', [
            'site' => (string)$evo->getConfig('site_name'),
            'time' => date(DATE_ATOM),
            'method' => $methodLabel,
        ]);
        $mailer->isHTML(false);

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
}
