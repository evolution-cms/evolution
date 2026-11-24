<?php namespace EvolutionCMS\Auth;

use EvolutionCMS\Exceptions\ServiceActionException;
use EvolutionCMS\Models\SystemSetting;
use EvolutionCMS\Models\User;
use EvolutionCMS\Services\PasswordRecoveryService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The second sign-in step: which method a user has, its challenge, recovery codes.
 *
 * PipelineUserManager calls gate() on each way into a session after the first factor is accepted
 * and before anything is written. A user who owes a second factor gets no session: the attempt waits
 * in the visitor's session until complete() accepts an answer.
 *
 * Which method applies: the administrator's choice for the user, else the site setting, else (only
 * when the site setting is "off") what the user chose on their own page.
 *
 * Methods: `email` and `totp` of core/includes/two_factor, and whatever extend() adds, which a plugin
 * calls on the OnTwoFactorMethods event.
 *
 * @since 3.6.0
 */
class TwoFactor
{
    public const OFF = 'off';
    public const SETTING = 'two_factor_method';
    public const TABLE = 'user_two_factor';
    public const SESSION = 'evoTwoFactor';

    /** Query parameter of the front-end page that asks website users for the second factor. */
    public const QUERY = 'evo_two_factor';

    /** Code of the ServiceActionException that says the sign-in waits for the second factor: pending(), complete(). */
    public const PENDING = 2;

    public const RECOVERY_CODES = 10;

    /** Seconds an accepted password waits for the second factor. */
    protected const PENDING_TTL = 900;

    /** Seconds the user may change their own set-up after proving the current one. */
    protected const UNLOCK_TTL = 600;

    /** Rows of TABLE that are not a method; extend() accepts no such key. */
    protected const POLICY = '@policy';
    protected const RECOVERY = '@recovery';

    /** @var TwoFactorMethod[] */
    protected array $extended = [];

    /** User id whose second factor complete() has just accepted. */
    protected ?int $passed = null;

    protected int $trusted = 0;

    /* ------------------------------------------------------------------ methods */

    /**
     * Add a method, or replace a built-in one.
     */
    public function extend(string $key, TwoFactorMethod $method): void
    {
        if ($key === self::OFF || !preg_match('/^[a-z][a-z0-9_-]{0,31}$/D', $key)) {
            throw new \InvalidArgumentException('Invalid two-factor method key: ' . $key);
        }

        $this->extended[$key] = $method;
    }

    /**
     * @return TwoFactorMethod[] by key
     */
    public function methods(): array
    {
        $builtIn = [];
        foreach (['email', 'totp'] as $key) {
            $builtIn[$key] = require EVO_CORE_PATH . 'includes/two_factor/' . $key . '.php';
        }
        $this->collect();

        return array_replace($builtIn, $this->extended);
    }

    /** Lets plugins call extend(). */
    protected function collect(): void
    {
        evo()->invokeEvent('OnTwoFactorMethods', ['twoFactor' => $this]);
    }

    public function method(string $key): ?TwoFactorMethod
    {
        return $this->methods()[$key] ?? null;
    }

    /**
     * @return array<string, string> choices of the settings form
     */
    public function options(): array
    {
        return [self::OFF => __('global.two_factor_off')]
            + array_map(static fn (TwoFactorMethod $method) => $method->title(), $this->methods());
    }

    /* ------------------------------------------------------------------- policy */

    /**
     * The site setting as stored: the configuration array holds the settings of whoever is signed in
     * on top, and those must not decide about somebody else's sign-in.
     */
    public function siteMethod(): string
    {
        $value = config('cms.settings.' . self::SETTING)
            ?? SystemSetting::query()->where('setting_name', self::SETTING)->value('setting_value');

        return is_string($value) && $value !== '' ? $value : self::OFF;
    }

