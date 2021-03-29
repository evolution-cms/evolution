<?php namespace EvolutionCMS\UserManager\Services\Users;

use EvolutionCMS\Exceptions\ServiceActionException;
use EvolutionCMS\Exceptions\ServiceValidationException;
use EvolutionCMS\UserManager\Interfaces\UserServiceInterface;
use \EvolutionCMS\Models\User;

class UserLogout implements UserServiceInterface
{
    /**
     * @var \string[][]
     */
    public $validate;

    /**
     * @var array
     */
    public $messages;

    /**
     * @var array
     */
    public $userData;

    /**
     * @var bool
     */
    public $events;

    /**
     * @var bool
     */
    public $cache;

    /**
     * @var array $validateErrors
     */
    public $validateErrors;

    /**
     * @var User
     */
    private $user;
    /**
     * @var int
     */
    private $blockedMinutes;
    /**
     * @var int
     */
    private $failedLoginAttempts;

    /**
     * @var
     */
    private $userSettings;

    /**
     * UserRegistration constructor.
     * @param array $userData
     * @param bool $events
     * @param bool $cache
     */
    public function __construct(array $userData, bool $events = true, bool $cache = true)
    {
        $this->validate = $this->getValidationRules();
        $this->messages = $this->getValidationMessages();
        $this->userData = $userData;
        $this->events = $events;
        $this->cache = $cache;
    }

    /**
     * @return \string[][]
     */
    public function getValidationRules(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getValidationMessages(): array
    {
        return [];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model
     * @throws ServiceActionException
     * @throws ServiceValidationException
     */
    public function process(): string
    {
        if (!$this->checkRules()) {
            throw new ServiceActionException(\Lang::get('global.error_no_privileges'));
        }

        if (!$this->validate()) {
            $exception = new ServiceValidationException();
            $exception->setValidationErrors($this->validateErrors);
            throw $exception;
        }


        $internalKey = EvolutionCMS()->getLoginUserID();
        if (!$internalKey) {
            return false;
        }
        $user = User::query()->find($internalKey);
        $username = '';
        if (!is_null($user)) {
            $user->refresh_token = '';
            $user->access_token = '';
            $user->valid_to = NULL;
            $user->save();
            $username = $_SESSION[evo()->getContext() . 'Shortname'];
            if(is_null($username)) $username = '';
            $sid = EvolutionCMS()->sid;
            if ($this->events) {
                // invoke OnBeforeManagerLogout event
                EvolutionCMS()->invokeEvent("OnBeforeManagerLogout",
                    array(
                        "userid" => $internalKey,
                        "username" => $username
                    ));
            }
        }
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', 0, MODX_BASE_URL);
        }
        @session_destroy(); // this sometimes generate an error in iis

        \EvolutionCMS\Models\ActiveUserLock::query()->where('sid', $sid)->delete();

        \EvolutionCMS\Models\ActiveUserSession::query()->where('sid', $sid)->delete();

        if ($this->events) {
            // invoke OnManagerLogout event
            EvolutionCMS()->invokeEvent("OnManagerLogout",
                array(
                    "userid" => $internalKey,
                    "username" => $username
                ));
        }
        return $username;
    }

    /**
     * @return bool
     */
    public function checkRules(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function validate(): bool
    {
        return true;
    }


}
