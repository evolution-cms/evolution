<?php namespace EvolutionCMS\Services;

use EvolutionCMS\Models\User;
use EvolutionCMS\Models\UserAttribute;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Schema;

/**
 * Issues and validates the one-time token used to set a new password.
 *
 * The token lives in `users.cachepwd`, and its expiry in `users.cachepwd_valid_to`.
 * Two flows use it:
 *
 *  1. "Forgot password" on the manager login screen (ManagerTheme::repairPassword).
 *  2. Automatic recovery: the stored password cannot be verified in any known format,
 *     so no password could ever succeed and the account would be permanently locked
 *     out. Rather than answering "wrong password" forever, the login flow issues a
 *     recovery token and mails the owner a link.
 *
 * Both flows end at manager/index.php?a=0&hash=...&mode=hash, which logs the user in
 * once and redirects to the change-password screen.
 */
class PasswordRecoveryService
{
    /** Fallback lifetime in minutes when `pwd_repair_minutes` is unset or unreadable. */
    public const DEFAULT_TTL_MINUTES = 60;

    public const REQUEST_ACCEPTED = 'accepted';
    public const REQUEST_THROTTLED = 'throttled';

    /** Used where `cms.auth.recovery` is missing or holds a value that is not a positive number. */
    public const DEFAULT_REQUEST_LIMITS = [
        'attempts' => 5,
        'decay' => 900,
        'mails' => 3,
        'mail_decay' => 3600,
        'hold' => 3,
    ];

    /**
     * Cached result of the column probe. Installations update at different times and
     * the automatic flow must not fatal on a database that has not migrated yet.
     *
     * @var bool|null
     */
    protected static $hasExpiryColumn = null;

    /**
     * Issue a token, or return the still-valid one the user already has.
     *
     * Reusing an unexpired token is what keeps the automatic flow from turning a
     * repeated login attempt into a mail flood.
     *
     * @param User $user
     * @return string
     */
    public function issueToken(User $user): string
    {
        if ($this->hasValidToken($user)) {
            return $user->cachepwd;
        }

        $token = bin2hex(random_bytes(16));
        $ttl = $this->ttlSeconds();

        $user->cachepwd = $token;
        if ($this->supportsExpiry()) {
            // No deadline recorded means the link never expires — that is what
            // `pwd_repair_minutes` = 0 asks for.
            $user->cachepwd_valid_to = $ttl > 0 ? date('Y-m-d H:i:s', time() + $ttl) : null;
        }
        $user->save();

        return $token;
    }

    /**
     * Configured lifetime of a recovery link, in seconds.
     *
     * @return int 0 when links never expire
     */
    public function ttlSeconds(): int
    {
        $minutes = $this->configuredMinutes();

        // A missing or malformed setting must not silently mean "never expires";
        // only an explicit 0 does.
        if (!is_numeric($minutes) || (int) $minutes < 0) {
            $minutes = self::DEFAULT_TTL_MINUTES;
        }

        return (int) $minutes * 60;
    }