    /**
     * The administrator's choice for one user, null for the site setting.
     */
    public function override(int $userId): ?string
    {
        $value = $this->record($userId, self::POLICY)['state']['override'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function setOverride(int $userId, ?string $method): void
    {
        $this->setPolicy($userId, 'override', $method);
    }

    /**
     * What the user chose on their own; counts only while neither the site nor an administrator decides.
     */
    public function setOwnChoice(int $userId, ?string $method): void
    {
        $this->setPolicy($userId, 'own', $method);
    }

    public function mayChoose(int $userId): bool
    {
        return $this->siteMethod() === self::OFF && $this->override($userId) === null;
    }

    protected function setPolicy(int $userId, string $name, ?string $method): void
    {
        $record = $this->record($userId, self::POLICY);
        $state = $record['state'];
        unset($state[$name]);
        if ($method !== null && $method !== '') {
            $state[$name] = $method;
        }
        $this->store($userId, self::POLICY, $state, null, 0);
    }

    /**
     * Key of the method the user has to pass, OFF for none.
     */
    public function effective(int $userId): string
    {
        $policy = $this->record($userId, self::POLICY)['state'];
        $site = $this->siteMethod();
        $key = (string) ($policy['override'] ?? ($site !== self::OFF ? $site : ($policy['own'] ?? self::OFF)));

        if ($key === '' || $key === self::OFF || $this->method($key) !== null) {
            return $key === '' ? self::OFF : $key;
        }

        // Its plugin is gone. Neither an open door nor a locked out site: a method that always exists.
        $fallback = $this->isConfirmed($userId, 'totp') ? 'totp' : 'email';
        $this->log("Two-factor method \"{$key}\" is not available; \"{$fallback}\" is asked of user {$userId} instead.");

        return $fallback;
    }

    /* --------------------------------------------------------------- sign-in gate */

    /**
     * Let a login go on, or stop it until the second factor is given.
     *
     * @param User $user whoever passed the first factor
     * @param string $context session context, mgr or web
     * @param array $data what the login was called with; `two_factor_code` answers right away
     * @param string $stage LoginPipeline::STAGE_*
     * @param callable|null $rotate gives the visitor a new session id
     * @throws ServiceActionException
     */
    public function gate(User $user, string $context, array $data, string $stage, ?callable $rotate = null): void
    {
        $userId = (int) $user->getKey();

        if ($this->trusted > 0 || $this->passed === $userId || $this->isConsole()) {
            return;
        }

        $key = $this->effective($userId);
        if ($key === self::OFF) {
            return;
        }

        $method = $this->method($key);
        $record = $this->record($userId, $key);
        $needsSetup = $method->needsSetup($user, $record['state'], $context);

        $response = $data['two_factor_code'] ?? '';
        if (is_string($response) && $response !== '' && !$needsSetup) {
            if ($this->verify($user, $key, $response, $context)) {
                return;
            }
            throw new ServiceActionException(__('global.two_factor_wrong_code'));
        }

        $waits = $this->hasSession();
        if ($waits) {
            // what follows belongs to whoever entered the password, not to a session id they were handed
            if ($rotate !== null) {
                $rotate();
            }
            $_SESSION[self::SESSION]['pending'][$context] = [
                'user' => $userId,
                'key' => $key,
                'stage' => $stage,
                'remember' => (int) !empty($data['rememberme']),
                'return' => $this->currentUrl(),
                'at' => time(),
                'setup' => $needsSetup ? $method->beginSetup($user, $context) : null,
            ];
        }

        if (!$needsSetup) {
            try {
                $this->begin($user, $key, $context);
            } catch (ServiceActionException $exception) {
                // the code cannot be sent, but a recovery code will do
                if (!$waits || $this->recoveryCodesLeft($userId) === 0) {
                    $this->cancel($context);
                    throw $exception;
                }
            }
        }

        if ($waits && $context === 'web') {
            $this->redirectToPage();
        }

        throw new ServiceActionException(__($needsSetup && !$waits
            ? 'global.two_factor_setup_required'
            : 'global.two_factor_required'), self::PENDING);
    }

    /**
     * Run a login whose own first factor is already as good as two, such as a passkey with user
     * verification, or one an administrator starts for a user, without asking for the second factor.
     *
     * @param callable $login
     * @return mixed what it returns
     */
    public function withoutChallenge(callable $login)
    {
        $this->trusted++;
        try {
            return $login();
        } finally {
            $this->trusted--;
        }
    }

    /**
     * The sign-in that waits for a second factor.
     *
     * @return array{user: int, key: string, stage: string, remember: int, return: string, at: int, setup: ?array}|null
     */
    public function pending(string $context): ?array
    {
        $pending = $_SESSION[self::SESSION]['pending'][$context] ?? null;
        if (!is_array($pending) || (int) ($pending['at'] ?? 0) + self::PENDING_TTL < time()) {
            $this->cancel($context);

            return null;
        }

        return $pending;
    }

    public function cancel(string $context): void
    {
        unset($_SESSION[self::SESSION]['pending'][$context]);
    }

    /**
     * Finish the waiting sign-in.
     *
     * @return array{user: User, stage: string, return: string, codes: string[]} `codes` are new recovery codes,
     *         to be shown now: only hashes of them are kept
     * @throws ServiceActionException when the answer is wrong, the account is locked or nothing waits
     */
    public function complete(string $context, string $response): array
    {
        $pending = $this->pending($context);
        $user = $pending === null ? null : User::query()->find($pending['user']);
        if ($user === null) {
            throw new ServiceActionException(__('global.two_factor_expired'));
        }

        $userId = (int) $user->getKey();
        $key = $this->effective($userId);
        if ($key !== self::OFF) {
            if ($key !== $pending['key']) {
                // the administrator switched the method meanwhile
                $this->cancel($context);
                throw new ServiceActionException(__('global.two_factor_expired'));
            }

            if (is_array($pending['setup'])) {
                // enrolled meanwhile, in another browser: that set-up stands
                if (!$this->method($key)->needsSetup($user, $this->record($userId, $key)['state'], $context)) {
                    $this->cancel($context);
                    throw new ServiceActionException(__('global.two_factor_expired'));
                }
                $accepted = $this->finishSetup($user, $key, $response, $pending['setup'], $context);
            } else {
                $accepted = $this->verify($user, $key, $response, $context);
            }
            if (!$accepted) {
                throw new ServiceActionException(__('global.two_factor_wrong_code'));
            }
        }

        $this->cancel($context);
        $this->passed = $userId;
        try {
            $user = evo()->make('UserManager')->loginByIdWithoutPipeline([
                'id' => $userId,
                'rememberme' => $pending['remember'],
                'context' => $context,
            ]);
        } finally {
            $this->passed = null;
        }

        if ($pending['stage'] === LoginPipeline::STAGE_HASH_LOGIN) {
            // hashLogin() stopped before it could spend the recovery link
            (new PasswordRecoveryService())->clearToken($user->refresh());
        }
        $this->unlock($userId, $context);

        return [
            'user' => $user,
            'stage' => $pending['stage'],
            'return' => $pending['return'],
            'codes' => $key === self::OFF ? [] : $this->firstRecoveryCodes($userId),
        ];
    }

    /**
     * Start the challenge of the waiting sign-in once more (send another code).
     *
     * @throws ServiceActionException
     */
    public function resend(string $context): void
    {
        $pending = $this->pending($context);
        $user = $pending === null ? null : User::query()->find($pending['user']);
        if ($user !== null && $pending['setup'] === null) {
            $this->begin($user, $pending['key'], $context);
        }
    }

    /**
     * @throws ServiceActionException when the challenge cannot be started
     */
    protected function begin(User $user, string $key, string $context): void
    {
        $userId = (int) $user->getKey();
        $record = $this->record($userId, $key);
        try {
            $state = $this->method($key)->begin($user, $record['state'], $context);
        } catch (ServiceActionException $exception) {
            $this->log("Two-factor challenge \"{$key}\" of user {$userId} was not started: " . e($exception->getMessage()));
            throw $exception;
        }
        $this->store($userId, $key, $state, $record['raw'], $record['confirmed']);
    }

    /**
     * Check an answer, or a recovery code in place of it, and count the attempt.
     *
     * @throws ServiceActionException when the account is locked
     */
    public function verify(User $user, string $key, string $response, string $context): bool
    {
        $userId = (int) $user->getKey();
        $this->countAttempt($userId);

        if ($this->spendRecoveryCode($userId, $response)) {
            $this->clearAttempts($userId);

            return true;
        }

        $record = $this->record($userId, $key);
        $state = $this->method($key)?->verify($user, $response, $record['state'], $context);
        if ($state === null || !$this->store($userId, $key, $state, $record['raw'], $record['confirmed'] ?: time())) {
            return false;
        }
        $this->clearAttempts($userId);

        return true;
    }

    protected function finishSetup(User $user, string $key, string $response, array $setup, string $context): bool
    {
        $userId = (int) $user->getKey();
        $this->countAttempt($userId);

        $state = $this->method($key)?->finishSetup($user, $response, $setup, $context);
        if ($state === null) {
            return false;
        }
        $this->store($userId, $key, $state, null, time());
        $this->clearAttempts($userId);

        return true;
    }

    /* ----------------------------------------------------------- the user's own page */

    /** Is the set-up proven by an accepted answer? */
    public function isConfirmed(int $userId, string $key): bool
    {
        return $this->record($userId, $key)['confirmed'] > 0;
    }

    /** Must the user prove the method in force before touching the set-up? */
    public function isLocked(User $user, string $context): bool
    {
        $userId = (int) $user->getKey();
        $key = $this->effective($userId);
        if ($key === self::OFF
            || !$this->isConfirmed($userId, $key)
            || $this->method($key)->needsSetup($user, $this->record($userId, $key)['state'], $context)) {
            return false;
        }

        $unlocked = $_SESSION[self::SESSION]['unlocked'][$context] ?? [];

        return (int) ($unlocked['user'] ?? 0) !== $userId || (int) ($unlocked['at'] ?? 0) + self::UNLOCK_TTL < time();
    }

    protected function unlock(int $userId, string $context): void
    {
        $_SESSION[self::SESSION]['unlocked'][$context] = ['user' => $userId, 'at' => time()];
    }

    /**
     * Start setting up a method, or proving the one in force when $key is null.
     *
     * @throws ServiceActionException
     */
    public function beginOwn(User $user, ?string $key, string $context): void
    {
        $unlocking = $key === null;
        $key ??= $this->effective((int) $user->getKey());
        $method = $this->method($key);
        if ($method === null || $unlocking !== $this->isLocked($user, $context)) {
            return;
        }

        $record = $this->record((int) $user->getKey(), $key);
        // a method without a set-up of its own is proven by its ordinary challenge
        $setup = !$unlocking && ($this->hasSetup($method) || $method->needsSetup($user, $record['state'], $context))
            ? $method->beginSetup($user, $context)
            : null;
        if ($setup === null) {
            $this->begin($user, $key, $context);
        }
        $_SESSION[self::SESSION]['own'][$context] = [
            'user' => (int) $user->getKey(), 'key' => $key, 'unlock' => $unlocking, 'setup' => $setup, 'at' => time(),
        ];
    }

    /** Methods with a set-up override beginSetup(). */
    protected function hasSetup(TwoFactorMethod $method): bool
    {
        return (new \ReflectionMethod($method, 'beginSetup'))->class !== TwoFactorMethod::class;
    }

    /**
     * @return array{user: int, key: string, unlock: bool, setup: ?array, at: int}|null
     */
    public function ownPending(User $user, string $context): ?array
    {
        $own = $_SESSION[self::SESSION]['own'][$context] ?? null;
        if (!is_array($own) || (int) ($own['user'] ?? 0) !== (int) $user->getKey()
            || (int) ($own['at'] ?? 0) + self::PENDING_TTL < time()) {
            $this->cancelOwn($context);

            return null;
        }

        return $own;
    }

    /**
     * @throws ServiceActionException
     */
    public function resendOwn(User $user, string $context): void
    {
        $own = $this->ownPending($user, $context);
        if ($own !== null && $own['setup'] === null) {
            $this->begin($user, $own['key'], $context);
        }
    }

    public function cancelOwn(string $context): void
    {
        unset($_SESSION[self::SESSION]['own'][$context]);
    }

    /**
     * Answer what beginOwn() started.
     *
     * @return string[] new recovery codes, if the user had none
     * @throws ServiceActionException
     */
    public function completeOwn(User $user, string $response, string $context): array
    {
        $own = $this->ownPending($user, $context);
        if ($own === null) {
            throw new ServiceActionException(__('global.two_factor_expired'));
        }

        $userId = (int) $user->getKey();
        if (is_array($own['setup'])) {
            $accepted = $this->finishSetup($user, $own['key'], $response, $own['setup'], $context);
        } elseif ($own['unlock']) {
            $accepted = $this->verify($user, $own['key'], $response, $context);
        } else {
            // a recovery code says nothing about the method that is being set up
            $accepted = $this->normalizeRecoveryCode($response) === null
                && $this->verify($user, $own['key'], $response, $context);
        }
        if (!$accepted) {
            throw new ServiceActionException(__('global.two_factor_wrong_code'));
        }

        $this->cancelOwn($context);
        $this->unlock($userId, $context);
        if ($own['unlock']) {
            return [];
        }
        if ($this->mayChoose($userId)) {
            $this->setOwnChoice($userId, $own['key']);
        }

        return $this->firstRecoveryCodes($userId);
    }

    /**
     * Take back everything the user has set up. The administrator's choice stays.
     */
    public function reset(User $user): void
    {
        $userId = (int) $user->getKey();
        foreach ($this->methods() as $key => $method) {
            $record = $this->record($userId, $key);
            if ($record['raw'] !== null) {
                $method->forget($user, $record['state']);
            }
        }
        DB::table(self::TABLE)->where('user_id', $userId)->where('method', '<>', self::POLICY)->delete();
        $this->setOwnChoice($userId, null);
    }

    /* ------------------------------------------------------------- recovery codes */

    /**
     * Replace the recovery codes.
     *
     * @return string[] the codes; only their hashes are stored
     */
    public function regenerateRecoveryCodes(int $userId): array
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789_';
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODES; $i++) {
            $code = '';
            while (strlen($code) < 16) {
                // "_" only rounds the alphabet to 32 so that every letter is equally likely
                $char = $alphabet[ord(random_bytes(1)) & 31];
                $code .= $char === '_' ? '' : $char;
            }
            $codes[] = implode('-', str_split($code, 4));
        }
        $hashes = array_map(static fn (string $code) => hash('sha256', str_replace('-', '', $code)), $codes);
        $this->store($userId, self::RECOVERY, $hashes, null, time());

        return $codes;
    }

