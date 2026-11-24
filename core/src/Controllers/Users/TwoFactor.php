<?php namespace EvolutionCMS\Controllers\Users;

use EvolutionCMS\Auth\TwoFactor as Service;
use EvolutionCMS\Controllers\AbstractController;
use EvolutionCMS\Exceptions\ServiceActionException;
use EvolutionCMS\Interfaces\ManagerTheme;
use EvolutionCMS\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * Screens of two-factor authentication: the challenge of a sign-in, in the manager and on the
 * website, and the page on which users look after their own set-up (manager action 202).
 *
 * @since 3.6.0
 */
class TwoFactor extends AbstractController implements ManagerTheme\PageControllerInterface
{
    public const ACTION_ID = 202;

    protected $view = 'page.two_factor';

    /**
     * {@inheritdoc}
     */
    public function canView(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function checkLocked(): ?string
    {
        return null;
    }

    protected static function service(): Service
    {
        return evo()->make(Service::class);
    }

    /* ------------------------------------------------------------ manager sign-in */

    /**
     * The manager login page while a sign-in waits for the second factor, null when none does.
     */
    public static function loginPage(string $error = ''): ?string
    {
        $challenge = static::challengeParameters('mgr', EVO_MANAGER_URL . 'index.php?a=0', $error);

        return $challenge === null
            ? null
            : static::inLoginDesign(view('manager::two_factor.challenge', $challenge)->render());
    }

    /**
     * Handle the answer posted by loginPage().
     *
     * @param callable $finish gets the User and the stage, returns the address to go on to
     */
    public static function answerLogin(callable $finish): void
    {
        [$result, $error] = static::answer('mgr');
        if ($result !== null) {
            $url = $finish($result['user'], $result['stage']);
            if ($result['codes'] === []) {
                header('Location: ' . $url);
            } else {
                echo static::inLoginDesign(view('manager::two_factor.codes', ['codes' => $result['codes'], 'url' => $url])->render());
            }

            return;
        }

        $page = static::loginPage($error);
        if ($page === null) {
            // cancelled, expired or locked meanwhile
            echo $error === '' ? '' : static::inLoginDesign(view('manager::two_factor.message', [
                'message' => $error, 'url' => EVO_MANAGER_URL,
            ])->render());
            if ($error === '') {
                header('Location: ' . EVO_MANAGER_URL);
            }

            return;
        }
        echo $page;
    }

    /**
     * The login template, whatever it is, with its form replaced.
     */
    protected static function inLoginDesign(string $content): string
    {
        $theme = evo()['ManagerTheme'];
        $login = $theme->makeTemplate('login', 'manager_login_tpl', [
            'login_form_position_class' => 'loginbox-' . evo()->getConfig('login_form_position'),
            'login_form_style_class' => 'loginbox-' . evo()->getConfig('login_form_style'),
        ], false);

        return static::replaceLoginForm($login, $content)
            ?? view('manager::two_factor.page', ['content' => $content])->render();
    }

    /**
     * @return string|null null when the template has no recognizable login form
     */
    public static function replaceLoginForm(string $login, string $content): ?string
    {
        $form = '~<form\b(?=[^>]*\bloginfrm\b)([^>]*)>(.*?)</form>~is';
        if (!preg_match($form, $login, $match)) {
            return null;
        }

        $class = preg_match('~\bclass="([^"]*)"~i', $match[1], $found) ? $found[1] : '';
        $logo = preg_match('~<a\b[^>]*\bclass="[^"]*\blogo\b[^"]*"[^>]*>.*?</a>~is', $match[2], $found)
            ? '<div class="form-group form-group--logo text-center">' . $found[0] . '</div>'
            : '';
        // scripts of the template that drive its own form
        $login = preg_replace_callback(
            '~<script\b[^>]*>.*?</script>~is',
            static fn (array $script) => str_contains($script[0], 'loginfrm') ? '' : $script[0],
            $login
        );

        return preg_replace_callback(
            $form,
            static fn () => '<div class="' . $class . '">' . $logo . $content . '</div>',
            (string) $login,
            1
        );
    }

    /* ------------------------------------------------------------------- challenge */

    /**
     * @return array|null parameters of the two_factor.challenge view, null when no sign-in waits
     */
    protected static function challengeParameters(string $context, string $action, string $error): ?array
    {
        $service = static::service();
        $pending = $service->pending($context);
        $user = $pending === null ? null : User::query()->find($pending['user']);
        $method = $pending === null ? null : $service->method($pending['key']);
        if ($user === null || $method === null) {
            return null;
        }

        $setup = is_array($pending['setup']);

        return [
            'title' => $method->title(),
            'form' => $setup
                ? $method->setupForm($user, $pending['setup'], $context)
                : $method->form($user, $service->state($user, $pending['key']), $context),
            'setup' => $setup,
            'resend' => !$setup && $method->canResend(),
            'recovery' => !$setup && $service->recoveryCodesLeft((int) $user->getKey()) > 0,
            'action' => $action,
            'error' => $error,
        ];
    }

    /**
     * @return array{0: ?array, 1: string} what complete() returned, or the message to show
     */
    protected static function answer(string $context): array
    {
        $service = static::service();
        try {
            switch ($_POST['two_factor'] ?? '') {
                case 'cancel':
                    $service->cancel($context);
                    break;
                case 'resend':
                    $service->resend($context);
                    break;
                default:
                    return [$service->complete($context, static::postedCode()), ''];
            }
        } catch (ServiceActionException $exception) {
            return [null, $exception->getMessage()];
        }

        return [null, ''];
    }

    protected static function postedCode(): string
    {
        $code = $_POST['two_factor_code'] ?? '';

        return is_string($code) ? $code : '';
    }

    /* ------------------------------------------------------------------ own set-up */

    public function process(): bool
    {
        $this->parameters = static::account('mgr', 'index.php?a=' . self::ACTION_ID);
        if (isset($this->parameters['redirect'])) {
            header('Location: ' . $this->parameters['redirect']);
            evo_exit();
        }

        return true;
    }

    /**
     * The user's own page: handle what it posted and describe what it shows.
     *
     * @return array parameters of the two_factor.account view, or `redirect`
     */
    protected static function account(string $context, string $action): array
    {
        $service = static::service();
        $user = User::query()->find((int) evo()->getLoginUserID($context));
        if ($user === null) {
            return ['redirect' => $context === 'mgr' ? EVO_MANAGER_URL : EVO_SITE_URL];
        }
        $userId = (int) $user->getKey();
        $error = '';
        $codes = [];

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && is_string($_POST['two_factor'] ?? null)) {
            try {
                $codes = static::changeAccount($service, $user, $context, $_POST['two_factor']);
                if ($codes === []) {
                    return ['redirect' => $action];
                }
            } catch (ServiceActionException $exception) {
                $error = $exception->getMessage();
            }
        }

        $methods = $service->methods();
        $effective = $service->effective($userId);
        $own = $service->ownPending($user, $context);
        $locked = $service->isLocked($user, $context);
        $rows = [];
        foreach ($methods as $key => $method) {
            $rows[$key] = [
                'title' => $method->title(),
                'confirmed' => $service->isConfirmed($userId, $key),
                'active' => $key === $effective,
            ];
        }

        $form = null;
        if ($own !== null && isset($methods[$own['key']])) {
            $method = $methods[$own['key']];
            $form = [
                'title' => $method->title(),
                'html' => is_array($own['setup'])
                    ? $method->setupForm($user, $own['setup'], $context)
                    : $method->form($user, $service->state($user, $own['key']), $context),
                'recovery' => $own['unlock'] && $service->recoveryCodesLeft($userId) > 0,
                'resend' => !is_array($own['setup']) && $method->canResend(),
            ];
        }

        return [
            'action' => $action,
            'error' => $error,
            'codes' => $codes,
            'rows' => $rows,
            'form' => $form,
            'locked' => $locked,
            'effective' => $effective,
            'mayChoose' => $service->mayChoose($userId),
            'codesLeft' => $service->recoveryCodesLeft($userId),
            'canRegenerate' => array_filter(array_column($rows, 'confirmed')) !== [],
        ];
    }

