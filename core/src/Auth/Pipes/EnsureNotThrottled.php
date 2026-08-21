<?php namespace EvolutionCMS\Auth\Pipes;

use Closure;
use EvolutionCMS\Auth\Contracts\LoginPipe;
use EvolutionCMS\Auth\LoginAttempt;
use EvolutionCMS\Exceptions\ServiceActionException;
use Illuminate\Cache\RateLimiter;

/**
 * Rate limits login attempts per username and IP.
 *
 * Uses Illuminate's RateLimiter, which is already available: it is bound by
 * Illuminate\Cache\CacheServiceProvider, and that provider is registered as
 * `Laravel_Cache` in core/config/app.php. Nothing from Illuminate\Auth is involved.
 *
 * This complements, and does not replace, the account lockout in UserLogin
 * (failedlogincount / blockeduntil): that one protects a single account, this one also
 * covers attempts spread across many usernames from one address.
 *
 * Limits come from `cms.auth.throttle`:
 *     ['attempts' => 5, 'decay' => 300]
 */
class EnsureNotThrottled implements LoginPipe
{
    public const DEFAULT_ATTEMPTS = 5;
    public const DEFAULT_DECAY_SECONDS = 300;

    /**
     * @var RateLimiter|null
     */
    protected $limiter;

    /**
     * @param RateLimiter|null $limiter
     */
    public function __construct($limiter = null)
    {
        $this->limiter = $limiter instanceof RateLimiter ? $limiter : null;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(LoginAttempt $attempt, Closure $next)
    {
        $limiter = $this->limiter();

        if (is_null($limiter)) {
            // No cache available (CLI, installer): rate limiting is a safety net, not a
            // precondition, so a missing limiter must not block a legitimate login.
            return $next($attempt);
        }

        $key = $this->throttleKey($attempt);
        [$attempts, $decay] = $this->limits();

        if ($limiter->tooManyAttempts($key, $attempts)) {
            throw new ServiceActionException($this->message($limiter->availableIn($key)));
        }

        $limiter->hit($key, $decay);

        $user = $next($attempt);

        // Only a completed login clears the counter — a pipe further down the chain may
        // still refuse the attempt, and that refusal has to keep counting.
        $limiter->clear($key);

        return $user;
    }

    /**
     * @param LoginAttempt $attempt
     * @return string
     */
    protected function throttleKey(LoginAttempt $attempt): string
    {
        $identity = (string) ($attempt->get('username', $attempt->get('id', '')));
        $address = (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli');

        return 'evo-login|' . $attempt->stage . '|' . mb_strtolower($identity) . '|' . $address;
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected function limits(): array
    {
        try {
            $configured = config('cms.auth.throttle', []);
        } catch (\Throwable $exception) {
            $configured = [];
        }

        $attempts = (int) ($configured['attempts'] ?? self::DEFAULT_ATTEMPTS);
        $decay = (int) ($configured['decay'] ?? self::DEFAULT_DECAY_SECONDS);

        return [
            $attempts > 0 ? $attempts : self::DEFAULT_ATTEMPTS,
            $decay > 0 ? $decay : self::DEFAULT_DECAY_SECONDS,
        ];
    }

    /**
     * @param int $seconds
     * @return string
     */
    protected function message(int $seconds): string
    {
        try {
            return \Lang::get('global.login_processor_throttled', ['seconds' => $seconds]);
        } catch (\Throwable $exception) {
            return 'Too many login attempts. Please try again in ' . $seconds . ' seconds.';
        }
    }

    /**
     * @return RateLimiter|null
     */
    protected function limiter(): ?RateLimiter
    {
        if (!is_null($this->limiter)) {
            return $this->limiter;
        }

        try {
            return evolutionCMS()->make(RateLimiter::class);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
