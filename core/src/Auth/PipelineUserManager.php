<?php namespace EvolutionCMS\Auth;

use EvolutionCMS\Exceptions\ServiceActionException;
use EvolutionCMS\Interfaces\UserManagerInterface;
use EvolutionCMS\Models\User;
use EvolutionCMS\UserManager\Services\UserManager;

/**
 * The UserManager that runs a login pipeline around the three ways into a session.
 *
 * This is the only class in the core that couples to the user-manager package, and it
 * couples by inheritance: everything it does not override — create, edit, delete,
 * logout, changePassword and the rest of the 22 methods — keeps working exactly as
 * shipped. The package itself is not modified, so its own compatibility is untouched
 * and it can be upgraded independently.
 *
 * It is not registered by default. A site opts in by pointing the provider key at
 * PipelineUserManagerServiceProvider:
 *
 *     // core/custom/config/app/providers/Evolution_UserManager.php
 *     <?php return EvolutionCMS\Providers\PipelineUserManagerServiceProvider::class;
 *
 * With an empty `cms.auth.pipeline` this class is a pass-through, so opting in changes
 * nothing until pipes are configured.
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
                return parent::login($attempt->data, $attempt->events, $attempt->cache);
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
                return parent::loginById($attempt->data, $attempt->events, $attempt->cache);
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
     * @param array $userData
     * @param bool $events
     * @param bool $cache
     * @return mixed
     */
    public function loginByIdWithoutPipeline(array $userData, bool $events = true, bool $cache = true)
    {
        return parent::loginById($userData, $events, $cache);
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

        $user = parent::loginById(
            ['id' => $user->getKey()] + array_intersect_key($attempt->data, ['context' => 1, 'rememberme' => 1]),
            $attempt->events,
            $attempt->cache
        );
        // the link is spent
        $user->cachepwd = '';
        $user->save();

        return $user;
    }
}
