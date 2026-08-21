<?php namespace EvolutionCMS\Services;

use EvolutionCMS\Models\User;
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

        $link = EVO_MANAGER_URL . '?a=0&hash=' . rawurlencode((string) $token) . '&mode=' . rawurlencode((string) $mode);

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