    public function recoveryCodesLeft(int $userId): int
    {
        return count($this->record($userId, self::RECOVERY)['state']);
    }

    /**
     * @return string[]
     */
    protected function firstRecoveryCodes(int $userId): array
    {
        return $this->record($userId, self::RECOVERY)['raw'] === null ? $this->regenerateRecoveryCodes($userId) : [];
    }

    protected function normalizeRecoveryCode(string $code): ?string
    {
        $code = strtolower((string) preg_replace('/[\s-]+/', '', $code));

        return preg_match('/^[a-z2-9]{16}$/D', $code) ? $code : null;
    }

    protected function spendRecoveryCode(int $userId, string $response): bool
    {
        $code = $this->normalizeRecoveryCode($response);
        if ($code === null) {
            return false;
        }

        $record = $this->record($userId, self::RECOVERY);
        $hash = hash('sha256', $code);
        foreach ($record['state'] as $i => $stored) {
            if (is_string($stored) && hash_equals($stored, $hash)) {
                $left = $record['state'];
                unset($left[$i]);
                if (!$this->store($userId, self::RECOVERY, array_values($left), $record['raw'], $record['confirmed'])) {
                    return false;
                }
                $this->log("User {$userId} signed in with a recovery code; " . count($left) . ' left.');

                return true;
            }
        }

        return false;
    }

