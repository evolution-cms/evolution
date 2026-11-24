<?php namespace EvolutionCMS\Auth;

use EvolutionCMS\Exceptions\ServiceActionException;
use EvolutionCMS\Interfaces\UserManagerInterface;
use EvolutionCMS\Models\User;
use EvolutionCMS\UserManager\Services\UserManager;
use EvolutionCMS\UserManager\Services\Users\UserLogin;
use EvolutionCMS\UserManager\Services\Users\UserLoginById;

/**
 * The UserManager that runs a login pipeline around the three ways into a session.
 *
 * This is the only class in the core that couples to the user-manager package, and it
 * couples by inheritance: everything it does not override — create, edit, delete,
 * logout, changePassword and the rest of the 22 methods — keeps working exactly as
 * shipped. The package itself is not modified, so its own compatibility is untouched
 * and it can be upgraded independently.
 *
 * PipelineUserManagerServiceProvider registers it by default. With an empty `cms.auth.pipeline`
 * it adds one thing to the package: TwoFactor::gate(), between the first factor and the session.
 * The gate sits inside the package's own login classes, where the user is known and nothing is
 * written yet. They are anonymous so that they take no autoloader entries.
 */
class PipelineUserManager extends UserManager implements UserManagerInterface
{
    /**
     * @var LoginPipeline
     */
    protected $pipeline;

    /**
     * @param LoginPipeline|null $pipeline
     */
    public function __construct($pipeline = null)
    {
        $this->pipeline = $pipeline instanceof LoginPipeline ? $pipeline : new LoginPipeline();
    }

    /**
     * {@inheritDoc}
     */
    public function login(array $userData, bool $events = true, bool $cache = true)
    {
        return $this->pipeline->run(
            new LoginAttempt(LoginPipeline::STAGE_LOGIN, $userData, $this, $events, $cache),
            function (LoginAttempt $attempt) {
                return (new class($attempt->data, $attempt->events, $attempt->cache) extends UserLogin {
                    public function authProcess()
                    {
                        PipelineUserManager::secondFactor(
                            $this->user,
                            $this->context,
                            $this->userData,
                            LoginPipeline::STAGE_LOGIN,
                            fn () => $this->regenerateSessionId()
                        );
                        parent::authProcess();
                    }
                })->process();
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function loginById(array $userData, bool $events = true, bool $cache = true)
    {
        return $this->pipeline->run(
            new LoginAttempt(LoginPipeline::STAGE_LOGIN_BY_ID, $userData, $this, $events, $cache),
            function (LoginAttempt $attempt) {
                return $this->loginByIdWithoutPipeline($attempt->data, $attempt->events, $attempt->cache);
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function hashLogin(array $userData, bool $events = true, bool $cache = true)
    {
        return $this->pipeline->run(
            new LoginAttempt(LoginPipeline::STAGE_HASH_LOGIN, $userData, $this, $events, $cache),
            function (LoginAttempt $attempt) {
                return $this->loginByHash($attempt);
            }
        );
    }

    /**
     * Log a user in by id without re-entering the pipeline.
     *
     * For pipes that authenticate the user themselves — social login, SSO — and only
     * need the session written. Calling loginById() from inside a pipe would run the
     * loginById pipeline nested inside the login pipeline, so every `*` pipe would run
     * twice for one attempt.
     *
     * The second factor is asked for all the same. A pipe whose own check is as good as two
     * wraps the call in TwoFactor::withoutChallenge().
     *
     * @param array $userData
     * @param bool $events
     * @param bool $cache
     * @return mixed
     */
    public function loginByIdWithoutPipeline(array $userData, bool $events = true, bool $cache = true)
    {
        return $this->startSession($userData, $events, $cache, LoginPipeline::STAGE_LOGIN_BY_ID);
    }

    /**
     * The package's own UserHashLogin cannot be loaded: it narrows a property of UserLogin to private.
     *
     * @throws ServiceActionException
     */
    protected function loginByHash(LoginAttempt $attempt)
    {
        $hash = $attempt->get('hash');
        $user = is_string($hash) && $hash !== '' ? User::query()->where('cachepwd', $hash)->first() : null;
        if ($user === null) {
            throw new ServiceActionException(\Lang::get('global.login_processor_unknown_user'));
        }

        $user = $this->startSession(
            ['id' => $user->getKey()] + array_intersect_key($attempt->data, ['context' => 1, 'rememberme' => 1, 'two_factor_code' => 1]),
            $attempt->events,
            $attempt->cache,
            LoginPipeline::STAGE_HASH_LOGIN
        );
        // the link is spent
        $user->cachepwd = '';
        $user->save();

        return $user;
    }

    protected function startSession(array $userData, bool $events, bool $cache, string $stage)
    {
        $login = new class($userData, $events, $cache) extends UserLoginById {
            public string $stage = '';

            public function authProcess()
            {
                PipelineUserManager::secondFactor(
                    $this->user,
                    $this->context,
                    $this->userData,
                    $this->stage,
                    fn () => $this->regenerateSessionId()
                );
                parent::authProcess();
            }
        };
        $login->stage = $stage;

        return $login->process();
    }

    /**
     * @internal for the login classes above
     */
    public static function secondFactor(User $user, string $context, array $data, string $stage, callable $rotate): void
    {
        evo()->make(TwoFactor::class)->gate($user, $context, $data, $stage, $rotate);
    }
}
