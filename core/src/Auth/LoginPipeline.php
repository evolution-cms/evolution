<?php namespace EvolutionCMS\Auth;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;

/**
 * Runs the configurable chain of login pipes.
 *
 * The shape is deliberately the one Laravel itself uses for this problem: Fortify
 * composes login out of an ordered array of pipe classes rather than out of events,
 * because an event listener cannot refuse a login. Only Illuminate\Pipeline is needed
 * for that, and it depends on nothing but a container — no Illuminate\Auth guard, no
 * Foundation application.
 *
 * Configuration lives under `cms.auth.pipeline` and is empty by default, so the whole
 * mechanism is inert until a site opts in:
 *
 *     // core/custom/config/cms/auth/pipeline.php
 *     return [
 *         '*'     => [\EvolutionCMS\Auth\Pipes\EnsureNotThrottled::class],
 *         'login' => [\Acme\Sso\Pipes\SocialAuthentication::class],
 *     ];
 *
 * `*` runs for every entry point and is where a second factor belongs: a pipe listed
 * only under `login` is trivially bypassed through a password recovery link
 * (`hashLogin`) or through remember-me (`loginById`).
 */
class LoginPipeline
{
    /** Entry points a pipeline can be attached to. */
    public const STAGE_LOGIN = 'login';
    public const STAGE_LOGIN_BY_ID = 'loginById';
    public const STAGE_HASH_LOGIN = 'hashLogin';

    /** Pseudo-stage: runs before every stage above. */
    public const STAGE_ANY = '*';

    /**
     * @var Container|null
     */
    protected $container;

    /**
     * @param Container|null $container
     */
    public function __construct(?Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param LoginAttempt $attempt
     * @param Closure $destination what the entry point would have done on its own
     * @return mixed
     */
    public function run(LoginAttempt $attempt, Closure $destination)
    {
        $pipes = $this->pipesFor($attempt->stage);

        if ($pipes === []) {
            return $destination($attempt);
        }

        return (new Pipeline($this->resolveContainer()))
            ->send($attempt)
            ->through($pipes)
            ->then($destination);
    }

    /**
     * Ordered pipes for one stage: the `*` pipes first, then the stage's own.
     *
     * @param string $stage
     * @return array
     */
    public function pipesFor(string $stage): array
    {
        $configured = $this->configuredPipeline();

        // A flat list is read as "every stage", which is the safe reading: a pipe the
        // author did not think about placing must not silently cover only one door.
        if ($configured !== [] && array_is_list($configured)) {
            $configured = [self::STAGE_ANY => $configured];
        }

        $pipes = array_merge(
            (array) ($configured[self::STAGE_ANY] ?? []),
            (array) ($configured[$stage] ?? [])
        );

        return array_values(array_filter($pipes, static function ($pipe) {
            return is_string($pipe) ? $pipe !== '' : is_object($pipe);
        }));
    }

    /**
     * @return array
     */
    protected function configuredPipeline(): array
    {
        try {
            $configured = config('cms.auth.pipeline', []);
        } catch (\Throwable $exception) {
            return [];
        }

        return is_array($configured) ? $configured : [];
    }

    /**
     * @return Container
     */
    protected function resolveContainer(): Container
    {
        return $this->container ?? evolutionCMS();
    }
}