    /* ------------------------------------------------------------------- attempts */

    /**
     * Count an answer before it is checked, with the counter and the lock of wrong passwords.
     *
     * One statement: parallel guesses cannot slip past the limit between a check and an update.
     *
     * @throws ServiceActionException when the account is locked
     */
    protected function countAttempt(int $userId): void
    {
        $now = time();
        $limit = max(1, (int) evo()->getConfig('failed_login_attempts', 3));
        $until = $now + 60 * max(1, (int) evo()->getConfig('blocked_minutes', 10));

        // blockeduntil goes first: MySQL evaluates assignments left to right, with the new values
        $counted = DB::table('user_attributes')
            ->where('internalKey', $userId)
            ->where('blocked', 0)
            ->where('blockeduntil', '<', $now)
            ->update([
                'blockeduntil' => DB::raw("CASE WHEN failedlogincount = {$limit} - 1 OR {$limit} = 1 THEN {$until} ELSE blockeduntil END"),
                'failedlogincount' => DB::raw("CASE WHEN failedlogincount >= {$limit} THEN 1 ELSE failedlogincount + 1 END"),
            ]);

        if ($counted !== 1) {
            throw new ServiceActionException(__('global.login_processor_blocked2'));
        }
    }

    protected function clearAttempts(int $userId): void
    {
        DB::table('user_attributes')->where('internalKey', $userId)->update(['failedlogincount' => 0, 'blockeduntil' => 0]);
    }