    /**
     * @return string[] recovery codes to show
     */
    protected static function changeAccount(Service $service, User $user, string $context, string $operation): array
    {
        $userId = (int) $user->getKey();
        [$operation, $key] = explode(':', $operation . ':', 2);
        $key = rtrim($key, ':');

        switch ($operation) {
            case 'unlock':
                $service->beginOwn($user, null, $context);
                return [];
            case 'setup':
                $service->beginOwn($user, $key, $context);
                return [];
            case 'resend':
                $service->resendOwn($user, $context);
                return [];
            case 'cancel':
                $service->cancelOwn($context);
                return [];
            case 'confirm':
                return $service->completeOwn($user, static::postedCode(), $context);
        }

        if ($service->isLocked($user, $context)) {
            return [];
        }

        switch ($operation) {
            case 'use':
                if ($service->mayChoose($userId) && $service->isConfirmed($userId, $key)) {
                    $service->setOwnChoice($userId, $key);
                }
                break;
            case 'off':
                if ($service->mayChoose($userId)) {
                    $service->setOwnChoice($userId, null);
                }
                break;
            case 'codes':
                return $service->regenerateRecoveryCodes($userId);
        }

        return [];
    }

    /* --------------------------------------------------------------------- website */

    /**
     * The page website users get: the challenge while a sign-in waits, else their own set-up.
     *
     * A site replaces its design with a view called `two_factor`.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public static function website()
    {
        $service = static::service();
        $url = $service->pageUrl();
        $pending = $service->pending('web');
        $content = '';

        if ($pending !== null) {
            $error = '';
            if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
                [$result, $error] = static::answer('web');
                if ($result !== null && $result['codes'] === []) {
                    return new RedirectResponse($result['return']);
                }
                if ($result !== null) {
                    $content = view('manager::two_factor.codes', ['codes' => $result['codes'], 'url' => $result['return']])->render();
                } elseif ($error === '') {
                    return new RedirectResponse($service->pending('web') === null ? $pending['return'] : $url);
                }
            }
            if ($content === '') {
                $challenge = static::challengeParameters('web', $url, $error);
                $content = $challenge === null
                    ? view('manager::two_factor.message', ['message' => $error, 'url' => $pending['return']])->render()
                    : view('manager::two_factor.challenge', $challenge)->render();
            }
        } else {
            if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !static::tokenMatches()) {
                return new RedirectResponse($url);
            }
            $account = static::account('web', $url);
            if (isset($account['redirect'])) {
                return new RedirectResponse($account['redirect']);
            }
            $content = view('manager::two_factor.account', $account)->render();
        }

        $view = view()->exists('two_factor') ? 'two_factor' : 'manager::two_factor.page';

        return response()->make(view($view, ['content' => $content])->render(), 200, ['Cache-Control' => 'no-store']);
    }

    protected static function tokenMatches(): bool
    {
        $token = $_SESSION['_token'] ?? null;

        return is_string($token) && $token !== '' && is_string($_POST['_token'] ?? null)
            && hash_equals($token, $_POST['_token']);
    }
}