    /**
     * @return mixed the raw `pwd_repair_minutes` setting, or null when unreadable
     */
    protected function configuredMinutes()
    {
        try {
            return evolutionCMS()->getConfig('pwd_repair_minutes');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    /**
     * @param User $user
     * @return bool
     */
    public function hasValidToken(User $user): bool
    {
        if ((string) $user->cachepwd === '') {
            return false;
        }

        return !$this->isExpired($user);
    }

    /**
     * Resolve a token coming from a recovery link.
     *
     * @param string $token
     * @return User|null null when unknown or expired
     */
    public function findByToken($token): ?User
    {
        $token = (string) $token;

        if ($token === '') {
            return null;
        }

        $user = User::query()->where('cachepwd', $token)->first();

        if (is_null($user)) {
            return null;
        }

        if ($this->isExpired($user)) {
            // An expired token is spent: drop it so the link cannot be replayed.
            $this->clearToken($user);

            return null;
        }

        return $user;
    }

    /**
     * @param User $user
     * @return void
     */
    public function clearToken(User $user): void
    {
        $user->cachepwd = '';
        if ($this->supportsExpiry()) {
            $user->cachepwd_valid_to = null;
        }
        $user->save();
    }

    /**
     * Handle a "forgot password" request from the login screen.
     *
     * The result says nothing about the address: an unknown one, a blocked account, a mail
     * that could not be sent and a mail that went out are all REQUEST_ACCEPTED. Only the
     * per-address limit is reported, and it counts every request, whatever was asked for.
     *
     * @param mixed $email as posted
     * @param string $address the client address (REMOTE_ADDR, not a forwarded header)
     * @return string REQUEST_ACCEPTED or REQUEST_THROTTLED
     */
    public function requestByEmail($email, string $address): string
    {
        $email = is_string($email) ? trim($email) : '';
        $limiter = $this->limiter();
        $limits = $this->requestLimits();

        if (!is_null($limiter)) {
            $key = 'evo-recovery|address|' . $address;

            if ($limiter->tooManyAttempts($key, $limits['attempts'])) {
                // One line per window, however long the client keeps trying.
                $limiter->attempt($key . '|logged', 1, function () use ($address) {
                    $this->log(2, 'Too many password recovery requests from ' . e($address) . '; further ones are refused.');
                }, $limits['decay']);

                return self::REQUEST_THROTTLED;
            }

            $limiter->hit($key, $limits['decay']);
        }

        $user = $email === '' || strlen($email) > 254 ? null : $this->findRecoverableUser($email);
        $detaches = $this->detaches();

        if (is_null($user) && $detaches) {
            return self::REQUEST_ACCEPTED;
        }

        // Everything that happens only for an account waits until the reply is on its way, so
        // the time the reply takes says as little about the address as its text does. Where the
        // server cannot let go of the client first (Apache mod_php), the client still sees how
        // long the connection stays open, so there every answered request holds it equally long.
        $release = $detaches ? null : $this->now() + $limits['hold'];

        $this->defer(function () use ($user, $release) {
            if (!is_null($user)) {
                $this->deliver($user);
            }
            if (!is_null($release)) {
                $this->holdUntil($release);
            }
        });

        return self::REQUEST_ACCEPTED;
    }

    /**
     * Issue the token and mail the link, unless the account was mailed too often already.
     *
     * @param User $user
     * @return void
     */
    protected function deliver(User $user): void
    {
        $limiter = $this->limiter();

        if (!is_null($limiter)) {
            // Requests from many addresses must not flood one mailbox either. Silent: saying
            // "already sent" would confirm the account.
            $limits = $this->requestLimits();
            $key = 'evo-recovery|user|' . $user->getKey();

            if ($limiter->tooManyAttempts($key, $limits['mails'])) {
                return;
            }

            $limiter->hit($key, $limits['mail_decay']);
        }

        try {
            $failure = $this->sendRecoveryMail($user, $this->issueToken($user)) ? null : 'the mailer refused it';
        } catch (\Throwable $exception) {
            $failure = $exception->getMessage();
        }

        if (!is_null($failure)) {
            // The requester is not told, so the administrator has to be.
            $this->log(3, 'Password recovery mail for user ' . (int) $user->getKey() . ' was not sent: ' . e($failure));
        }
    }

    /**
     * Run a callback once the response has been sent (Core::deferAfterResponse()).
     *
     * @param callable $callback
     * @return void
     */
    protected function defer(callable $callback): void
    {
        evolutionCMS()->deferAfterResponse($callback);
    }

    /**
     * Whether deferred work runs with the client already released: Core::finishRequest() can
     * do that under PHP-FPM, FrankenPHP (worker mode included) and LiteSpeed only.
     *
     * @return bool
     */
    protected function detaches(): bool
    {
        return function_exists('fastcgi_finish_request') || function_exists('litespeed_finish_request');
    }

    /**
     * @return float
     */
    protected function now(): float
    {
        return microtime(true);
    }

    /**
     * Keep the connection until a moment that does not depend on what was done for it.
     *
     * @param float $release
     * @return void
     */
    protected function holdUntil(float $release): void
    {
        $left = $release - $this->now();

        if ($left > 0 && PHP_SAPI !== 'cli') {
            // the page itself must not wait for this
            flush();
            usleep((int) ($left * 1000000));
        }
    }

    /**
     * @param string $email
     * @return User|null null when there is no such account or it is blocked
     */
    protected function findRecoverableUser(string $email): ?User
    {
        $attributes = UserAttribute::query()->where('email', $email)->first();

        if (is_null($attributes) || (int) $attributes->blocked === 1) {
            return null;
        }

        return User::query()->find($attributes->internalKey);
    }

    /**
     * Limits of `cms.auth.recovery`.
     *
     * @return array{attempts: int, decay: int, mails: int, mail_decay: int, hold: int}
     */
    protected function requestLimits(): array
    {
        $configured = $this->configuredLimits();

        $limits = [];
        foreach (self::DEFAULT_REQUEST_LIMITS as $name => $default) {
            $value = (int) ($configured[$name] ?? $default);
            $limits[$name] = $value > 0 ? $value : $default;
        }

        return $limits;
    }

    /**
     * @return array the raw `cms.auth.recovery` config, empty when unreadable
     */
    protected function configuredLimits(): array
    {
        try {
            return (array) config('cms.auth.recovery', []);
        } catch (\Throwable $exception) {
            return [];
        }
    }

    /**
     * @return RateLimiter|null null when no cache is available: the limits are a safety net,
     *                          and a missing one must not lock the owner out of recovery
     */
    protected function limiter(): ?RateLimiter
    {
        try {
            return evolutionCMS()->make(RateLimiter::class);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    /**
     * @param int $type 2 warning, 3 error
     * @param string $message
     * @return void
     */
    protected function log(int $type, string $message): void
    {
        try {
            evolutionCMS()->logEvent(0, $type, $message, 'Auth');
        } catch (\Throwable $exception) {
            // nothing to report to
        }
    }

    /**
     * Start recovery for an account whose stored password cannot be verified.
     *
     * @param User $user
     * @return bool true when a mail was sent for this attempt
     */
    public function startAutomaticRecovery(User $user): bool
    {
        // A token that is still valid means the mail already went out; sending another
        // one on every login attempt would turn a broken row into a mail amplifier.
        if ($this->hasValidToken($user)) {
            return false;
        }

        $token = $this->issueToken($user);

        // Only the manager area has a password-reset destination in the core. On the
        // front end the token is still issued, so an extra can build its own flow.
        if (evolutionCMS()->getContext() !== 'mgr') {
            return false;
        }

        return $this->sendRecoveryMail($user, $token);
    }

    /**
     * @param User $user
     * @param string $token
     * @param string $mode
     * @return bool
     */
    public function sendRecoveryMail(User $user, $token, $mode = 'hash'): bool
    {
        $email = is_null($user->attributes) ? '' : (string) $user->attributes->email;

        if ($email === '' || !defined('EVO_MANAGER_URL')) {
            return false;
        }

        $link = $this->recoveryBaseUrl() . '?a=0&hash=' . rawurlencode((string) $token) . '&mode=' . rawurlencode((string) $mode);

        $body = '
                <p>' . \Lang::get('global.forgot_password_email_intro') . ' <a href="' . $link . '">' . \Lang::get('global.forgot_password_email_link') . '</a></p>
                <p>' . \Lang::get('global.forgot_password_email_instructions') . '</p>
                <p><small>' . $this->validityNotice($user) . '</small></p>';

        $param = [];
        $param['from'] = evolutionCMS()->getConfig('site_name') . '<' . evolutionCMS()->getConfig('emailsender') . '>';
        $param['to'] = $email;
        $param['subject'] = \Lang::get('global.password_change_request');
        $param['body'] = $body;

        return evolutionCMS()->sendmail($param);
    }

    /**
     * Manager URL the recovery link points at.
     *
     * @return string
     */
    protected function recoveryBaseUrl(): string
    {
        return static::resolveRecoveryBaseUrl(
            (string) EVO_MANAGER_URL,
            defined('EVO_SITE_HOSTNAMES') ? (string) EVO_SITE_HOSTNAMES : '',
            $_SERVER
        );
    }

    /**
     * Pick the manager URL for a recovery mail without trusting the Host header.
     *
     * EVO_MANAGER_URL is derived from the Host header unless EVO_SITE_HOSTNAMES pins it, and
     * a forged header would make the mail carry the one-time token to the attacker's host.
     * With no pinned host list, the web server's own SERVER_NAME is used instead: it is the
     * configured name where the server fixes it (nginx $server_name, Apache with
     * UseCanonicalName On) and no worse than the header where the server just echoes it.
     * A manager URL whose host is not the one in the Host header was pinned by the operator
     * (EVO_SITE_URL in custom/define.php) and is kept, and so is any SERVER_NAME that is not a
     * usable host name, such as nginx's catch-all "_". Behind a published port that differs from
     * the one the server listens on, the link loses its port: pin EVO_SITE_HOSTNAMES there.
     *
     * @param string $managerUrl Value of EVO_MANAGER_URL.
     * @param string $siteHostnames Value of EVO_SITE_HOSTNAMES (comma separated).
     * @param array $server The $_SERVER array.
     * @return string
     */
    public static function resolveRecoveryBaseUrl(string $managerUrl, string $siteHostnames, array $server): string
    {
        if (trim($siteHostnames, " ,\t") !== '') {
            return $managerUrl;
        }

        $serverName = (string) ($server['SERVER_NAME'] ?? '');
        $parts = parse_url($managerUrl);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])
            || !preg_match('/^(?:[A-Za-z0-9](?:[A-Za-z0-9._-]*[A-Za-z0-9])?|\[[0-9A-Fa-f:.]+\])$/', $serverName)) {
            return $managerUrl;
        }

        $headerHost = strtolower((string) preg_replace('/:\d+$/', '', (string) ($server['HTTP_HOST'] ?? '')));
        if ($headerHost === '' || $headerHost !== strtolower($parts['host'])) {
            return $managerUrl;
        }

        // The port comes from the server too: the one in the URL came from the Host header, and
        // Apache ignores that port when it picks a virtual host, so "site.test:9080" would reach
        // this site and send the token to whatever listens on 9080. SERVER_PORT is the ServerName
        // port under UseCanonicalName On, the listening port elsewhere; 80 and 443 are left out,
        // which also covers TLS terminated by a proxy in front of a server on 80.
        $serverPort = (string) ($server['SERVER_PORT'] ?? '');
        $port = preg_match('/^[1-9]\d{0,4}$/', $serverPort) && (int) $serverPort <= 65535
            && !in_array((int) $serverPort, [80, 443], true)
            ? ':' . (int) $serverPort
            : '';

        return $parts['scheme'] . '://' . $serverName . $port . ($parts['path'] ?? '/');
    }

    /**
     * The sentence that tells the recipient how long the link stays usable.
     *
     * Built from the deadline actually stored on the row, not from the current setting:
     * changing `pwd_repair_minutes` afterwards must not make an already sent mail lie.
     *
     * @param User $user
     * @return string
     */
    protected function validityNotice(User $user): string
    {
        $validTo = $this->supportsExpiry() ? $user->cachepwd_valid_to : null;

        if (empty($validTo)) {
            return \Lang::get('global.forgot_password_email_valid_unlimited');
        }

        return \Lang::get('global.forgot_password_email_valid_until', [
            'datetime' => evolutionCMS()->toDateFormat(strtotime((string) $validTo)),
        ]);
    }

    /**
     * @param User $user
     * @return bool
     */
    protected function isExpired(User $user): bool
    {
        if (!$this->supportsExpiry()) {
            return false;
        }

        $validTo = $user->cachepwd_valid_to;

        // No deadline recorded = never expires (`pwd_repair_minutes` = 0). Tokens that
        // predate the expiry column cannot be mistaken for these: the migration that
        // added the column cleared every token that existed at the time.
        if (empty($validTo)) {
            return false;
        }

        return strtotime((string) $validTo) < time();
    }

    /**
     * @return bool
     */
    protected function supportsExpiry(): bool
    {
        if (self::$hasExpiryColumn === null) {
            try {
                self::$hasExpiryColumn = Schema::hasColumn('users', 'cachepwd_valid_to');
            } catch (\Throwable $exception) {
                self::$hasExpiryColumn = false;
            }
        }

        return self::$hasExpiryColumn;
    }

    /**
     * Test seam: forget the cached column probe.
     *
     * @return void
     */
    public static function flushSchemaCache(): void
    {
        self::$hasExpiryColumn = null;
    }
}