    /* -------------------------------------------------------------------- storage */

    /** What the method has stored about the user. */
    public function state(User $user, string $key): array
    {
        return $this->record((int) $user->getKey(), $key)['state'];
    }

    /**
     * @return array{raw: ?string, state: array, confirmed: int} `raw` is null when there is no row
     */
    protected function record(int $userId, string $key): array
    {
        try {
            $row = DB::table(self::TABLE)->where('user_id', $userId)->where('method', $key)->first();
        } catch (QueryException $exception) {
            // the code of an update is in place before its migrations are
            if (Schema::hasTable(self::TABLE)) {
                throw $exception;
            }
            $row = null;
        }
        $state = $row === null ? null : json_decode($this->decrypt((string) $row->data), true);

        return [
            'raw' => $row === null ? null : (string) $row->data,
            'state' => is_array($state) ? $state : [],
            'confirmed' => $row === null ? 0 : (int) $row->confirmed_at,
        ];
    }

    /** Delete everything stored about a user: the database may give the id to another account. */
    public static function purge(int $userId): void
    {
        try {
            DB::table(self::TABLE)->where('user_id', $userId)->delete();
        } catch (QueryException $exception) {
            if (Schema::hasTable(self::TABLE)) {
                throw $exception;
            }
        }
    }

    /**
     * @param string|null $raw what the row has to hold still, null to replace whatever it holds
     * @return bool false when another request was faster
     */
    protected function store(int $userId, string $key, array $state, ?string $raw, int $confirmed): bool
    {
        $values = ['data' => $this->encrypt((string) json_encode($state, JSON_THROW_ON_ERROR)), 'confirmed_at' => $confirmed];
        $table = DB::table(self::TABLE);

        if ($raw === null) {
            $table->updateOrInsert(['user_id' => $userId, 'method' => $key], $values);

            return true;
        }

        $row = $table->where('user_id', $userId)->where('method', $key)->where('data', $raw);
        if ($values['data'] === $raw) {
            // MySQL reports rows that stay the same as not updated
            $row->update($values);

            return true;
        }

        return $row->update($values) === 1;
    }

    /**
     * Encrypts with `cms.auth.two_factor.key` when the site has set one.
     */
    protected function encrypt(string $data): string
    {
        $key = $this->key();
        if ($key === null) {
            return $data;
        }
        $iv = random_bytes(12);
        $cipher = openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        return 'enc:' . base64_encode($iv . $tag . $cipher);
    }

    protected function decrypt(string $data): string
    {
        if (!str_starts_with($data, 'enc:')) {
            return $data;
        }
        $key = $this->key();
        $binary = (string) base64_decode(substr($data, 4));
        $plain = $key === null ? false : openssl_decrypt(
            substr($binary, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($binary, 0, 12), substr($binary, 12, 16)
        );
        if ($plain === false) {
            // never read as "nothing is set up": that would let the next password enrol a new device
            throw new ServiceActionException(__('global.two_factor_key_lost'));
        }

        return $plain;
    }

    protected function key(): ?string
    {
        $key = config('cms.auth.two_factor.key');

        return is_string($key) && $key !== '' ? hash('sha256', $key, true) : null;
    }

    /* ---------------------------------------------------------------- environment */

    protected function isConsole(): bool
    {
        return is_cli();
    }

    protected function hasSession(): bool
    {
        return !(class_exists('EvoSessionProxy', false) && \EvoSessionProxy::disabled()) && isset($_SESSION);
    }

    /** Local address to go back to after a website sign-in. */
    protected function currentUrl(): string
    {
        $url = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
            ? (string) ($_SERVER['REQUEST_URI'] ?? '')
            : (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $path = (string) parse_url($url, PHP_URL_PATH);
        $query = (string) parse_url($url, PHP_URL_QUERY);
        $host = parse_url($url, PHP_URL_HOST);

        if (!str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '\\')
            || ($host !== null && $host !== parse_url(EVO_SITE_URL, PHP_URL_HOST))) {
            return EVO_BASE_URL;
        }

        return $path . ($query === '' ? '' : '?' . $query);
    }

    public function pageUrl(): string
    {
        return EVO_BASE_URL . 'index.php?' . self::QUERY . '=1';
    }

    /**
     * Extras that sign website users in know nothing about a second factor: take a browser that waits
     * for a page to the one that asks for it. A script gets the exception.
     */
    protected function redirectToPage(): void
    {
        if (config('cms.auth.two_factor.redirect', true) === false
            || headers_sent()
            || is_ajax()
            || !str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'text/html')) {
            return;
        }
        evo()->sendRedirect($this->pageUrl());
    }

    protected function log(string $message): void
    {
        try {
            evo()->logEvent(0, 2, $message, 'Auth');
        } catch (\Throwable $exception) {
            // nothing to report to
        }
    }
}
